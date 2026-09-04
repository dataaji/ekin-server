<?php
/* Buat akun pertama (hanya bisa dijalankan sekali, saat belum ada user) */
require __DIR__ . '/bootstrap.php';

if (userCount($pdo) > 0) redirect('login.php');
headerAman();   // sudah ada akun -> ke login

$err = ''; $recoveryCode = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama  = trim($_POST['nama'] ?? '');
  $user  = trim($_POST['username'] ?? '');
  $pass  = $_POST['password'] ?? '';
  $pass2 = $_POST['password2'] ?? '';

  if ($user === '' || $pass === '') {
    $err = 'Username dan password wajib diisi.';
  } elseif (!preg_match('/^[A-Za-z0-9._-]{3,60}$/', $user)) {
    $err = 'Username 3-60 karakter, hanya huruf/angka/titik/garis.';
  } elseif (strlen($pass) < 6) {
    $err = 'Password minimal 6 karakter.';
  } elseif ($pass !== $pass2) {
    $err = 'Konfirmasi password tidak sama.';
  } else {
    // Cek ganda (race) — meski praktis tidak akan terjadi
    if (userCount($pdo) > 0) redirect('login.php');
    $raw = strtoupper(bin2hex(random_bytes(4))); $recoveryCode = substr($raw,0,4).'-'.substr($raw,4,4);
    $stmt = $pdo->prepare("INSERT INTO users (username,password_hash,nama,recovery_hash,created_at) VALUES (?,?,?,?,?)");
    $stmt->execute([$user, password_hash($pass, PASSWORD_DEFAULT), $nama ?: $user, password_hash($recoveryCode, PASSWORD_DEFAULT), date('Y-m-d H:i:s')]);
    $_SESSION['uid']   = (int)$pdo->lastInsertId();
    $_SESSION['uname'] = $nama ?: $user;
    // jangan redirect: tampilkan kode pemulihan sekali
  }
}
?><!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Setup Akun — <?= e(APP_NAME) ?></title>
<?php include __DIR__ . '/auth_style.php'; ?>
<style>.codebox{background:#0f172a;color:#5eead4;font-size:22px;font-weight:800;letter-spacing:3px;text-align:center;padding:16px;border-radius:12px;margin:14px 0;font-family:ui-monospace,monospace}</style>
</head><body>
  <?php if ($recoveryCode): ?>
  <div class="box" style="text-align:center">
    <div class="logo">🛟</div>
    <h1>Simpan Kode Pemulihan</h1>
    <div class="sub">Catat kode ini di tempat aman. Dipakai untuk reset password bila lupa.</div>
    <div class="codebox"><?= e($recoveryCode) ?></div>
    <p class="hint" style="margin-top:0">Kode ini <b>hanya ditampilkan sekali</b>. Bisa dibuat ulang lewat menu Akun.</p>
    <a href="index.php"><button type="button">Sudah Saya Catat — Masuk Aplikasi</button></a>
  </div>
  <?php else: ?>
  <form class="box" method="post" autocomplete="off">
    <div class="logo">🔐</div>
    <h1>Buat Akun</h1>
    <div class="sub">Langkah sekali saja untuk mengamankan aplikasi</div>
    <?php if ($err): ?><div class="err"><?= e($err) ?></div><?php endif; ?>
    <div class="field"><label>Nama (untuk sapaan)</label>
      <input type="text" name="nama" value="<?= e($_POST['nama'] ?? '') ?>" placeholder="Nama lengkap"></div>
    <div class="field"><label>Username</label>
      <input type="text" name="username" value="<?= e($_POST['username'] ?? '') ?>" placeholder="mis. winarto" required></div>
    <div class="field"><label>Password</label>
      <input type="password" name="password" placeholder="Minimal 6 karakter" required></div>
    <div class="field"><label>Ulangi Password</label>
      <input type="password" name="password2" placeholder="Ketik ulang password" required></div>
    <button type="submit">Buat Akun &amp; Masuk</button>
    <div class="hint">Ingat baik-baik username &amp; password ini.<br>Setelah ini kamu diberi <b>kode pemulihan</b> untuk jaga-jaga bila lupa.</div>
  </form>
  <?php endif; ?>
</body></html>
