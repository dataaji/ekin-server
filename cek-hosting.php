<?php
/* =====================================================================
   CEK KESIAPAN HOSTING untuk e-Kinerja.
   Buka file ini lewat browser (mis. https://ekin.vortatech.site/cek-hosting.php).
   Kalau semua HIJAU -> lanjut pasang aplikasi. HAPUS file ini setelah selesai.
   ===================================================================== */
header('Content-Type: text/html; charset=utf-8');
$ok = true;
function baris($nama, $lolos, $detail) {
  global $ok; if (!$lolos) $ok = false;
  $warna = $lolos ? '#16a34a' : '#dc2626';
  $ikon  = $lolos ? '&#10003;' : '&#10007;';
  echo "<tr><td style='padding:8px 14px;border-bottom:1px solid #eee'>$nama</td>"
     . "<td style='padding:8px 14px;border-bottom:1px solid #eee;color:$warna;font-weight:700'>$ikon</td>"
     . "<td style='padding:8px 14px;border-bottom:1px solid #eee;color:#555'>$detail</td></tr>";
}

$phpOK = version_compare(PHP_VERSION, '7.4.0', '>=');

echo "<div style='font-family:system-ui,Segoe UI,sans-serif;max-width:720px;margin:24px auto'>";
echo "<h2>Cek Kesiapan Hosting &mdash; e-Kinerja</h2>";
echo "<table style='border-collapse:collapse;width:100%;font-size:14px'>";

baris('Versi PHP (minimal 7.4)', $phpOK, 'Terpasang: ' . PHP_VERSION);
baris('Ekstensi PDO', extension_loaded('pdo'), 'untuk koneksi database');
baris('PDO SQLite (mode lokal/sederhana)', extension_loaded('pdo_sqlite'), extension_loaded('pdo_sqlite') ? 'tersedia' : 'tidak ada (pakai MySQL)');
baris('PDO MySQL (mode hosting)', extension_loaded('pdo_mysql'), extension_loaded('pdo_mysql') ? 'tersedia' : 'tidak ada');
baris('Ekstensi ZipArchive', class_exists('ZipArchive'), 'untuk unduh banyak bukti sekaligus (.zip)');
baris('Ekstensi fileinfo', function_exists('finfo_open'), 'untuk mendeteksi jenis berkas');
baris('Ekstensi mbstring', function_exists('mb_substr'), 'untuk teks Indonesia yang aman');
baris('Folder bisa ditulis', is_writable(__DIR__), 'lokasi: ' . __DIR__);

$maxUp = ini_get('upload_max_filesize'); $maxPost = ini_get('post_max_size');
baris('Batas unggah berkas', true, "upload_max_filesize = $maxUp &nbsp;|&nbsp; post_max_size = $maxPost");

echo "</table>";
echo $ok
  ? "<p style='margin-top:18px;padding:12px 16px;background:#dcfce7;border-radius:8px;color:#166534'><b>SEMUA SIAP.</b> Hosting ini bisa menjalankan e-Kinerja. Lanjut ke langkah berikutnya, lalu <b>hapus file cek-hosting.php ini</b>.</p>"
  : "<p style='margin-top:18px;padding:12px 16px;background:#fee2e2;border-radius:8px;color:#991b1b'><b>ADA YANG BELUM SIAP.</b> Tunjukkan halaman ini ke saya sebelum lanjut &mdash; ada bagian merah yang perlu diaktifkan dulu di cPanel (mis. lewat <i>Select PHP Version &rarr; Extensions</i>).</p>";
echo "<p style='color:#888;font-size:12px'>Setelah dipakai, hapus file ini demi keamanan.</p>";
echo "</div>";
