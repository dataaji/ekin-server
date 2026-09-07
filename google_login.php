<?php
/* =====================================================================
   Masuk dengan Google.
   Menerima ID token (JWT) dari tombol Google, memverifikasi keasliannya,
   lalu mencari/membuat akun berdasarkan email, dan login.

   Verifikasi utama dilakukan LOKAL (cek tanda tangan pakai kunci publik
   Google yang di-cache) supaya tidak bergantung panggilan keluar tiap login.
   Bila gagal, jatuh ke cara cadangan (endpoint tokeninfo Google).
   ===================================================================== */
require __DIR__ . '/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
function jgo($a){ echo json_encode($a); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); jgo(['ok'=>false,'error'=>'Metode salah.']); }
$cred = $_POST['credential'] ?? '';
if (!$cred) jgo(['ok'=>false,'error'=>'Token Google kosong.']);

/* base64url decode */
function b64u($d){ $r = strlen($d) % 4; if ($r) $d .= str_repeat('=', 4 - $r); return base64_decode(strtr($d, '-_', '+/')); }

/* ambil URL: curl kalau ada, kalau tidak file_get_contents. return [body|null, httpcode, err] */
function httpGet($url){
  if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_CONNECTTIMEOUT=>8, CURLOPT_TIMEOUT=>12, CURLOPT_SSL_VERIFYPEER=>true, CURLOPT_SSL_VERIFYHOST=>2]);
    $b = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
    return [$b === false ? null : $b, $code, $err];
  }
  $b = @file_get_contents($url);
  return [$b === false ? null : $b, 0, $b === false ? 'file_get_contents gagal' : ''];
}

/* kunci publik Google (format PEM per kid), di-cache 6 jam di folder sementara server */
function googleCerts(&$why){
  $cache = sys_get_temp_dir() . '/ekin_gcerts.json';
  if (is_file($cache) && (time() - filemtime($cache) < 6*3600)) {
    $c = json_decode(@file_get_contents($cache), true); if (is_array($c) && $c) return $c;
  }
  [$b, $code, $err] = httpGet('https://www.googleapis.com/oauth2/v1/certs');
  if ($b && $code === 200) { $c = json_decode($b, true); if (is_array($c) && $c) { @file_put_contents($cache, $b); return $c; } }
  if (is_file($cache)) { $c = json_decode(@file_get_contents($cache), true); if (is_array($c) && $c) return $c; } // pakai cache lama
  $why = 'ambil kunci Google gagal (http=' . $code . ' ' . $err . ')';
  return null;
}

/* ---- Verifikasi token ---- */
$payload = null; $verified = false; $why = '';
$parts = explode('.', $cred);
if (count($parts) === 3) {
  $header  = json_decode(b64u($parts[0]), true);
  $payload = json_decode(b64u($parts[1]), true);
  $sig     = b64u($parts[2]);
  $kid = $header['kid'] ?? ''; $alg = $header['alg'] ?? '';
  if ($alg === 'RS256' && $kid && function_exists('openssl_verify')) {
    $certs = googleCerts($why);
    if ($certs && !empty($certs[$kid])) {
      $ok = openssl_verify($parts[0] . '.' . $parts[1], $sig, $certs[$kid], OPENSSL_ALGO_SHA256);
      if ($ok === 1) $verified = true; else $why = 'tanda tangan tidak cocok';
    }
  } else { $why = 'header token tidak sesuai'; }
}

/* Cadangan: verifikasi lewat endpoint tokeninfo Google */
if (!$verified) {
  [$b, $code, $err] = httpGet('https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($cred));
  $ti = $b ? json_decode($b, true) : null;
  if ($ti && $code === 200 && !empty($ti['sub'])) { $payload = $ti; $verified = true; }
  else jgo(['ok'=>false,'error'=>'Verifikasi Google gagal. [' . $why . ' | tokeninfo http=' . $code . ' ' . $err . ']']);
}

/* ---- Cek klaim token ---- */
if (!$payload || empty($payload['sub'])) jgo(['ok'=>false,'error'=>'Token Google tidak berisi data.']);
$aud = $payload['aud'] ?? '';
$iss = $payload['iss'] ?? '';
$exp = (int)($payload['exp'] ?? 0);
$ev  = $payload['email_verified'] ?? 'false';
if ($aud !== GOOGLE_CLIENT_ID) jgo(['ok'=>false,'error'=>'Token bukan untuk aplikasi ini.']);
if ($iss !== 'accounts.google.com' && $iss !== 'https://accounts.google.com') jgo(['ok'=>false,'error'=>'Penerbit token tidak sah.']);
if ($exp < time() - 60) jgo(['ok'=>false,'error'=>'Token kedaluwarsa, coba lagi.']);
if ($ev !== true && $ev !== 'true') jgo(['ok'=>false,'error'=>'Email Google belum terverifikasi.']);

$email = strtolower(trim($payload['email'] ?? ''));
$nama  = trim($payload['name'] ?? ($payload['given_name'] ?? '')) ?: $email;
if ($email === '') jgo(['ok'=>false,'error'=>'Email tidak ada pada akun Google.']);

/* ---- Cari akun by email; kalau belum ada, buat ---- */
try {
  $s = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
  $s->execute([$email]); $u = $s->fetch();
  if (!$u) {
    $base = preg_replace('/[^a-z0-9._-]/', '', explode('@', $email)[0]); if ($base === '') $base = 'user';
    $username = $base; $i = 1;
    $chk = $pdo->prepare("SELECT 1 FROM users WHERE username = ?");
    do { $chk->execute([$username]); $ada = $chk->fetchColumn(); if ($ada) $username = $base . (++$i); } while ($ada);
    $rand = bin2hex(random_bytes(24));
    $pdo->prepare("INSERT INTO users (username, password_hash, nama, email, created_at) VALUES (?,?,?,?,?)")
        ->execute([$username, password_hash($rand, PASSWORD_DEFAULT), $nama, $email, date('Y-m-d H:i:s')]);
    $uid = (int)$pdo->lastInsertId(); $unameSes = $nama;
  } else { $uid = (int)$u['id']; $unameSes = $u['nama'] ?: $u['username']; }
} catch (Throwable $e) {
  error_log('[e-Kinerja] Google login DB error: ' . $e->getMessage());
  jgo(['ok'=>false,'error'=>'Gagal menyimpan akun. Coba lagi.']);
}

session_regenerate_id(true);
$_SESSION['uid']   = $uid;
$_SESSION['uname'] = $unameSes;
jgo(['ok'=>true]);
