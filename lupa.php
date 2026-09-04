<?php
require __DIR__ . '/bootstrap.php';
if (userCount($pdo)===0) redirect('setup.php');
if (currentUserId()) redirect('index.php');

headerAman();
$err=''; $done=false;
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $sisa = limitSisaDetik($pdo,'lupa',5,30);   // kode pemulihan pendek -> rem lebih ketat
  if ($sisa>0) { $err='Terlalu banyak percobaan. Coba lagi dalam '.ceil($sisa/60).' menit.'; }
  elseif (!csrf_ok_form()) { $err='Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.'; }
  else {
  $user=trim($_POST['username']??''); $code=strtoupper(trim($_POST['code']??''));
  $new=$_POST['new']??''; $new2=$_POST['new2']??'';
  $stmt=$pdo->prepare("SELECT * FROM users WHERE username=? LIMIT 1"); $stmt->execute([$user]); $u=$stmt->fetch();
  if (!$u || empty($u['recovery_hash'])) { limitCatatGagal($pdo,'lupa',30); $err='Akun tidak ditemukan atau belum punya kode pemulihan.'; usleep(400000); }
  elseif (!password_verify($code,$u['recovery_hash'])) { limitCatatGagal($pdo,'lupa',30); $err='Kode pemulihan salah.'; usleep(400000); }
  elseif (strlen($new)<6) { $err='Password baru minimal 6 karakter.'; }
  elseif ($new!==$new2) { $err='Konfirmasi password tidak sama.'; }
  else {
    // ganti password; kode pemulihan sekali pakai -> dikosongkan agar dibuat ulang
    $pdo->prepare("UPDATE users SET password_hash=?, recovery_hash=NULL WHERE id=?")
        ->execute([password_hash($new,PASSWORD_DEFAULT),$u['id']]);
    limitBersih($pdo,'lupa');
    $done=true;
  }
  }
}
?><!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Lupa Password — <?= e(APP_NAME) ?></title>
<?php include __DIR__ . '/auth_style.php'; ?>
</head><body>
  <?php if($done): ?>
  <div class="box" style="text-align:center">
    <div class="logo">✅</div>
    <h1>Berhasil</h1>
    <div class="sub">Password sudah diganti. Silakan masuk dengan password baru.</div>
    <a href="login.php"><button type="button">Ke Halaman Masuk</button></a>
    <div class="hint">Kode pemulihan lama sudah hangus. Buat kode baru lewat menu <b>Akun</b> setelah masuk.</div>
  </div>
  <?php else: ?>
  <form class="box" method="post" autocomplete="off">
    <div class="logo">🛟</div>
    <h1>Lupa Password</h1>
    <div class="sub">Pulihkan dengan kode pemulihanmu</div>
    <?php if($err): ?><div class="err"><?= e($err) ?></div><?php endif; ?>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <div class="field"><label>Username</label><input type="text" name="username" value="<?= e($_POST['username']??'') ?>" required></div>
    <div class="field"><label>Kode Pemulihan</label><input type="text" name="code" placeholder="mis. A1B2-C3D4" required></div>
    <div class="field"><label>Password Baru</label><input type="password" name="new" placeholder="Minimal 6 karakter" required></div>
    <div class="field"><label>Ulangi Password Baru</label><input type="password" name="new2" required></div>
    <button type="submit">Reset Password</button>
    <div class="hint"><a href="login.php" style="color:#0d9488;font-weight:700;text-decoration:none">← Kembali ke Masuk</a><br>
      Belum punya kode pemulihan? Buat lewat menu <b>Akun</b> saat sudah bisa masuk.</div>
  </form>
  <?php endif; ?>
</body></html>
