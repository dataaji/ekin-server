# e-Kinerja

Aplikasi pencatat kegiatan & kinerja harian ASN (model SKP), berbasis PHP.

- **SKP** (Tahunan/Bulanan) → **RHK** (Utama/Tambahan) → **Indikator** (Aspek + IKI + Target) → **Rencana Aksi** (per bulan) → **Realisasi** → **Bukti Dukung**
- Dukung dua database: **SQLite** (coba lokal) & **MySQL** (hosting) lewat `DB_DRIVER` di `config.php`.
- Login + kode pemulihan, CSRF, proteksi folder unggahan, unduh ZIP bukti.

## Pasang
1. Salin `config.example.php` → `config.php`, isi sesuai database.
2. Coba lokal: klik `JALANKAN-LOKAL.bat` (butuh PHP), buka http://127.0.0.1:8000.
3. Hosting: upload semua file ke folder domain, buka di browser → buat akun admin.

## Catatan
- `config.php`, `data/*.db`, dan `uploads/` **tidak** masuk ke Git (privat) — lihat `.gitignore`.
- Butuh ekstensi PHP: pdo, pdo_sqlite / pdo_mysql, zip, fileinfo, mbstring.
