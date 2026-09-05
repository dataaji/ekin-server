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
<script src="https://accounts.google.com/gsi/client" async defer></script>
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
    <div style="display:flex;align-items:center;gap:10px;margin:16px 0;color:#94a3b8;font-size:12px"><span style="flex:1;height:1px;background:#e2e8f0"></span>atau<span style="flex:1;height:1px;background:#e2e8f0"></span></div>
    <div id="g_id_onload" data-client_id="<?= e(GOOGLE_CLIENT_ID) ?>" data-callback="onGoogleLogin" data-auto_prompt="false"></div>
    <div class="g_id_signin" data-type="standard" data-theme="outline" data-size="large" data-text="signin_with" data-shape="rectangular" data-logo_alignment="center" style="display:flex;justify-content:center"></div>
  </form>
<script>
function onGoogleLogin(resp){
  var b=document.querySelector('.g_id_signin'); if(b) b.style.opacity='.5';
  fetch('google_login.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'credential='+encodeURIComponent(resp.credential)})
    .then(function(r){return r.json();})
    .then(function(j){ if(j&&j.ok){ location.href='index.php'; } else { alert((j&&j.error)||'Gagal masuk dengan Google.'); if(b) b.style.opacity='1'; } })
    .catch(function(){ alert('Gagal menghubungi server.'); if(b) b.style.opacity='1'; });
}
</script>
</body></html>
