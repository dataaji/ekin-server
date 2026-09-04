<?php
/* =====================================================================
   Inisialisasi: koneksi DB, sesi, migrasi tabel, fungsi bantu.
   File ini di-include oleh semua halaman lain.
   ===================================================================== */

require __DIR__ . '/config.php';

date_default_timezone_set(APP_TZ);
mb_internal_encoding('UTF-8');

// ---- Sesi aman ----
session_set_cookie_params([
  'lifetime' => 0,
  'path'     => '/',
  'httponly' => true,
  'secure'   => !empty($_SERVER['HTTPS']),
  'samesite' => 'Lax',
]);
session_start();

// ---- Koneksi database (mendukung MySQL untuk hosting & SQLite untuk lokal) ----
$IS_SQLITE = (defined('DB_DRIVER') && DB_DRIVER === 'sqlite');
try {
  if ($IS_SQLITE) {
    $dataDir = __DIR__ . '/data';
    if (!is_dir($dataDir)) @mkdir($dataDir, 0755, true);
    // Lindungi file database bila folder ini kebetulan ada di web server
    $htDb = $dataDir . '/.htaccess';
    if (!file_exists($htDb)) @file_put_contents($htDb, "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
    // DB_PATH di config.php boleh menunjuk ke disk lokal supaya lebih cepat
    $dbFile = (defined('DB_PATH') && DB_PATH !== '') ? DB_PATH : $dataDir . '/ekin.db';
    $dbDir  = dirname($dbFile);
    if (!is_dir($dbDir)) @mkdir($dbDir, 0755, true);
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    /* Penyetelan kecepatan. Berpengaruh besar bila berkas database berada di
       folder tersinkron (Google Drive/OneDrive) yang aksesnya lambat:
       cache lebih besar = lebih jarang membaca ulang berkas. */
    try {
      $pdo->exec("PRAGMA cache_size=-16000");   // ~16 MB cache halaman
      $pdo->exec("PRAGMA temp_store=MEMORY");
      $pdo->exec("PRAGMA synchronous=NORMAL");
    } catch (Throwable $e) {}
    // Sediakan fungsi DATE_FORMAT agar query yang sama jalan di SQLite
    $pdo->sqliteCreateFunction('DATE_FORMAT', function($d, $f){
      $map = str_replace(['%Y','%m','%d','%H','%i','%s'], ['Y','m','d','H','i','s'], $f);
      return date($map, strtotime($d));
    });
  } else {
    $pdo = new PDO(
      'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
      DB_USER, DB_PASS,
      [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
      ]
    );
  }
} catch (Throwable $e) {
  http_response_code(500);
  // Pesan rinci hanya ditulis ke log server, tidak ditampilkan ke pengunjung
  // (isinya bisa memuat nama database / user).
  error_log('[e-Kinerja] Koneksi DB gagal: ' . $e->getMessage());
  die('<h2>Koneksi database gagal.</h2><p>Periksa data di <b>config.php</b> (DB_DRIVER / nama database / user / password). Rincian kesalahan ada di log server.</p>');
}

/* ---- Migrasi otomatis (buat tabel bila belum ada) ----
   PENTING untuk kecepatan: seluruh blok CREATE/ALTER ini dulu dijalankan pada
   SETIAP permintaan halaman — di database yang tersimpan di folder tersinkron
   (Google Drive) itu menambah puluhan milidetik tiap klik. Sekarang dijaga
   penanda versi: kalau skema sudah sesuai, blok ini dilewati seluruhnya.
   Naikkan SKEMA_VERSI setiap kali ada tabel/kolom baru. */
define('SKEMA_VERSI', '2026-09-04c');
$__perluMigrasi = true;
try {
  $__v = $pdo->query("SELECT v FROM appmeta WHERE k='skema_versi'")->fetchColumn();
  if ($__v === SKEMA_VERSI) $__perluMigrasi = false;
} catch (Throwable $e) { $__perluMigrasi = true; }

if ($__perluMigrasi) {
// Struktur bertingkat: kegiatan (bulanan) -> subkegiatan -> harian -> berkas
if ($IS_SQLITE) {
  $pdo->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE NOT NULL, password_hash TEXT NOT NULL, nama TEXT, created_at TEXT NOT NULL)");
  $pdo->exec("CREATE TABLE IF NOT EXISTS kegiatan (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, nama TEXT NOT NULL, bulan TEXT NOT NULL, created_at TEXT NOT NULL)");
  $pdo->exec("CREATE TABLE IF NOT EXISTS subkegiatan (id INTEGER PRIMARY KEY AUTOINCREMENT, kegiatan_id INTEGER NOT NULL, nama TEXT NOT NULL, created_at TEXT NOT NULL)");
  $pdo->exec("CREATE TABLE IF NOT EXISTS subsub (id INTEGER PRIMARY KEY AUTOINCREMENT, subkegiatan_id INTEGER NOT NULL, nama TEXT NOT NULL, created_at TEXT NOT NULL)");
  $pdo->exec("CREATE TABLE IF NOT EXISTS harian (id INTEGER PRIMARY KEY AUTOINCREMENT, subkegiatan_id INTEGER NOT NULL, uraian TEXT, created_at TEXT NOT NULL)");
  $pdo->exec("CREATE TABLE IF NOT EXISTS berkas (id INTEGER PRIMARY KEY AUTOINCREMENT, harian_id INTEGER NOT NULL, subkegiatan_id INTEGER NOT NULL, original_name TEXT NOT NULL, stored_name TEXT NOT NULL, mime TEXT, size INTEGER NOT NULL, created_at TEXT NOT NULL)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_keg_user ON kegiatan(user_id)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_sub_keg ON subkegiatan(kegiatan_id)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_subsub_sub ON subsub(subkegiatan_id)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_har_sub ON harian(subkegiatan_id)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_ber_har ON berkas(harian_id)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_ber_sub ON berkas(subkegiatan_id)");
} else {
  $pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(60) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    nama VARCHAR(120) NULL,
    created_at DATETIME NOT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  $pdo->exec("CREATE TABLE IF NOT EXISTS kegiatan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nama VARCHAR(255) NOT NULL,
    bulan CHAR(7) NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX(user_id), INDEX(bulan)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  $pdo->exec("CREATE TABLE IF NOT EXISTS subkegiatan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kegiatan_id INT NOT NULL,
    nama VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX(kegiatan_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  $pdo->exec("CREATE TABLE IF NOT EXISTS subsub (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subkegiatan_id INT NOT NULL,
    nama VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX(subkegiatan_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  $pdo->exec("CREATE TABLE IF NOT EXISTS harian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subkegiatan_id INT NOT NULL,
    uraian TEXT NULL,
    created_at DATETIME NOT NULL,
    INDEX(subkegiatan_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  $pdo->exec("CREATE TABLE IF NOT EXISTS berkas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    harian_id INT NOT NULL,
    subkegiatan_id INT NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    mime VARCHAR(150) NULL,
    size INT NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX(harian_id), INDEX(subkegiatan_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// ---- Tabel berkas Ekin resmi (ekin tahunan / ekin bulanan, terpisah dari bukti) ----
if ($IS_SQLITE) {
  $pdo->exec("CREATE TABLE IF NOT EXISTS ekinfile (id INTEGER PRIMARY KEY AUTOINCREMENT, kegiatan_id INTEGER NOT NULL, bulan_ke INTEGER DEFAULT 0, original_name TEXT NOT NULL, stored_name TEXT NOT NULL, mime TEXT, size INTEGER NOT NULL, created_at TEXT NOT NULL)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_ekin_keg ON ekinfile(kegiatan_id)");
  // target bulanan per kegiatan (definisi tahunan) per bulan
  $pdo->exec("CREATE TABLE IF NOT EXISTS kegbulan (id INTEGER PRIMARY KEY AUTOINCREMENT, subkegiatan_id INTEGER NOT NULL, bulan_ke INTEGER NOT NULL, target INTEGER DEFAULT 0, created_at TEXT NOT NULL)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_kegbulan ON kegbulan(subkegiatan_id,bulan_ke)");
  // Aspek + Indikator Kinerja Individu, per Rencana Aksi (subsub)
  $pdo->exec("CREATE TABLE IF NOT EXISTS aspekiki (id INTEGER PRIMARY KEY AUTOINCREMENT, subsub_id INTEGER NOT NULL, aspek TEXT DEFAULT 'kuantitas', iki TEXT, created_at TEXT NOT NULL)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_aspekiki ON aspekiki(subsub_id)");
  $pdo->exec("CREATE TABLE IF NOT EXISTS appmeta (k TEXT PRIMARY KEY, v TEXT)");
  $pdo->exec("CREATE TABLE IF NOT EXISTS rhkskip (id INTEGER PRIMARY KEY AUTOINCREMENT, rhk_id INTEGER NOT NULL, bulan_ke INTEGER NOT NULL)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_rhkskip ON rhkskip(rhk_id,bulan_ke)");
  $pdo->exec("CREATE TABLE IF NOT EXISTS logingagal (k TEXT PRIMARY KEY, gagal INTEGER DEFAULT 0, sampai TEXT)");
  // (Indeks tambahan dibuat SETELAH kolom-kolomnya ditambahkan lewat ALTER di
  //  bawah — kalau dibuat di sini, database BARU error karena kolomnya belum ada.)
} else {
  $pdo->exec("CREATE TABLE IF NOT EXISTS ekinfile (id INT AUTO_INCREMENT PRIMARY KEY, kegiatan_id INT NOT NULL, bulan_ke INT DEFAULT 0, original_name VARCHAR(255) NOT NULL, stored_name VARCHAR(255) NOT NULL, mime VARCHAR(150) NULL, size INT NOT NULL, created_at DATETIME NOT NULL, INDEX(kegiatan_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  $pdo->exec("CREATE TABLE IF NOT EXISTS kegbulan (id INT AUTO_INCREMENT PRIMARY KEY, subkegiatan_id INT NOT NULL, bulan_ke INT NOT NULL, target INT DEFAULT 0, created_at DATETIME NOT NULL, INDEX(subkegiatan_id,bulan_ke)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  $pdo->exec("CREATE TABLE IF NOT EXISTS aspekiki (id INT AUTO_INCREMENT PRIMARY KEY, subsub_id INT NOT NULL, aspek VARCHAR(12) DEFAULT 'kuantitas', iki TEXT NULL, created_at DATETIME NOT NULL, INDEX(subsub_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  $pdo->exec("CREATE TABLE IF NOT EXISTS appmeta (k VARCHAR(40) PRIMARY KEY, v TEXT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  $pdo->exec("CREATE TABLE IF NOT EXISTS rhkskip (id INT AUTO_INCREMENT PRIMARY KEY, rhk_id INT NOT NULL, bulan_ke INT NOT NULL, INDEX(rhk_id,bulan_ke)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  $pdo->exec("CREATE TABLE IF NOT EXISTS logingagal (k VARCHAR(120) PRIMARY KEY, gagal INT DEFAULT 0, sampai DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  // (Indeks tambahan dibuat SETELAH kolom-kolomnya ditambahkan lewat ALTER di bawah.)
}

// ---- Kolom tambahan (upgrade dari versi lama; diabaikan bila sudah ada) ----
$__alter = function($sql) use ($pdo){ try{ $pdo->exec($sql); }catch(Throwable $e){} };
if ($IS_SQLITE) {
  $__alter("ALTER TABLE users ADD COLUMN recovery_hash TEXT");
  $__alter("ALTER TABLE subkegiatan ADD COLUMN target INTEGER DEFAULT 0");
  $__alter("ALTER TABLE subkegiatan ADD COLUMN satuan TEXT");
  $__alter("ALTER TABLE harian ADD COLUMN jumlah INTEGER DEFAULT 0");
  $__alter("ALTER TABLE kegiatan ADD COLUMN tipe TEXT DEFAULT 'bulan'");
  $__alter("ALTER TABLE kegiatan ADD COLUMN tahun INTEGER");
  $__alter("ALTER TABLE subkegiatan ADD COLUMN bulan_ke INTEGER DEFAULT 0");
  $__alter("ALTER TABLE harian ADD COLUMN bulan_ke INTEGER DEFAULT 0");
  $__alter("ALTER TABLE harian ADD COLUMN subsub_id INTEGER DEFAULT 0");
  $__alter("ALTER TABLE subsub ADD COLUMN bulan_ke INTEGER DEFAULT 0");
  $__alter("ALTER TABLE subsub ADD COLUMN target INTEGER DEFAULT 0");
  $__alter("ALTER TABLE subsub ADD COLUMN satuan TEXT");
  $__alter("ALTER TABLE ekinfile ADD COLUMN sub_id INTEGER DEFAULT 0");
  $__alter("ALTER TABLE subkegiatan ADD COLUMN pimpinan TEXT");
  $__alter("ALTER TABLE subkegiatan ADD COLUMN kategori TEXT DEFAULT 'utama'");
  $__alter("ALTER TABLE subsub ADD COLUMN aspek TEXT DEFAULT 'kuantitas'");
  $__alter("ALTER TABLE kegbulan ADD COLUMN subsub_id INTEGER DEFAULT 0");
  $__alter("ALTER TABLE kegbulan ADD COLUMN rencana_aksi TEXT");
  $__alter("ALTER TABLE ekinfile ADD COLUMN rhk_id INTEGER DEFAULT 0");
  $__alter("ALTER TABLE ekinfile ADD COLUMN raksi_id INTEGER DEFAULT 0");
  $__alter("ALTER TABLE aspekiki ADD COLUMN rhk_id INTEGER DEFAULT 0");
  $__alter("ALTER TABLE aspekiki ADD COLUMN target INTEGER DEFAULT 0");
} else {
  $__alter("ALTER TABLE users ADD COLUMN recovery_hash VARCHAR(255) NULL");
  $__alter("ALTER TABLE subkegiatan ADD COLUMN target INT DEFAULT 0");
  $__alter("ALTER TABLE subkegiatan ADD COLUMN satuan VARCHAR(40) NULL");
  $__alter("ALTER TABLE harian ADD COLUMN jumlah INT DEFAULT 0");
  $__alter("ALTER TABLE kegiatan ADD COLUMN tipe VARCHAR(10) DEFAULT 'bulan'");
  $__alter("ALTER TABLE kegiatan ADD COLUMN tahun INT NULL");
  $__alter("ALTER TABLE subkegiatan ADD COLUMN bulan_ke INT DEFAULT 0");
  $__alter("ALTER TABLE harian ADD COLUMN bulan_ke INT DEFAULT 0");
  $__alter("ALTER TABLE harian ADD COLUMN subsub_id INT DEFAULT 0");
  $__alter("ALTER TABLE subsub ADD COLUMN bulan_ke INT DEFAULT 0");
  $__alter("ALTER TABLE subsub ADD COLUMN target INT DEFAULT 0");
  $__alter("ALTER TABLE subsub ADD COLUMN satuan VARCHAR(40) NULL");
  $__alter("ALTER TABLE ekinfile ADD COLUMN sub_id INT DEFAULT 0");
  $__alter("ALTER TABLE subkegiatan ADD COLUMN pimpinan TEXT NULL");
  $__alter("ALTER TABLE subkegiatan ADD COLUMN kategori VARCHAR(12) DEFAULT 'utama'");
  $__alter("ALTER TABLE subsub ADD COLUMN aspek VARCHAR(12) DEFAULT 'kuantitas'");
  $__alter("ALTER TABLE kegbulan ADD COLUMN subsub_id INT DEFAULT 0");
  $__alter("ALTER TABLE kegbulan ADD COLUMN rencana_aksi TEXT NULL");
  $__alter("ALTER TABLE ekinfile ADD COLUMN rhk_id INT DEFAULT 0");
  $__alter("ALTER TABLE ekinfile ADD COLUMN raksi_id INT DEFAULT 0");
  $__alter("ALTER TABLE aspekiki ADD COLUMN rhk_id INT DEFAULT 0");
  $__alter("ALTER TABLE aspekiki ADD COLUMN target INT DEFAULT 0");
}

/* ---- Indeks tambahan (dipercepat) ----
   Dibuat DI SINI, sesudah semua kolom di atas ditambahkan lewat ALTER, supaya
   aman di database baru (kolom sudah pasti ada) maupun lama. Dibungkus try/catch
   agar tidak pernah menggagalkan pemuatan halaman. */
$__idx = [
  ['idx_har_subsub',   'harian',      'subsub_id'],
  ['idx_har_bulan',    'harian',      'subkegiatan_id,bulan_ke'],
  ['idx_aspek_rhk',    'aspekiki',    'rhk_id'],
  ['idx_subsub_bulan', 'subsub',      'subkegiatan_id,bulan_ke'],
  ['idx_sub_kegbulan', 'subkegiatan', 'kegiatan_id,bulan_ke'],
  ['idx_ekin_bulan',   'ekinfile',    'kegiatan_id,bulan_ke'],
  ['idx_ekin_raksi',   'ekinfile',    'rhk_id,raksi_id'],
];
foreach ($__idx as $ix) {
  $sqlIdx = $IS_SQLITE
    ? "CREATE INDEX IF NOT EXISTS {$ix[0]} ON {$ix[1]}({$ix[2]})"
    : "CREATE INDEX {$ix[0]} ON {$ix[1]}({$ix[2]})";
  try { $pdo->exec($sqlIdx); } catch (Throwable $e) {}
}

/* ---- Reset SEKALI ke model SKP v6 (RHK > Rencana Aksi > Aspek/IKI + Realisasi) ----
   Sesuai keputusan "mulai bersih": hapus data hierarki lama + berkas fisiknya satu kali.
   Dijaga marker appmeta agar tidak berulang. */
try {
  $done = $pdo->query("SELECT v FROM appmeta WHERE k='reset_skpv6'")->fetchColumn();
  if ($done === false) {
    $storeTmp = (defined('STORAGE_DIR') && STORAGE_DIR !== '') ? rtrim(STORAGE_DIR, "/\\") : __DIR__ . '/uploads';
    foreach (['berkas','ekinfile'] as $tb) {
      try { foreach ($pdo->query("SELECT stored_name FROM $tb") as $r) { $p = $storeTmp.'/'.basename($r['stored_name']); if (is_file($p)) @unlink($p); } } catch (Throwable $e) {}
    }
    foreach (['berkas','harian','aspekiki','kegbulan','subsub','subkegiatan','ekinfile','kegiatan'] as $tb) {
      try { $pdo->exec("DELETE FROM $tb"); } catch (Throwable $e) {}
    }
    $pdo->prepare("INSERT INTO appmeta (k,v) VALUES ('reset_skpv6',?)")->execute([date('Y-m-d H:i:s')]);
  }
} catch (Throwable $e) { /* abaikan */ }

// Tandai skema sudah mutakhir supaya permintaan berikutnya melewati blok ini
try {
  $pdo->prepare("INSERT INTO appmeta (k,v) VALUES ('skema_versi',?)")->execute([SKEMA_VERSI]);
} catch (Throwable $e) {
  try { $pdo->prepare("UPDATE appmeta SET v=? WHERE k='skema_versi'")->execute([SKEMA_VERSI]); } catch (Throwable $e2) {}
}
} // akhir $__perluMigrasi

// ---- Folder upload (bisa diatur lewat STORAGE_DIR di config) ----
$__store = (defined('STORAGE_DIR') && STORAGE_DIR !== '') ? rtrim(STORAGE_DIR, "/\\") : __DIR__ . '/uploads';
define('UPLOAD_DIR', $__store);
if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0755, true);
// Jaga folder upload agar tidak bisa diakses langsung dari browser (berlaku bila folder
// berada di dalam web root; kalau di luar web root, otomatis sudah tidak bisa diakses).
$htUp = UPLOAD_DIR . '/.htaccess';
if (is_dir(UPLOAD_DIR) && is_writable(UPLOAD_DIR) && !file_exists($htUp)) {
  @file_put_contents($htUp, "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
}
if (!is_dir(UPLOAD_DIR)) {
  http_response_code(500);
  die('<h2>Folder penyimpanan tidak bisa dibuat.</h2><p>Periksa <b>STORAGE_DIR</b> di config.php: <code>' . htmlspecialchars(UPLOAD_DIR) . '</code></p>');
}

// ---- Fungsi bantu ----
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function redirect($u){ header("Location: $u"); exit; }
function currentUserId(){ return $_SESSION['uid'] ?? null; }
function currentUserName(){ return $_SESSION['uname'] ?? ''; }

function userCount(PDO $pdo){ return (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(); }

function requireLogin(){
  if (!currentUserId()) redirect('login.php');
}
function requireLoginJson(){
  if (!currentUserId()) { http_response_code(401); header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>'Belum login']); exit; }
}

function csrf_token(){
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
  return $_SESSION['csrf'];
}
function csrf_check(){
  $t = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF'] ?? '');
  if (!$t || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $t)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['ok'=>false,'error'=>'Token keamanan tidak valid. Muat ulang halaman.']);
    exit;
  }
}
/* Cek CSRF untuk halaman HTML biasa (login/lupa/setup) — tidak balas JSON */
function csrf_ok_form(){
  $t = $_POST['csrf'] ?? '';
  return $t && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
}

/* ============ KEAMANAN BERKAS ============
   Nama simpan di server SELALU dibuat acak dan berakhiran .bin, jadi tidak ada
   berkas berakhiran .php/.html/.svg di folder uploads yang bisa dieksekusi atau
   dirender browser walau folder itu sempat bisa diakses langsung (mis. server
   Nginx yang mengabaikan .htaccess). Nama asli tetap disimpan di database dan
   dipakai saat diunduh. */
function namaSimpanAman(){
  return bin2hex(random_bytes(16)) . '.bin';
}

/* Tipe yang aman dibuka langsung di dalam browser (inline). Selain ini dipaksa
   diunduh, supaya berkas HTML/SVG/skrip tidak pernah dijalankan di domain kita. */
function mimeBolehInline($mime){
  $m = strtolower(trim((string)$mime));
  if ($m === '') return false;
  $aman = [
    'image/jpeg','image/png','image/gif','image/webp','image/bmp','image/x-icon',
    'application/pdf','text/plain','audio/mpeg','audio/ogg','audio/wav','video/mp4','video/webm',
  ];
  return in_array($m, $aman, true);
}

/* Batasi Content-Type yang dikirim ulang ke browser. Nilai aneh/berbahaya
   (text/html, image/svg+xml, application/xhtml+xml, dll) diturunkan jadi biner. */
function mimeKirimAman($mime){
  $m = strtolower(trim((string)$mime));
  if (!preg_match('#^[a-z0-9][a-z0-9!\#$&^_.+-]{0,80}/[a-z0-9][a-z0-9!\#$&^_.+-]{0,80}$#', $m)) return 'application/octet-stream';
  return mimeBolehInline($m) ? $m : 'application/octet-stream';
}

/* Header keamanan dasar untuk halaman HTML */
function headerAman(){
  if (headers_sent()) return;
  header('X-Content-Type-Options: nosniff');
  header('X-Frame-Options: SAMEORIGIN');
  header('Referrer-Policy: same-origin');
  header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

/* ============ REM PERCOBAAN MASUK (anti tebak password) ============ */
function _limitKey($tag){
  $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
  return $tag . '|' . $ip;
}
/* Kembalikan sisa detik terkunci (0 = boleh mencoba) */
function limitSisaDetik(PDO $pdo, $tag, $maxGagal = 8, $jendelaMenit = 15){
  try {
    $s = $pdo->prepare("SELECT gagal, sampai FROM logingagal WHERE k=?");
    $s->execute([_limitKey($tag)]);
    $r = $s->fetch();
    if (!$r) return 0;
    if ((int)$r['gagal'] >= $maxGagal && strtotime($r['sampai']) > time()) return strtotime($r['sampai']) - time();
    return 0;
  } catch (Throwable $e) { return 0; }
}
function limitCatatGagal(PDO $pdo, $tag, $jendelaMenit = 15){
  try {
    $k = _limitKey($tag);
    $s = $pdo->prepare("SELECT gagal, sampai FROM logingagal WHERE k=?");
    $s->execute([$k]); $r = $s->fetch();
    $sampai = date('Y-m-d H:i:s', time() + $jendelaMenit * 60);
    if (!$r) {
      $pdo->prepare("INSERT INTO logingagal (k,gagal,sampai) VALUES (?,1,?)")->execute([$k, $sampai]);
    } else {
      $gagal = (strtotime($r['sampai']) > time()) ? ((int)$r['gagal'] + 1) : 1;
      $pdo->prepare("UPDATE logingagal SET gagal=?, sampai=? WHERE k=?")->execute([$gagal, $sampai, $k]);
    }
  } catch (Throwable $e) {}
}
function limitBersih(PDO $pdo, $tag){
  try { $pdo->prepare("DELETE FROM logingagal WHERE k=?")->execute([_limitKey($tag)]); } catch (Throwable $e) {}
}
