# Panduan e-Kinerja

Aplikasi ini menyimpan data di server, sehingga bisa diakses dari **HP maupun laptop** dengan data yang sama, dilindungi **login**.

- **Mau coba dulu di komputer sendiri?** → baca **BAGIAN 1** (mode lokal, tanpa install database).
- **Mau pasang di hosting/cPanel?** → baca **BAGIAN 2**.

---

# BAGIAN 1 — Coba di Lokal (paling cepat)

Butuh **PHP** terpasang di komputer (kamu sudah punya). Tidak perlu MySQL.

1. Pastikan di `config.php` baris ini berbunyi: `define('DB_DRIVER', 'sqlite');` (ini bawaannya).
2. **Klik dua kali file `JALANKAN-LOKAL.bat`.**
   Akan muncul jendela hitam, dan browser otomatis membuka `http://127.0.0.1:8000`.
3. Karena belum ada akun, kamu diarahkan ke **Buat Akun** — isi nama, username, password.
4. Selesai — langsung bisa dipakai. Data tersimpan di file `data/ekin.db`.
5. Untuk berhenti: tutup jendela hitam itu.

> Catatan: mode lokal hanya bisa dibuka di komputer itu saja. Untuk akses dari HP,
> lanjut ke BAGIAN 2 (pasang di hosting).

---

# Lokasi Simpan Berkas (opsional)

Secara bawaan, semua berkas bukti tersimpan **otomatis** di folder `uploads/` di dalam
aplikasi. Kamu bisa mengubahnya lewat `STORAGE_DIR` di **`config.php`**:

- Biarkan kosong `''` → **otomatis** (folder aplikasi). Paling simpel & andal.
- Isi path folder lain, mis. **folder Google Drive yang tersinkron di PC** (khusus mode lokal),
  agar berkas otomatis ikut naik ke Google Drive:
  ```php
  define('STORAGE_DIR', 'C:/Users/NAMA/My Drive/EKIN-Berkas');
  ```
  Syaratnya: aplikasi dijalankan di PC itu, dan Google Drive desktop aktif menyinkron folder tsb.

> Menaruh berkas ke Google Drive saat **di-hosting online** butuh mode Google Drive API
> (perlu Google Cloud + OAuth) — belum aktif; bisa disiapkan menyusul.

---

# BAGIAN 2 — Pasang di Hosting (cPanel)

## A. Siapkan Database (cPanel)

1. Login ke **cPanel** hosting kamu.
2. Buka menu **MySQL® Databases**.
3. **Create New Database** — misalnya beri nama `ekin`.
   → Nama lengkapnya nanti jadi seperti `namaakun_ekin`. **Catat.**
4. Di bagian **MySQL Users → Add New User** — buat user, misalnya `ekin`, beri password kuat.
   → Namanya jadi seperti `namaakun_ekin`. **Catat username & password-nya.**
5. Di bagian **Add User To Database** — pilih user + database tadi → **Add** →
   pada halaman hak akses, centang **ALL PRIVILEGES** → **Make Changes**.

## B. Isi config.php

Buka file **`config.php`**. **Ubah driver ke `mysql`**, lalu isi data database dari langkah A:

```php
define('DB_DRIVER', 'mysql');          // <-- ganti dari 'sqlite' ke 'mysql'
define('DB_HOST', 'localhost');
define('DB_NAME', 'namaakun_ekin');   // nama database
define('DB_USER', 'namaakun_ekin');   // username database
define('DB_PASS', 'password_database_kamu');
```

(Opsional) Ganti juga `APP_NAME` / `MAX_UPLOAD_MB` bila perlu.

## C. Upload File ke Subdomain

1. Di cPanel buka **Domains / Subdomains**, buat subdomain, mis. `ekin.domainkamu.com`.
   Catat **Document Root**-nya (mis. `public_html/ekin`).
2. Buka **File Manager** → masuk ke folder Document Root subdomain itu.
3. Upload **SEMUA isi folder `ekin-server`** ke situ (bukan foldernya, tapi isinya):
   `index.php`, `config.php`, `bootstrap.php`, `api.php`, `login.php`, `setup.php`,
   `logout.php`, `download.php`, `zip.php`, `rekap.php`, `auth_style.php`,
   `.htaccess`, `.user.ini`, dan folder `uploads`.
   > Tip: upload saja file `ekin-server.zip` lalu **Extract** di File Manager, kemudian
   > pindahkan isinya ke Document Root. Pastikan file diawali titik (`.htaccess`, `.user.ini`)
   > ikut ter-upload — aktifkan **Settings → Show Hidden Files (dotfiles)** di File Manager.

