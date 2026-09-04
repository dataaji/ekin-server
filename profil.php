<?php
require __DIR__ . '/bootstrap.php';
requireLogin();
headerAman();

$msg=''; $err=''; $newCode='';
$stmt=$pdo->prepare("SELECT * FROM users WHERE id=?"); $stmt->execute([currentUserId()]); $u=$stmt->fetch();

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $tok=$_POST['csrf']??'';
  if (!$tok || !hash_equals($_SESSION['csrf']??'', $tok)) { $err='Token keamanan tidak valid, muat ulang halaman.'; }
  else {
    $act=$_POST['act']??'';
    if ($act==='ganti') {
      $old=$_POST['old']??''; $new=$_POST['new']??''; $new2=$_POST['new2']??'';
      if (!password_verify($old,$u['password_hash'])) $err='Password lama salah.';
      elseif (strlen($new)<6) $err='Password baru minimal 6 karakter.';
      elseif ($new!==$new2) $err='Konfirmasi password baru tidak sama.';
      else { $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([password_hash($new,PASSWORD_DEFAULT),currentUserId()]); $msg='Password berhasil diganti.'; }
    } elseif ($act==='recovery') {
      $raw=strtoupper(bin2hex(random_bytes(4))); $newCode=substr($raw,0,4).'-'.substr($raw,4,4);
      $pdo->prepare("UPDATE users SET recovery_hash=? WHERE id=?")->execute([password_hash($newCode,PASSWORD_DEFAULT),currentUserId()]);
      $msg='Kode pemulihan baru dibuat. Simpan baik-baik — hanya ditampilkan sekali.';
      $stmt->execute([currentUserId()]); $u=$stmt->fetch();
    }
  }
}
$csrf=csrf_token();
$hasRec=!empty($u['recovery_hash']);
?><!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Akun — <?= e(APP_NAME) ?></title>
<?php include __DIR__ . '/auth_style.php'; ?>
<style>
  .box{max-width:440px}
  .sec{margin-top:18px;padding-top:18px;border-top:1px solid #e2e8f0}
  .sec h2{font-size:15px;margin-bottom:12px}
  .ok{background:#dcfce7;color:#15803d;border-radius:10px;padding:10px 12px;font-size:13px;margin-bottom:14px}
  .codebox{background:#0f172a;color:#5eead4;font-size:20px;font-weight:800;letter-spacing:2px;text-align:center;padding:14px;border-radius:12px;margin:10px 0;font-family:ui-monospace,monospace}
  .back{display:inline-block;margin-bottom:14px;color:#0d9488;font-weight:700;text-decoration:none;font-size:13px}
  .muted{font-size:12.5px;color:#64748b;line-height:1.5}
  button.sec-btn{background:#334155}
</style>
</head><body>
  <div class="box">
    <a class="back" href="index.php">← Kembali ke aplikasi</a>
    <div class="logo">⚙️</div>
    <h1>Akun</h1>
    <div class="sub">Masuk sebagai <b><?= e($u['username']) ?></b></div>

    <?php if($msg): ?><div class="ok"><?= e($msg) ?></div><?php endif; ?>
    <?php if($err): ?><div class="err"><?= e($err) ?></div><?php endif; ?>
    <?php if($newCode): ?><div class="codebox"><?= e($newCode) ?></div>
      <p class="muted">Catat kode ini di tempat aman. Dipakai untuk memulihkan akun bila lupa password (lewat halaman "Lupa password").</p><?php endif; ?>

    <div class="sec">
      <h2>🔑 Ganti Password</h2>
      <form method="post" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="act" value="ganti">
        <div class="field"><label>Password lama</label><input type="password" name="old" required></div>
        <div class="field"><label>Password baru</label><input type="password" name="new" placeholder="Minimal 6 karakter" required></div>
        <div class="field"><label>Ulangi password baru</label><input type="password" name="new2" required></div>
        <button type="submit">Ganti Password</button>
      </form>
    </div>

    <div class="sec">
      <h2>🛟 Kode Pemulihan</h2>
      <p class="muted">Status: <?= $hasRec ? '<b style="color:#15803d">sudah diatur</b>' : '<b style="color:#b91c1c">belum diatur</b>' ?>.
        Kode ini dipakai untuk reset password bila lupa (tanpa email). Membuat kode baru akan menggantikan yang lama.</p>
      <form method="post" style="margin-top:10px">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="act" value="recovery">
        <button type="submit" class="sec-btn"><?= $hasRec ? 'Buat Kode Pemulihan Baru' : 'Buat Kode Pemulihan' ?></button>
      </form>
    </div>
  </div>
</body></html>
