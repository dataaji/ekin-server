<?php
/* Unduh berkas sebagai ZIP.
   ?kegiatan=ID    -> seluruh kegiatan: 1 folder per sub kegiatan (berkas harian digabung)
   ?subkegiatan=ID -> hanya folder 1 sub kegiatan                                        */
require __DIR__ . '/bootstrap.php';
requireLogin();
$uid = currentUserId();

$bulanNama = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$hariNama  = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
function labelBulan($b){ global $bulanNama; if(!preg_match('/^\d{4}-\d{2}$/',$b)) return ''; [$y,$m]=explode('-',$b); return $bulanNama[(int)$m].' '.$y; }
function labelPeriode($keg){ if(($keg['tipe']??'bulan')==='tahun') return 'Tahun '.(int)$keg['tahun']; $l=labelBulan($keg['bulan']??''); return $l?:''; }
/* Bersihkan nama berkas: buang pemisah folder, karakter terlarang Windows,
   karakter kendali, dan titik di depan (agar tidak jadi "..") */
$sanit = function($s){
  $s = preg_replace('/[\\\\\/:*?"<>|\x00-\x1F]+/','-', trim((string)$s));
  $s = ltrim($s, '. ');
  return $s !== '' ? mb_substr($s,0,180) : 'berkas';
};

/* Kirim 1 berkas apa adanya (tanpa ZIP). Selalu sebagai unduhan biner —
   tidak pernah dirender browser, apa pun isi berkasnya. */
function kirimSatuBerkas($path,$namaAsli,$mime=null){
  if(!is_file($path)) { http_response_code(404); die('Berkas tidak ditemukan.'); }
  $nama = preg_replace('/[\\\\\/:*?"<>|\x00-\x1F]+/','-', (string)$namaAsli);
  $nama = ltrim($nama, '. '); if($nama==='') $nama='berkas';
  header('Content-Type: application/octet-stream');
  header('Content-Length: '.filesize($path));
  header('Content-Disposition: attachment; filename="'.$nama.'"; filename*=UTF-8\'\''.rawurlencode($nama));
  header('X-Content-Type-Options: nosniff');
  header('Cache-Control: private, max-age=0, must-revalidate');
  readfile($path); exit;
}

if (!class_exists('ZipArchive')) { http_response_code(500); die('Fitur ZIP tidak tersedia (ZipArchive belum aktif di server ini).'); }

/* ---- Mode SKP: ?judul=ID [&bulan=M] [&ekin=1] ----
   Unduh semua bukti dukung 1 SKP (setahun) atau 1 bulan, atau berkas Ekin-nya. */
