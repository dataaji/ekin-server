<?php
/* Rekap 1 kegiatan (siap cetak / simpan PDF), dikelompokkan per sub kegiatan */
require __DIR__ . '/bootstrap.php';
requireLogin();
headerAman();
$uid=currentUserId();

$kid=(int)($_GET['kegiatan']??0);
$s=$pdo->prepare("SELECT * FROM kegiatan WHERE id=? AND user_id=?"); $s->execute([$kid,$uid]);
$keg=$s->fetch(); if(!$keg){ http_response_code(404); die('Kegiatan tidak ditemukan.'); }

$bulanNama=['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$bulanS=['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
$hariNama=['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
$isTahun=(($keg['tipe']??'bulan')==='tahun');
if($isTahun){ $labelBulan='Tahun '.(int)$keg['tahun']; }
else { [$yy,$mm]=explode('-',$keg['bulan']); $labelBulan=$bulanNama[(int)$mm].' '.$yy; }

$subs=$pdo->prepare("SELECT * FROM subkegiatan WHERE kegiatan_id=? ORDER BY bulan_ke, nama"); $subs->execute([$kid]); $subs=$subs->fetchAll();
$hstmt=$pdo->prepare("SELECT * FROM harian WHERE subkegiatan_id=? ORDER BY created_at, id");
$fstmt=$pdo->prepare("SELECT original_name FROM berkas WHERE harian_id=? ORDER BY id");
$totH=0;$totB=0;
?><!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Rekap <?= e($keg['nama']) ?> — <?= e($labelBulan) ?></title>
<style>
  body{font-family:'Segoe UI',system-ui,sans-serif;color:#111;padding:28px;max-width:940px;margin:auto}
  h1{font-size:20px;margin:0 0 2px}
  .sub{color:#555;margin-bottom:18px;font-size:13px}
  h2{font-size:15px;margin:20px 0 6px;padding:6px 10px;background:#ccfbf1;color:#0f766e;border-radius:6px}
  table{width:100%;border-collapse:collapse;font-size:13px;margin-bottom:6px}
  th,td{border:1px solid #cbd5e1;padding:7px 9px;vertical-align:top;text-align:left}
  th{background:#0d9488;color:#fff;font-size:12px}
  tr:nth-child(even) td{background:#f1f5f9}
  small{color:#64748b}
  .foot{margin-top:18px;font-size:12px;color:#555}
  .bar{margin-bottom:16px;display:flex;gap:8px}
  .btn{background:#0d9488;color:#fff;border:none;padding:10px 18px;border-radius:8px;font-weight:700;cursor:pointer;text-decoration:none;font-size:14px}
  .btn.grey{background:#64748b}
  .empty{color:#94a3b8;font-style:italic;margin:4px 0 10px}
  @media print{.bar{display:none}}
</style></head><body>
  <div class="bar">
    <button class="btn" onclick="window.print()">🖨️ Cetak / Simpan PDF</button>
    <a class="btn grey" href="index.php">← Kembali</a>
  </div>
  <h1>Rekap Kegiatan: <?= e($keg['nama']) ?></h1>
  <div class="sub"><?= $isTahun?'Periode':'Bulan' ?>: <b><?= e($labelBulan) ?></b> · Nama: <b><?= e(currentUserName()) ?></b></div>

  <?php if(!$subs): ?>
    <p class="empty">Belum ada sub kegiatan.</p>
  <?php else: $curBk=-1; foreach($subs as $sub):
      $hstmt->execute([$sub['id']]); $rows=$hstmt->fetchAll();
      if($isTahun && (int)($sub['bulan_ke']??0)!==$curBk){ $curBk=(int)($sub['bulan_ke']??0);
        echo '<h2 style="background:#0d9488;color:#fff">📅 '.e($curBk>=1&&$curBk<=12?$bulanNama[$curBk]:'Tanpa bulan').'</h2>'; } ?>
    <h2>📂 <?= e($sub['nama']) ?></h2>
    <?php if(!$rows): ?>
      <div class="empty">Belum ada catatan harian.</div>
    <?php else: ?>
      <table>
        <thead><tr><th style="width:34px">No</th><th style="width:150px">Tanggal &amp; Jam</th><th>Uraian</th><th style="width:200px">Berkas</th></tr></thead>
        <tbody>
        <?php $no=0; foreach($rows as $h): $no++;$totH++; $ts=strtotime($h['created_at']);
          $fstmt->execute([$h['id']]); $files=array_column($fstmt->fetchAll(),'original_name'); $totB+=count($files); ?>
          <tr>
            <td style="text-align:center"><?= $no ?></td>
            <td><?= $hariNama[(int)date('w',$ts)] ?>, <?= (int)date('j',$ts) ?> <?= $bulanS[(int)date('n',$ts)] ?> <?= date('Y',$ts) ?><br><small><?= date('H:i',$ts) ?> WIB</small></td>
            <td><?= $h['uraian']!==''&&$h['uraian']!==null ? nl2br(e($h['uraian'])) : '<span style="color:#94a3b8">-</span>' ?></td>
            <td><?= $files ? e(implode(', ',$files)) : '-' ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  <?php endforeach; endif; ?>

  <div class="foot">Total <?= count($subs) ?> sub kegiatan · <?= $totH ?> catatan harian · <?= $totB ?> berkas · Dicetak <?= date('d/m/Y H:i') ?> WIB — <?= e(APP_NAME) ?></div>
</body></html>