## D. Pertama Kali Dibuka

1. Buka `https://ekin.domainkamu.com` di browser.
2. Karena belum ada akun, kamu diarahkan ke halaman **Buat Akun** —
   isi nama, username, dan password. Ini jadi akun login kamu.
3. Selesai! Kamu masuk ke aplikasi. Tabel database dibuat otomatis.

---

## Cara Pakai

- **Menambah kegiatan:** isi Judul (wajib) → Keterangan (opsional) → tarik/klik untuk upload
  dokumen → **Simpan**. Tanggal & jam terisi **otomatis** (WIB).
- **Akhir bulan:** pada judul bulan, klik **📦 Download berkas (ZIP)** untuk mengunduh semua
  dokumen bulan itu (nama file otomatis diberi tanggal + judul) beserta file rekap.
- **Cetak laporan:** klik **📄 Cetak rekap** → tampil tabel siap dicetak / disimpan sebagai PDF.
- **Buka di HP:** buka alamat yang sama di browser HP, lalu menu browser → **Add to Home Screen**
  supaya muncul seperti ikon aplikasi.

## Keamanan & Catatan

- Dokumen disimpan di folder `uploads/` yang **diblokir dari akses langsung**; hanya bisa dibuka
  lewat aplikasi setelah login.
- Nama berkas di server **selalu diacak dan berakhiran `.bin`** — jadi tidak ada berkas
  `.php`/`.html`/`.svg` di folder unggahan yang bisa dijalankan server. Nama aslimu tetap
  tersimpan di database dan dipakai saat berkas diunduh.
- Berkas yang dibuka lewat `download.php` hanya ditampilkan langsung di browser kalau tipenya
  aman (gambar, PDF, teks polos, audio/video). Selain itu **dipaksa jadi unduhan**.
- Login dibatasi: **8 kali salah → terkunci 15 menit**; halaman *Lupa Password* **5 kali salah
  → terkunci 30 menit** (dihitung per alamat IP).
- Semua form memakai **token keamanan (CSRF)**, termasuk halaman Masuk dan Lupa Password.
- Pakai **HTTPS** (SSL). Di cPanel biasanya ada **AutoSSL** gratis — pastikan aktif untuk subdomain.
- Bila lupa password: pakai **kode pemulihan** di halaman *Lupa Password*. Kode baru bisa dibuat
  kapan saja lewat menu **Akun**.

### Kalau hosting-mu memakai Nginx (bukan Apache)

Nginx **tidak membaca `.htaccess`**. Supaya folder `uploads/` dan `data/` tetap tertutup,
minta hosting menambahkan ini ke konfigurasi situs:

```nginx
location ~ ^/(uploads|data)/ { deny all; }
location ~ /\.           { deny all; }
location ~* \.(log|db|sqlite|sqlite3|md|bat|ini|bak|sql)$ { deny all; }
```

Cara paling aman dan tidak tergantung jenis server: **pindahkan folder unggahan ke luar
web root** lewat `STORAGE_DIR` di `config.php` (lihat bagian *Lokasi Simpan Berkas*).

> Saat dijalankan lokal lewat `JALANKAN-LOKAL.bat`, penjagaan ini sudah ditangani
> oleh `router.php`, jadi `uploads/`, `data/ekin.db`, dan `config.php` tetap tertutup.

## Kalau Ada Kendala

- **"Koneksi database gagal"** → cek lagi `config.php` (nama db/user/password) dan pastikan
  user sudah di-*add to database* dengan ALL PRIVILEGES.
- **Fitur ZIP tidak jalan** → ekstensi `ZipArchive` belum aktif di hosting; hubungi hosting untuk
  mengaktifkan, atau sementara pakai tombol **Cetak rekap**.
- **File besar gagal upload** → naikkan `upload_max_filesize` & `post_max_size` di `.user.ini`
  (dan `MAX_UPLOAD_MB` di `config.php`), sesuai batas hosting.