if (isset($_GET['judul'])) {
  $jid=(int)$_GET['judul'];
  $q=$pdo->prepare("SELECT * FROM kegiatan WHERE id=? AND user_id=?"); $q->execute([$jid,$uid]);
  $J=$q->fetch(); if(!$J){ http_response_code(404); die('SKP tidak ditemukan.'); }
  $bulanF = isset($_GET['bulan']) ? (int)$_GET['bulan'] : 0;
  $ekinOnly = !empty($_GET['ekin']);
  $daftar=[];   // [path, namaDalamZip, namaAsli]
  $used=[]; $total=0;
  $tambah=function($path,$nama,$asli=null) use (&$daftar,&$used,&$total){
    if(!is_file($path)) return;
    if(isset($used[$nama])){ $used[$nama]++; $dot=strrpos($nama,'.');
      $nama=$dot!==false?substr($nama,0,$dot).'('.$used[$nama].')'.substr($nama,$dot):$nama.'('.$used[$nama].')'; }
    else $used[$nama]=1;
    $daftar[]=[$path,$nama,$asli?:basename($nama)]; $total++;
  };
  if($ekinOnly){
    $e=$pdo->prepare("SELECT ef.*, sk.nama AS rhk_nama, ss.bulan_ke AS rb FROM ekinfile ef
        LEFT JOIN subkegiatan sk ON sk.id=ef.rhk_id LEFT JOIN subsub ss ON ss.id=ef.raksi_id
        WHERE ef.kegiatan_id=? ORDER BY ef.id");
    $e->execute([$jid]);
    foreach($e->fetchAll() as $r){
      $bk=(int)($r['rb']?:$r['bulan_ke']);
      $folder = ($r['raksi_id']>0 && $bk>=1 && $bk<=12) ? 'Ekin Bulanan/'.$bulanNama[$bk].'/' : (($r['rhk_id']>0)?'Ekin RHK/':'Ekin Tahunan/');
      $tambah(UPLOAD_DIR.'/'.basename($r['stored_name']), $folder.$sanit($r['original_name']), $r['original_name']);
    }
    $namaFile='e-Kinerja_Ekin_'.$sanit($J['nama']);
  } else {
    $sql="SELECT b.original_name,b.stored_name,h.created_at,h.bulan_ke,sk.nama AS rhk_nama
          FROM berkas b JOIN harian h ON h.id=b.harian_id
          JOIN subkegiatan sk ON sk.id=h.subkegiatan_id
          WHERE sk.kegiatan_id=?".($bulanF?" AND h.bulan_ke=?":"")." ORDER BY h.bulan_ke, h.created_at, b.id";
    $st=$pdo->prepare($sql); $st->execute($bulanF?[$jid,$bulanF]:[$jid]);
    foreach($st->fetchAll() as $r){
      $bk=(int)$r['bulan_ke']; $bn=($bk>=1&&$bk<=12)?$bulanNama[$bk]:'Lainnya';
      $tgl=date('Y-m-d',strtotime($r['created_at']));
      $folder=$bn.'/'.$sanit($r['rhk_nama']).'/';
      $tambah(UPLOAD_DIR.'/'.basename($r['stored_name']), $folder.$tgl.'_'.$sanit($r['original_name']), $r['original_name']);
    }
    $namaFile='e-Kinerja_'.$sanit($J['nama']).($bulanF?('_'.$bulanNama[$bulanF]):'_Setahun');
  }
  if(!$total){ http_response_code(404); die('Belum ada berkas untuk diunduh.'); }
  if($total===1){ kirimSatuBerkas($daftar[0][0], $daftar[0][2]); }
  $tmp=tempnam(sys_get_temp_dir(),'ekin'); $zip=new ZipArchive();
  if($zip->open($tmp,ZipArchive::OVERWRITE)!==true){ http_response_code(500); die('Gagal membuat ZIP.'); }
  foreach($daftar as $d) $zip->addFile($d[0],$d[1]);
  $zip->close();
  header('Content-Type: application/zip'); header('Content-Length: '.filesize($tmp));
  header('Content-Disposition: attachment; filename="'.$namaFile.'.zip"');
  header('Cache-Control: private, max-age=0, must-revalidate');
  readfile($tmp); @unlink($tmp); exit;
}
/* ---- Mode ringkas: unduh berkas 1 catatan harian, atau 1 sub+bulan ---- */
if (isset($_GET['harian']) || (isset($_GET['subkegiatan']) && isset($_GET['bulan_ke']))) {
  if (isset($_GET['harian'])) {
    $hid=(int)$_GET['harian'];
    $q=$pdo->prepare("SELECT b.original_name,b.stored_name,b.created_at FROM berkas b
      JOIN harian h ON h.id=b.harian_id JOIN subkegiatan sk ON sk.id=h.subkegiatan_id JOIN kegiatan k ON k.id=sk.kegiatan_id
      WHERE b.harian_id=? AND k.user_id=? ORDER BY b.id");
    $q->execute([$hid,$uid]); $rows=$q->fetchAll(); $namaFile='bukti_catatan';
  } else {
    $sid2=(int)$_GET['subkegiatan']; $bk2=(int)$_GET['bulan_ke'];
    $q=$pdo->prepare("SELECT b.original_name,b.stored_name,b.created_at FROM berkas b
      JOIN harian h ON h.id=b.harian_id JOIN subkegiatan sk ON sk.id=b.subkegiatan_id JOIN kegiatan k ON k.id=sk.kegiatan_id
      WHERE b.subkegiatan_id=? AND h.bulan_ke=? AND k.user_id=? ORDER BY b.id");
    $q->execute([$sid2,$bk2,$uid]); $rows=$q->fetchAll();
    $namaFile='bukti_'.($bk2>=1&&$bk2<=12?$bulanNama[$bk2]:'bulan');
  }
  if(!$rows){ http_response_code(404); die('Belum ada bukti untuk diunduh.'); }
  if(count($rows)===1){ $r0=$rows[0]; kirimSatuBerkas(UPLOAD_DIR.'/'.basename($r0['stored_name']), $r0['original_name']); }
  $tmp=tempnam(sys_get_temp_dir(),'ekin'); $zip=new ZipArchive();
  if($zip->open($tmp,ZipArchive::OVERWRITE)!==true){ http_response_code(500); die('Gagal membuat ZIP.'); }
  $used=[];
  foreach($rows as $r){ $path=UPLOAD_DIR.'/'.basename($r['stored_name']); if(!is_file($path))continue;
    $base=date('Y-m-d',strtotime($r['created_at'])).'_'.$sanit($r['original_name']);
    if(isset($used[$base])){$used[$base]++;$dot=strrpos($base,'.');$base=$dot!==false?substr($base,0,$dot).'('.$used[$base].')'.substr($base,$dot):$base.'('.$used[$base].')';}else $used[$base]=1;
    $zip->addFile($path,$base); }
  $zip->close();
  header('Content-Type: application/zip'); header('Content-Length: '.filesize($tmp));
  header('Content-Disposition: attachment; filename="e-Kinerja_'.$namaFile.'.zip"');
  header('Cache-Control: private, max-age=0, must-revalidate');
  readfile($tmp); @unlink($tmp); exit;
}

$mode=''; $kid=0; $sid=0;
if (isset($_GET['kegiatan']))    { $mode='kegiatan';    $kid=(int)$_GET['kegiatan']; }
elseif (isset($_GET['subkegiatan'])){ $mode='subkegiatan'; $sid=(int)$_GET['subkegiatan']; }
else { http_response_code(400); die('Parameter tidak lengkap.'); }

/* Kumpulkan daftar sub kegiatan yang akan di-zip */
if ($mode==='kegiatan') {
  $s=$pdo->prepare("SELECT * FROM kegiatan WHERE id=? AND user_id=?"); $s->execute([$kid,$uid]);
  $keg=$s->fetch(); if(!$keg){ http_response_code(404); die('Kegiatan tidak ditemukan.'); }
  $s=$pdo->prepare("SELECT * FROM subkegiatan WHERE kegiatan_id=? ORDER BY bulan_ke, nama"); $s->execute([$kid]);
  $subs=$s->fetchAll();
  $judulZip=trim($keg['nama'].' '.labelPeriode($keg));
  $namaFile='e-Kinerja_'.$sanit($keg['nama']).'_'.str_replace(' ','_',labelPeriode($keg));
} else {
  $s=$pdo->prepare("SELECT sk.*, k.nama AS kegiatan_nama, k.bulan AS kegiatan_bulan
    FROM subkegiatan sk JOIN kegiatan k ON k.id=sk.kegiatan_id WHERE sk.id=? AND k.user_id=?");
  $s->execute([$sid,$uid]); $sk=$s->fetch();
  if(!$sk){ http_response_code(404); die('Sub kegiatan tidak ditemukan.'); }
  $keg=['nama'=>$sk['kegiatan_nama'],'bulan'=>$sk['kegiatan_bulan']];
  $subs=[$sk];
  $judulZip=$sk['nama'].' ('.$sk['kegiatan_nama'].')';
  $namaFile='e-Kinerja_'.$sanit($sk['nama']);
}

$berkasStmt=$pdo->prepare("SELECT b.*, h.uraian FROM berkas b JOIN harian h ON h.id=b.harian_id WHERE b.subkegiatan_id=? ORDER BY b.created_at, b.id");
$harianStmt=$pdo->prepare("SELECT * FROM harian WHERE subkegiatan_id=? ORDER BY created_at, id");

$tmp=tempnam(sys_get_temp_dir(),'ekin'); $zip=new ZipArchive();
if($zip->open($tmp,ZipArchive::OVERWRITE)!==true){ http_response_code(500); die('Gagal membuat ZIP.'); }

$rekap="REKAP KEGIATAN — {$judulZip}\n".str_repeat('=',56)."\n\n";
$totalBerkas=0; $totalHarian=0; $adaBerkas=false;

foreach($subs as $sub){
  $folder='';
  if($mode==='kegiatan'){ $bk=(int)($sub['bulan_ke']??0);
    $folder=($bk>=1&&$bk<=12 ? $bulanNama[$bk].'/' : '').$sanit($sub['nama']).'/'; }
  $rekap.="SUB KEGIATAN: {$sub['nama']}\n".str_repeat('-',56)."\n";
  // catatan harian -> rekap
  $harianStmt->execute([$sub['id']]);
  foreach($harianStmt->fetchAll() as $h){
    $totalHarian++;
    $ts=strtotime($h['created_at']);
    $rekap.="  • ".$hariNama[(int)date('w',$ts)].", ".(int)date('j',$ts)." ".$bulanNama[(int)date('n',$ts)]." ".date('Y',$ts)." ".date('H:i',$ts)."\n";
    if($h['uraian']!=='' && $h['uraian']!==null) $rekap.="    ".str_replace("\n","\n    ",$h['uraian'])."\n";
  }
  // berkas -> masuk folder sub kegiatan (digabung)
  $berkasStmt->execute([$sub['id']]);
  $used=[];
  $names=[];
  foreach($berkasStmt->fetchAll() as $b){
    $path=UPLOAD_DIR.'/'.basename($b['stored_name']); if(!is_file($path)) continue;
    $tgl=date('Y-m-d',strtotime($b['created_at']));
    $base=$tgl.'_'.$sanit($b['original_name']);
    if(isset($used[$base])){ $used[$base]++; $dot=strrpos($base,'.');
      $base=$dot!==false?substr($base,0,$dot).'('.$used[$base].')'.substr($base,$dot):$base.'('.$used[$base].')';
    } else $used[$base]=1;
    $zip->addFile($path,$folder.$base); $totalBerkas++; $adaBerkas=true; $names[]=$base;
  }
  if($names) $rekap.="  Berkas (".count($names)."): ".implode(', ',$names)."\n";
  $rekap.="\n";
}

// Berkas Ekin resmi (hanya untuk mode kegiatan)
$totalEkin=0;
if($mode==='kegiatan'){
  $es=$pdo->prepare("SELECT * FROM ekinfile WHERE kegiatan_id=? ORDER BY bulan_ke, id"); $es->execute([$kid]);
  $usedE=[];
  foreach($es->fetchAll() as $e){
    $path=UPLOAD_DIR.'/'.basename($e['stored_name']); if(!is_file($path)) continue;
    $bk=(int)($e['bulan_ke']??0);
    $folderE = ($bk>=1&&$bk<=12) ? $bulanNama[$bk].'/Ekin Bulanan/' : 'Ekin/';
    $base=$folderE.$sanit($e['original_name']);
    if(isset($usedE[$base])){$usedE[$base]++;$dot=strrpos($base,'.');$base=$dot!==false?substr($base,0,$dot).'('.$usedE[$base].')'.substr($base,$dot):$base.'('.$usedE[$base].')';}else $usedE[$base]=1;
    $zip->addFile($path,$base); $totalEkin++; $adaBerkas=true;
  }
}

if(!$adaBerkas && $totalHarian===0){ $zip->close(); @unlink($tmp); http_response_code(404); die('Belum ada isi untuk diunduh.'); }

$rekap.=str_repeat('=',56)."\n";
$rekap.="Total: ".count($subs)." sub kegiatan, {$totalHarian} catatan harian, {$totalBerkas} berkas.\n";
$rekap.="Dibuat: ".date('d/m/Y H:i')." WIB\n";
$zip->addFromString('_REKAP.txt',$rekap);
$zip->close();

header('Content-Type: application/zip');
header('Content-Length: '.filesize($tmp));
header('Content-Disposition: attachment; filename="'.$namaFile.'.zip"');
header('Cache-Control: private, max-age=0, must-revalidate');
readfile($tmp); @unlink($tmp); exit;
