<?php
/* =====================================================================
   Masuk dengan Google.
   Menerima ID token (JWT) dari tombol Google di halaman login, memverifikasi
   ke server Google, lalu mencari/membuat akun berdasarkan email, dan login.
   ===================================================================== */
require __DIR__ . '/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
function jgo($a){ echo json_encode($a); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); jgo(['ok'=>false,'error'=>'Metode salah.']); }

$cred = $_POST['credential'] ?? '';
if (!$cred) jgo(['ok'=>false,'error'=>'Token Google kosong.']);

/* Verifikasi ID token ke Google (memeriksa tanda tangan, penerima, & masa berlaku). */
$info = null;
$url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($cred);
try {
  if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>15, CURLOPT_SSL_VERIFYPEER=>true, CURLOPT_SSL_VERIFYHOST=>2]);
    $res = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    if ($res !== false && $code === 200) $info = json_decode($res, true);
  } else {
    $res = @file_get_contents($url);
    if ($res !== false) $info = json_decode($res, true);
  }
} catch (Throwable $e) { $info = null; }

if (!$info || empty($info['sub'])) jgo(['ok'=>false,'error'=>'Verifikasi Google gagal. Coba lagi.']);

/* Cek keabsahan token */
$aud = $info['aud'] ?? '';
$iss = $info['iss'] ?? '';
$exp = (int)($info['exp'] ?? 0);
$ev  = $info['email_verified'] ?? 'false';
if ($aud !== GOOGLE_CLIENT_ID) jgo(['ok'=>false,'error'=>'Token bukan untuk aplikasi ini.']);
if ($iss !== 'accounts.google.com' && $iss !== 'https://accounts.google.com') jgo(['ok'=>false,'error'=>'Penerbit token tidak sah.']);
if ($exp < time()) jgo(['ok'=>false,'error'=>'Token kedaluwarsa, coba lagi.']);
if ($ev !== true && $ev !== 'true') jgo(['ok'=>false,'error'=>'Email Google belum terverifikasi.']);

$email = strtolower(trim($info['email'] ?? ''));
$nama  = trim($info['name'] ?? ($info['given_name'] ?? '')) ?: $email;
if ($email === '') jgo(['ok'=>false,'error'=>'Email tidak ada pada akun Google.']);

/* Cari akun berdasarkan email; kalau belum ada, buat baru. */
try {
  $s = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
  $s->execute([$email]); $u = $s->fetch();

  if (!$u) {
    // username unik dari bagian depan email
    $base = preg_replace('/[^a-z0-9._-]/', '', explode('@', $email)[0]);
    if ($base === '') $base = 'user';
    $username = $base; $i = 1;
    $chk = $pdo->prepare("SELECT 1 FROM users WHERE username = ?");
    do { $chk->execute([$username]); $ada = $chk->fetchColumn(); if ($ada) $username = $base . (++$i); } while ($ada);
    $rand = bin2hex(random_bytes(24)); // password acak (login akun ini hanya via Google)
    $ins = $pdo->prepare("INSERT INTO users (username, password_hash, nama, email, created_at) VALUES (?,?,?,?,?)");
    $ins->execute([$username, password_hash($rand, PASSWORD_DEFAULT), $nama, $email, date('Y-m-d H:i:s')]);
    $uid = (int)$pdo->lastInsertId();
    $unameSes = $nama;
  } else {
    $uid = (int)$u['id'];
    $unameSes = $u['nama'] ?: $u['username'];
  }
} catch (Throwable $e) {
  error_log('[e-Kinerja] Google login DB error: ' . $e->getMessage());
  jgo(['ok'=>false,'error'=>'Gagal menyimpan akun. Coba lagi.']);
}

session_regenerate_id(true);
$_SESSION['uid']   = $uid;
$_SESSION['uname'] = $unameSes;
jgo(['ok'=>true]);
