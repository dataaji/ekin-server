<?php
require __DIR__ . '/bootstrap.php';
requireLogin();
headerAman();

$msg=''; $err='';
$stmt=$pdo->prepare("SELECT * FROM users WHERE id=?"); $stmt->execute([currentUserId()]); $u=$stmt->fetch();

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $tok=$_POST['csrf']??'';
  if (!$tok || !hash_equals($_SESSION['csrf']??'', $tok)) { $err='Token keamanan tidak valid, muat ulang halaman.'; }
  else {
    $act=$_POST['act']??'';
    if ($act==='nama') {
      $nama=trim($_POST['nama']??'');
      if ($nama==='') $err='Nama tidak boleh kosong.';
      elseif (mb_strlen($nama)>60) $err='Nama terlalu panjang (maksimal 60 karakter).';
      else {
        $pdo->prepare("UPDATE users SET nama=? WHERE id=?")->execute([$nama,currentUserId()]);
        $_SESSION['uname']=$nama;                 // supaya sapaan di aplikasi ikut berubah
        $stmt->execute([currentUserId()]); $u=$stmt->fetch();
        $msg='Nama tampilan berhasil diganti.';
      }
    }
  }
}
$csrf=csrf_token();
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
  .back{display:inline-block;margin-bottom:14px;color:#0d9488;font-weight:700;text-decoration:none;font-size:13px}
  .muted{font-size:12.5px;color:#64748b;line-height:1.5}
  .idcard{background:#f1f5f9;border:1px solid #e2e8f0;border-radius:12px;padding:12px 14px;font-size:13px;line-height:1.7;margin-bottom:6px}
  .idcard b{color:#0f172a}
</style>
</head><body>
  <div class="box">
    <a class="back" href="index.php">← Kembali ke aplikasi</a>
    <div class="logo">⚙️</div>
    <h1>Akun</h1>
    <div class="sub">Pengaturan akun kamu</div>

    <?php if($msg): ?><div class="ok"><?= e($msg) ?></div><?php endif; ?>
    <?php if($err): ?><div class="err"><?= e($err) ?></div><?php endif; ?>

    <div class="idcard">
      <?php if(!empty($u['email'])): ?>Masuk dengan Google: <b><?= e($u['email']) ?></b><?php else: ?>Nama pengguna: <b><?= e($u['username']) ?></b><?php endif; ?>
    </div>

    <div class="sec">
      <h2>✏️ Nama Tampilan</h2>
      <p class="muted">Nama ini yang muncul di sapaan &amp; pojok aplikasi.</p>
      <form method="post" autocomplete="off" style="margin-top:10px">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="act" value="nama">
        <div class="field"><label>Nama</label>
          <input type="text" name="nama" value="<?= e($u['nama'] ?: $u['username']) ?>" maxlength="60" placeholder="mis. Aji Winarto" required autofocus></div>
        <button type="submit">Simpan Nama</button>
      </form>
    </div>

    <div class="sec">
      <a class="back" href="logout.php" style="color:#b91c1c;margin:0">⏻ Keluar dari akun</a>
    </div>
  </div>
</body></html>
