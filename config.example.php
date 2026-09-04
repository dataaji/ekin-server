<?php
/* =====================================================================
   KONFIGURASI e-Kinerja
   Isi bagian di bawah ini sesuai data dari cPanel kamu.
   (cPanel > MySQL Databases: buat database, buat user, hubungkan)
   ===================================================================== */

// --- Pilih jenis database ---
//   'sqlite' = untuk COBA DI LOKAL (tanpa install apa pun, klik JALANKAN-LOKAL.bat)
//   'mysql'  = untuk DI HOSTING/cPanel (isi juga DB_* di bawah)
define('DB_DRIVER', 'sqlite');

// --- Database MySQL (hanya dipakai jika DB_DRIVER = 'mysql') ---
define('DB_HOST', 'localhost');          // Biasanya 'localhost'
define('DB_NAME', 'namauser_ekin');      // Nama database dari cPanel
define('DB_USER', 'namauser_ekin');      // Username database dari cPanel
define('DB_PASS', 'PASSWORD_DATABASE');  // Password database

// --- Zona waktu (biarkan Asia/Jakarta untuk WIB) ---
define('APP_TZ', 'Asia/Jakarta');

// --- Ukuran maksimal per file upload dalam MB ---
//     Sesuaikan dengan batas hosting kamu (lihat juga file .user.ini)
define('MAX_UPLOAD_MB', 25);

// =====================================================================
//  LOKASI SIMPAN BERKAS
//  Kosong ('')  = OTOMATIS di folder aplikasi (uploads/). Paling simpel.
//  Atau isi path folder lain. Contoh untuk menaruh ke Google Drive
//  (hanya kalau aplikasi dijalankan di PC yang ada Google Drive desktop,
//   sehingga folder itu otomatis tersinkron ke Drive):
//     Windows : 'C:/Users/NAMA/My Drive/EKIN-Berkas'
//     Linux   : '/home/namauser/ekin-berkas'
//  (Untuk simpan ke Google Drive saat di-HOSTING online, perlu mode API —
//   lihat GDRIVE_* di bawah; belum aktif.)
// =====================================================================
define('STORAGE_DIR', '');

// =====================================================================
//  LOKASI FILE DATABASE (khusus mode sqlite)
//  Kosong ('')  = data/ekin.db di dalam folder aplikasi (bawaan).
//
//  KECEPATAN: kalau folder aplikasi ini berada di dalam Google Drive /
//  OneDrive, setiap pembacaan database jadi ~30x lebih lambat dan
//  perpindahan menu terasa tersendat. Pindahkan berkas database ke disk
//  lokal untuk menghilangkannya — berkas bukti dukung boleh tetap di Drive.
//     Windows : 'C:/EkinData/ekin.db'
//     Linux   : '/home/namauser/ekin-data/ekin.db'
//  CARA PINDAH: matikan dulu aplikasinya, salin data/ekin.db ke lokasi
//  baru, lalu tulis path lengkapnya di bawah ini. Ingat: berkas di disk
//  lokal TIDAK ikut tersinkron/tercadang ke Drive — cadangkan sendiri
//  secara berkala.
// =====================================================================
define('DB_PATH', '');

// --- (Persiapan) Google Drive via API — untuk hosting online ---
//     Belum aktif. Diisi nanti saat mode Google Drive API disiapkan.
define('GDRIVE_ENABLED', false);

// --- Nama aplikasi (boleh diganti, mis. nama instansi) ---
define('APP_NAME', 'e-Kinerja');
define('APP_SUBTITLE', 'Catatan kegiatan & kinerja harian');
