<?php
/* =====================================================================
   Penjaga akses berkas — dipakai oleh server bawaan PHP (php -S ... router.php).

   Server bawaan PHP TIDAK membaca .htaccess, begitu pula Nginx. Tanpa berkas
   ini, folder uploads/ dan data/ekin.db bisa dibuka langsung lewat browser.
   Router ini menolak semua permintaan ke berkas yang tidak boleh dibuka
   publik, lalu menyerahkan sisanya ke aplikasi seperti biasa.
   ===================================================================== */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = urldecode($path);
$path = str_replace('\\', '/', $path);
$rel  = ltrim($path, '/');

$tolak = function () {
  http_response_code(403);
  header('Content-Type: text/html; charset=UTF-8');
  echo '<h2>403 — Akses ditolak</h2><p>Berkas ini tidak boleh dibuka langsung. '
     . 'Bukti dukung hanya bisa diunduh dari dalam aplikasi setelah masuk.</p>';
  return true;
};

/* 1. Folder tertutup: seluruh isi uploads/ dan data/ */
if (preg_match('#^(uploads|data)(/|$)#i', $rel))            return $tolak();

/* 2. Berkas sistem yang tidak boleh dibuka langsung */
if (preg_match('#(^|/)(config|bootstrap|auth_style)\.php$#i', $rel)) return $tolak();

/* 3. Berkas pendukung: log, database, catatan, skrip, cadangan */
if (preg_match('#\.(log|db|sqlite|sqlite3|md|bat|ini|bak|sql)$#i', $rel)) return $tolak();

/* 4. Semua berkas/folder tersembunyi (.htaccess, .user.ini, .git, dll) */
if (preg_match('#(^|/)\.#', $rel))                          return $tolak();

/* Sisanya: layani seperti biasa (false = biarkan server bawaan yang urus) */
return false;
