<?php
/* PEMERIKSA DATABASE — tunjukkan aplikasi sedang menyambung ke mana & isinya.
   Buka: https://ekinerja.vortatech.site/cek-db.php  — HAPUS setelah selesai. */
require __DIR__ . '/bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');

echo "==== PEMERIKSA DATABASE e-Kinerja ====\n\n";
echo "DB_DRIVER (di config.php) : " . (defined('DB_DRIVER') ? DB_DRIVER : '(tidak ada)') . "\n";

try {
  if (defined('DB_DRIVER') && DB_DRIVER === 'mysql') {
    echo "DB_NAME (di config.php)   : " . (defined('DB_NAME') ? DB_NAME : '-') . "\n";
    echo "DB_USER (di config.php)   : " . (defined('DB_USER') ? DB_USER : '-') . "\n";
    echo "Benar-benar terhubung ke  : " . $pdo->query("SELECT DATABASE()")->fetchColumn() . "\n";
    $tabel = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
  } else {
    $f = (defined('DB_PATH') && DB_PATH !== '') ? DB_PATH : __DIR__ . '/data/ekin.db';
    echo "File SQLite yang dipakai  : " . $f . "\n";
    echo "  (kalau ini yang muncul, berarti config.php MASIH mode sqlite,\n";
    echo "   perubahan ke mysql belum tersimpan / belum ke-upload.)\n";
    $tabel = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
  }

  echo "\nJumlah tabel : " . count($tabel) . "\n";
  echo "Daftar tabel : " . (count($tabel) ? implode(', ', $tabel) : '(kosong)') . "\n";

  echo "\nJumlah akun (users) : " . userCount($pdo) . "\n";
  $rows = $pdo->query("SELECT id, username, nama FROM users")->fetchAll();
  foreach ($rows as $r) echo "  - #{$r['id']}  username: {$r['username']}   nama: {$r['nama']}\n";

} catch (Throwable $e) {
  echo "\nERROR: " . $e->getMessage() . "\n";
}
echo "\n==== selesai — hapus file ini setelah dibaca ====\n";
