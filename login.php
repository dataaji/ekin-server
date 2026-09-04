<?php
require __DIR__ . '/bootstrap.php';

if (userCount($pdo) === 0) redirect('setup.php');   // belum ada akun -> setup dulu
if (currentUserId()) redirect('index.php');          // sudah login

headerAman();
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $sisa = limitSisaDetik($pdo, 'login');   // rem: 8 gagal -> tunggu 15 menit
  if ($sisa > 0) {
    $err = 'Terlalu banyak percobaan gagal. Coba lagi dalam ' . ceil($sisa / 60) . ' menit.';
  } elseif (!csrf_ok_form()) {
    $err = 'Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.';
  } else {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$user]);
    $u = $stmt->fetch();
    if ($u && password_verify($pass, $u['password_hash'])) {
      limitBersih($pdo, 'login');
      session_regenerate_id(true);
      $_SESSION['uid']   = (int)$u['id'];
      $_SESSION['uname'] = $u['nama'] ?: $u['username'];
      redirect('index.php');
    } else {
      limitCatatGagal($pdo, 'login');
      $err = 'Username atau password salah.';
      usleep(400000); // sedikit jeda untuk memperlambat percobaan
    }
  }
}
?><!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk — <?= e(APP_NAME) ?></title>
<?php include __DIR__ . '/auth_style.php'; ?>
</head><body>
  <form class="box" method="post" autocomplete="off">
    <div class="logo">📋</div>
    <h1><?= e(APP_NAME) ?></h1>
    <div class="sub"><?= e(APP_SUBTITLE) ?></div>
    <?php if ($err): ?><div class="err"><?= e($err) ?></div><?php endif; ?>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <div class="field"><label>Username</label>
      <input type="text" name="username" value="<?= e($_POST['username'] ?? '') ?>" required autofocus></div>
    <div class="field"><label>Password</label>
      <input type="password" name="password" required></div>
    <button type="submit">Masuk</button>
    <div class="hint"><a href="lupa.php" style="color:#0d9488;font-weight:700;text-decoration:none">Lupa password?</a></div>
  </form>
</body></html>
