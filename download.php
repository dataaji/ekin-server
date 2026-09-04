<?php
/* Kirim 1 file ke browser (hanya milik user yang login) */
require __DIR__ . '/bootstrap.php';
requireLogin();

$dl  = isset($_GET['dl']);   // ?dl=1 -> paksa unduh, tanpa -> tampil di browser

if (isset($_GET['ekin'])) {
  $id = (int)$_GET['ekin'];
  $stmt = $pdo->prepare(
    "SELECT ef.* FROM ekinfile ef JOIN kegiatan k ON k.id = ef.kegiatan_id
     WHERE ef.id = ? AND k.user_id = ? LIMIT 1"
  );
} else {
  $id = (int)($_GET['id'] ?? 0);
  $stmt = $pdo->prepare(
    "SELECT b.* FROM berkas b
     JOIN subkegiatan sk ON sk.id = b.subkegiatan_id
     JOIN kegiatan k ON k.id = sk.kegiatan_id
     WHERE b.id = ? AND k.user_id = ? LIMIT 1"
  );
}
$stmt->execute([$id, currentUserId()]);
$f = $stmt->fetch();
if (!$f) { http_response_code(404); die('File tidak ditemukan.'); }

$path = UPLOAD_DIR . '/' . basename($f['stored_name']);
if (!is_file($path)) { http_response_code(404); die('Berkas hilang dari server.'); }

/* Content-Type disaring: hanya tipe aman (gambar/PDF/teks polos/media) yang boleh
   dikirim apa adanya. Tipe lain — termasuk HTML, SVG, dan skrip — diturunkan jadi
   biner DAN dipaksa diunduh, supaya berkas orang lain tidak pernah bisa
   dijalankan sebagai halaman di dalam domain aplikasi ini. */
$mime = mimeKirimAman($f['mime']);
$disp = ($dl || $mime === 'application/octet-stream') ? 'attachment' : 'inline';
$name = preg_replace('/["\r\n]/', '', $f['original_name']);

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header("Content-Disposition: $disp; filename=\"$name\"; filename*=UTF-8''" . rawurlencode($f['original_name']));
header('X-Content-Type-Options: nosniff');
header('Content-Security-Policy: default-src \'none\'; img-src \'self\' data:; media-src \'self\'; object-src \'none\'; sandbox');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');
header('Cache-Control: private, max-age=0, must-revalidate');
readfile($path);
exit;
