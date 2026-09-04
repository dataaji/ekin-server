@echo off
title e-Kinerja (Lokal)
cd /d "%~dp0"
set PORT=8000
set PHP=

rem --- Cari PHP ---
where php >nul 2>nul && set PHP=php
if not defined PHP if exist "C:\xampp\php\php.exe" set "PHP=C:\xampp\php\php.exe"
if not defined PHP if exist "C:\wamp64\bin\php\php.exe" set "PHP=C:\wamp64\bin\php\php.exe"
if not defined PHP for /d %%D in ("C:\laragon\bin\php\php-*") do if exist "%%D\php.exe" set "PHP=%%D\php.exe"

if not defined PHP (
  echo.
  echo  [!] PHP tidak ditemukan di komputer ini.
  echo      Pasang PHP dulu ^(mis. lewat XAMPP: https://www.apachefriends.org^),
  echo      lalu jalankan file ini lagi.
  echo.
  pause
  exit /b
)

echo.
echo  ============================================
echo    e-Kinerja berjalan di komputermu (lokal)
echo    Buka:  http://127.0.0.1:%PORT%
echo.
echo    Biarkan jendela hitam ini TETAP TERBUKA.
echo    Untuk berhenti: tutup jendela ini / tekan Ctrl+C.
echo  ============================================
echo.

start "" "http://127.0.0.1:%PORT%"
rem Server bawaan PHP tidak membaca .user.ini, jadi batas unggah diberikan di sini.
rem Tanpa ini batas asli cuma 2 MB dan berkas PDF besar dibuang diam-diam.
"%PHP%" -S 127.0.0.1:%PORT% -d upload_max_filesize=25M -d post_max_size=60M -d max_file_uploads=20 -d memory_limit=256M -d max_execution_time=120 router.php
