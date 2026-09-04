<?php
/* API JSON — model SKP v6:
   RHK (subkegiatan; kategori utama/tambahan, nama, target TAHUNAN, pimpinan) — di bawah wadah tahun (kegiatan)
     > Rencana Aksi (subsub; per BULAN: nama + target BULANAN)
         ├─ Aspek+IKI (aspekiki; boleh banyak; aspek kualitas/kuantitas/waktu + iki)
         └─ Realisasi (harian; uraian + jumlah) > Bukti Dukung (berkas)
   Ekin Tahunan -> RHK; Ekin Bulanan -> Rencana Aksi (ekinfile.rhk_id / raksi_id). */
require __DIR__ . '/bootstrap.php';
requireLoginJson();
header('Content-Type: application/json; charset=utf-8');

$uid    = currentUserId();
$action = $_GET['action'] ?? '';

/* Bila total kiriman melebihi post_max_size, PHP mengosongkan $_POST dan $_FILES
   sebelum kode ini jalan — tanpa penjelasan ini pengguna hanya melihat pesan
   "token keamanan tidak valid" yang menyesatkan. */
if ($_SERVER['REQUEST_METHOD']==='POST' && empty($_POST) && empty($_FILES)
    && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
  http_response_code(413);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok'=>false,'error'=>'Berkas yang dikirim terlalu besar untuk server ('
    .round((int)$_SERVER['CONTENT_LENGTH']/1048576,1).' MB, batas '.ini_get('post_max_size')
    .'). Kirim lebih sedikit berkas sekaligus, atau perkecil ukurannya.']);
  exit;
}
function jout($a){ echo json_encode($a); exit; }
$BULAN = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

/* Wadah tahun (tersembunyi) per (user, tahun) */
function getContainer($pdo,$uid,$tahun){
  $s=$pdo->prepare("SELECT id FROM kegiatan WHERE user_id=? AND tahun=? AND tipe='tahun' ORDER BY id LIMIT 1");
  $s->execute([$uid,$tahun]); $id=$s->fetchColumn();
  if($id) return (int)$id;
  $pdo->prepare("INSERT INTO kegiatan (user_id,nama,tipe,tahun,bulan,created_at) VALUES (?,?,?,?,'',?)")
      ->execute([$uid,'Tahun '.$tahun,'tahun',$tahun,date('Y-m-d H:i:s')]);
  return (int)$pdo->lastInsertId();
}
function ownJudul($pdo,$uid,$id){
  $s=$pdo->prepare("SELECT * FROM kegiatan WHERE id=? AND user_id=?"); $s->execute([$id,$uid]); return $s->fetch();
}
function ownRhk($pdo,$uid,$id){
  $s=$pdo->prepare("SELECT sk.*, k.tahun AS tahun, k.id AS wadah_id, k.nama AS judul_nama, k.tipe AS judul_tipe, k.bulan AS judul_bulan FROM subkegiatan sk JOIN kegiatan k ON k.id=sk.kegiatan_id WHERE sk.id=? AND k.user_id=?");
  $s->execute([$id,$uid]); return $s->fetch();
}
function judulBulanKe($k){ if(($k['tipe']??'')==='bulan' && preg_match('/^\d{4}-(\d{2})$/',$k['bulan']??'',$m)) return (int)$m[1]; return 0; }
function ownRaksi($pdo,$uid,$id){
  $s=$pdo->prepare("SELECT ss.*, sk.id AS rhk_id, sk.nama AS rhk_nama, sk.kategori AS rhk_kategori, sk.pimpinan AS rhk_pimpinan, sk.target AS rhk_target, sk.kegiatan_id AS wadah_id, k.tahun AS tahun
    FROM subsub ss JOIN subkegiatan sk ON sk.id=ss.subkegiatan_id JOIN kegiatan k ON k.id=sk.kegiatan_id WHERE ss.id=? AND k.user_id=?");
  $s->execute([$id,$uid]); return $s->fetch();
}
function ownRealisasi($pdo,$uid,$id){
  $s=$pdo->prepare("SELECT h.* FROM harian h JOIN subkegiatan sk ON sk.id=h.subkegiatan_id JOIN kegiatan k ON k.id=sk.kegiatan_id WHERE h.id=? AND k.user_id=?");
  $s->execute([$id,$uid]); return $s->fetch();
}
function ownIndikator($pdo,$uid,$id){
  $s=$pdo->prepare("SELECT a.* FROM aspekiki a JOIN subkegiatan sk ON sk.id=a.rhk_id JOIN kegiatan k ON k.id=sk.kegiatan_id WHERE a.id=? AND k.user_id=?");
  $s->execute([$id,$uid]); return $s->fetch();
}
/* Batas unggah yang BENAR-BENAR berlaku di server ini. MAX_UPLOAD_MB di
   config.php bisa saja lebih besar daripada upload_max_filesize/post_max_size
   PHP — yang menentukan adalah yang paling kecil. */
function batasUnggahByte(){
  $ke = function($v){
    $v = trim((string)$v); if($v==='') return 0;
    $n = (float)$v; $u = strtoupper(substr($v,-1));
    if($u==='G') $n *= 1024*1024*1024; elseif($u==='M') $n *= 1024*1024; elseif($u==='K') $n *= 1024;
    return (int)$n;
  };
  $c = [MAX_UPLOAD_MB*1024*1024];
  foreach(['upload_max_filesize','post_max_size'] as $k){ $b=$ke(ini_get($k)); if($b>0) $c[]=$b; }
  return min($c);
}
function alasanGagalUnggah($kode,$max){
  switch($kode){
    case UPLOAD_ERR_INI_SIZE:
    case UPLOAD_ERR_FORM_SIZE: return 'ukurannya melebihi batas '.round($max/1048576,1).' MB';
    case UPLOAD_ERR_PARTIAL:   return 'pengirimannya terputus';
    case UPLOAD_ERR_NO_FILE:   return 'berkas tidak terkirim';
    case UPLOAD_ERR_NO_TMP_DIR:return 'folder sementara server tidak ada';
    case UPLOAD_ERR_CANT_WRITE:return 'server gagal menulis berkas';
    case UPLOAD_ERR_EXTENSION: return 'ditolak oleh pengaturan server';
  }
  return 'gagal diunggah';
}
function saveDokFiles(){
  $saved=[]; $skipped=[];
  if(empty($_FILES['dok'])||!is_array($_FILES['dok']['name'])) return [$saved,$skipped];
  $max=batasUnggahByte(); $n=count($_FILES['dok']['name']);
  for($i=0;$i<$n;$i++){
    $orig=$_FILES['dok']['name'][$i]!==''?$_FILES['dok']['name'][$i]:'(tanpa nama)';
    $err=$_FILES['dok']['error'][$i];
    if($err===UPLOAD_ERR_NO_FILE) continue;                    // slot kosong, bukan kegagalan
    if($err!==UPLOAD_ERR_OK){ $skipped[]=$orig.' — '.alasanGagalUnggah($err,$max); continue; }
    $size=(int)$_FILES['dok']['size'][$i]; $tmp=$_FILES['dok']['tmp_name'][$i];
    if($size<=0||!is_uploaded_file($tmp)){ $skipped[]=$orig.' — berkas kosong / tidak sampai ke server'; continue; }
    if($size>$max){ $skipped[]=$orig.' — ukurannya '.round($size/1048576,1).' MB, melebihi batas '.round($max/1048576,1).' MB'; continue; }
    $stored=namaSimpanAman();  // selalu .bin -> tidak bisa dieksekusi/dirender server
    $mime=$_FILES['dok']['type'][$i]?:'application/octet-stream';
    if(function_exists('finfo_open')){$fi=finfo_open(FILEINFO_MIME_TYPE);$d=finfo_file($fi,$tmp);finfo_close($fi);if($d)$mime=$d;}
    if(move_uploaded_file($tmp,UPLOAD_DIR.'/'.$stored)) $saved[]=['orig'=>mb_substr($orig,0,255),'stored'=>$stored,'mime'=>mb_substr($mime,0,150),'size'=>$size];
  }
  return [$saved,$skipped];
}
/* Ekin Tahunan di level SKP (rhk_id=0, raksi_id=0) */
function ekinList2($pdo,$skpId,$bk=0){
  $s=$pdo->prepare("SELECT id,original_name,mime,size FROM ekinfile WHERE kegiatan_id=? AND rhk_id=0 AND raksi_id=0 AND bulan_ke=? ORDER BY id DESC");
  $s->execute([$skpId,$bk]);
  return array_map(fn($r)=>['id'=>(int)$r['id'],'name'=>$r['original_name'],'mime'=>$r['mime'],'size'=>(int)$r['size']],$s->fetchAll());
}
function ekinList($pdo,$rhkId,$raksiId){
  $s=$pdo->prepare("SELECT id,original_name,mime,size FROM ekinfile WHERE rhk_id=? AND raksi_id=? ORDER BY id DESC");
  $s->execute([$rhkId,$raksiId]);
  return array_map(fn($r)=>['id'=>(int)$r['id'],'name'=>$r['original_name'],'mime'=>$r['mime'],'size'=>(int)$r['size']],$s->fetchAll());
}
function loadRealisasi($pdo,$where,$params){
  $hs=$pdo->prepare("SELECT * FROM harian WHERE $where ORDER BY created_at DESC, id DESC");
  $hs->execute($params); $rows=$hs->fetchAll();
  $bs=$pdo->prepare("SELECT id,original_name,mime,size FROM berkas WHERE harian_id=? ORDER BY id");
  foreach($rows as &$h){
    $bs->execute([$h['id']]);
    $h['files']=array_map(fn($f)=>['id'=>(int)$f['id'],'name'=>$f['original_name'],'mime'=>$f['mime'],'size'=>(int)$f['size']],$bs->fetchAll());
    $h['id']=(int)$h['id']; $h['jumlah']=(int)($h['jumlah']??0);
  } unset($h);
  return $rows;
}
function saveBerkasFor($pdo,$hid,$sid){
  $saved=0; $skipped=[];
  if(empty($_FILES['dok'])||!is_array($_FILES['dok']['name'])) return [$saved,$skipped];
  $now=date('Y-m-d H:i:s'); $max=batasUnggahByte();
  $bs=$pdo->prepare("INSERT INTO berkas (harian_id,subkegiatan_id,original_name,stored_name,mime,size,created_at) VALUES (?,?,?,?,?,?,?)");
  $n=count($_FILES['dok']['name']);
  for($i=0;$i<$n;$i++){
    $orig=$_FILES['dok']['name'][$i]!==''?$_FILES['dok']['name'][$i]:'(tanpa nama)';
    $err=$_FILES['dok']['error'][$i];
    if($err===UPLOAD_ERR_NO_FILE) continue;
    if($err!==UPLOAD_ERR_OK){ $skipped[]=$orig.' — '.alasanGagalUnggah($err,$max); continue; }
    $size=(int)$_FILES['dok']['size'][$i]; $tmp=$_FILES['dok']['tmp_name'][$i];
    if($size<=0||!is_uploaded_file($tmp)){ $skipped[]=$orig.' — berkas kosong / tidak sampai ke server'; continue; }
    if($size>$max){ $skipped[]=$orig.' — ukurannya '.round($size/1048576,1).' MB, melebihi batas '.round($max/1048576,1).' MB'; continue; }
    $stored=namaSimpanAman();  // selalu .bin -> tidak bisa dieksekusi/dirender server
    $mime=$_FILES['dok']['type'][$i]?:'application/octet-stream';
    if(function_exists('finfo_open')){ $fi=finfo_open(FILEINFO_MIME_TYPE); $d=finfo_file($fi,$tmp); finfo_close($fi); if($d)$mime=$d; }
    if(move_uploaded_file($tmp,UPLOAD_DIR.'/'.$stored)){
      $bs->execute([$hid,$sid,mb_substr($orig,0,255),$stored,mb_substr($mime,0,150),$size,$now]); $saved++;
    }
  }
  return [$saved,$skipped];
}

try {

/* ================= DASHBOARD ================= */
if ($action==='dashboard'){
  $own = "JOIN subkegiatan sk ON sk.id=h.subkegiatan_id JOIN kegiatan k ON k.id=sk.kegiatan_id WHERE k.user_id=?";
  $c = function($sql,$p=null) use($pdo,$uid){ $s=$pdo->prepare($sql); $s->execute($p??[$uid]); return (int)$s->fetchColumn(); };
  $totals = [
    'rhk'      => $c("SELECT COUNT(*) FROM subkegiatan sk JOIN kegiatan k ON k.id=sk.kegiatan_id WHERE k.user_id=?"),
    'raksi'    => $c("SELECT COUNT(*) FROM subsub ss JOIN subkegiatan sk ON sk.id=ss.subkegiatan_id JOIN kegiatan k ON k.id=sk.kegiatan_id WHERE k.user_id=?"),
    'realisasi'=> $c("SELECT COUNT(*) FROM harian h $own"),
    'berkas'   => $c("SELECT COUNT(*) FROM berkas b JOIN subkegiatan sk ON sk.id=b.subkegiatan_id JOIN kegiatan k ON k.id=sk.kegiatan_id WHERE k.user_id=?"),
  ];
  $bl = date('Y-m');
  $bulanIni = [
    'ym'       => $bl,
    'raksi'    => $c("SELECT COUNT(*) FROM subsub ss JOIN subkegiatan sk ON sk.id=ss.subkegiatan_id JOIN kegiatan k ON k.id=sk.kegiatan_id WHERE k.user_id=? AND ss.bulan_ke=?", [$uid,(int)date('n')]),
    'realisasi'=> $c("SELECT COUNT(*) FROM harian h $own AND DATE_FORMAT(h.created_at,'%Y-%m')=?", [$uid,$bl]),
    'berkas'   => $c("SELECT COUNT(*) FROM berkas b JOIN subkegiatan sk ON sk.id=b.subkegiatan_id JOIN kegiatan k ON k.id=sk.kegiatan_id WHERE k.user_id=? AND DATE_FORMAT(b.created_at,'%Y-%m')=?", [$uid,$bl]),
  ];
  $s=$pdo->prepare("SELECT DATE_FORMAT(h.created_at,'%Y-%m') ym, COUNT(*) c FROM harian h $own GROUP BY ym");
  $s->execute([$uid]);
  $map=[]; foreach($s->fetchAll() as $r){ $map[$r['ym']]=(int)$r['c']; }
  $series=[]; for($i=5;$i>=0;$i--){ $ym=date('Y-m', strtotime("first day of -$i month")); $series[]=['ym'=>$ym,'count'=>$map[$ym]??0]; }
  $s=$pdo->prepare("SELECT h.id, h.uraian, h.created_at, h.subsub_id raksi_id, sk.nama rhk_nama
    FROM harian h JOIN subkegiatan sk ON sk.id=h.subkegiatan_id JOIN kegiatan k ON k.id=sk.kegiatan_id
    WHERE k.user_id=? ORDER BY h.created_at DESC, h.id DESC LIMIT 8");
  $s->execute([$uid]);
  $rc=$s->fetchAll();
  // jumlah berkas untuk 8 catatan terbaru diambil sekali jalan (dulu 8 query terpisah)
  $nfile=[];
  if($rc){
    $idsR=implode(',',array_map(fn($r)=>(int)$r['id'],$rc));
    foreach($pdo->query("SELECT harian_id, COUNT(*) n FROM berkas WHERE harian_id IN ($idsR) GROUP BY harian_id") as $b)
      $nfile[(int)$b['harian_id']]=(int)$b['n'];
  }
  $recent=array_map(fn($r)=>['id'=>(int)$r['id'],'uraian'=>$r['uraian'],'created_at'=>$r['created_at'],'raksi_id'=>(int)$r['raksi_id'],'rhk_nama'=>$r['rhk_nama'],
    'files'=>$nfile[(int)$r['id']]??0], $rc);
  $ts=$pdo->prepare("SELECT sk.id,sk.nama,sk.kategori,sk.target,k.tahun,
      (SELECT COALESCE(SUM(h.jumlah),0) FROM harian h WHERE h.subkegiatan_id=sk.id) cap
    FROM subkegiatan sk JOIN kegiatan k ON k.id=sk.kegiatan_id WHERE k.user_id=? ORDER BY k.tahun DESC, sk.id");
  $ts->execute([$uid]);
  $tahunan=array_map(fn($r)=>['id'=>(int)$r['id'],'nama'=>$r['nama'],'kategori'=>$r['kategori'],'tahun'=>(int)$r['tahun'],'target'=>(int)$r['target'],'capaian'=>(int)$r['cap']],$ts->fetchAll());
  jout(['ok'=>true,'user'=>currentUserName(),'totals'=>$totals,'bulan_ini'=>$bulanIni,'series'=>$series,'recent'=>$recent,'tahunan'=>$tahunan]);
}

/* ================= KALENDER ================= */
if ($action==='calendar'){
  $bl=$_GET['month']??date('Y-m');
  if(!preg_match('/^\d{4}-\d{2}$/',$bl)) $bl=date('Y-m');
  $s=$pdo->prepare("SELECT h.id,h.uraian,h.created_at,h.subsub_id raksi_id,sk.nama rhk_nama
    FROM harian h JOIN subkegiatan sk ON sk.id=h.subkegiatan_id JOIN kegiatan k ON k.id=sk.kegiatan_id
    WHERE k.user_id=? AND DATE_FORMAT(h.created_at,'%Y-%m')=? ORDER BY h.created_at, h.id");
  $s->execute([$uid,$bl]);
  jout(['ok'=>true,'month'=>$bl,'items'=>array_map(fn($r)=>['id'=>(int)$r['id'],'uraian'=>$r['uraian'],'created_at'=>$r['created_at'],'raksi_id'=>(int)$r['raksi_id'],'rhk_nama'=>$r['rhk_nama'],
    'files'=>(int)$pdo->query("SELECT COUNT(*) FROM berkas WHERE harian_id=".(int)$r['id'])->fetchColumn()],$s->fetchAll())]);
}

/* ================= JUDUL (wadah: Tahunan / Bulanan) ================= */
if ($action==='judul_list'){
  $s=$pdo->prepare("SELECT k.*,
      (SELECT COUNT(*) FROM subkegiatan sk WHERE sk.kegiatan_id=k.id) jml_rhk,
      (SELECT COALESCE(SUM(a.target),0) FROM aspekiki a JOIN subkegiatan sk ON sk.id=a.rhk_id WHERE sk.kegiatan_id=k.id) target,
      (SELECT COALESCE(SUM(h.jumlah),0) FROM harian h JOIN subkegiatan sk ON sk.id=h.subkegiatan_id WHERE sk.kegiatan_id=k.id) capaian
    FROM kegiatan k WHERE k.user_id=? ORDER BY k.tahun DESC, k.created_at DESC, k.id DESC");
  $s->execute([$uid]);
  jout(['ok'=>true,'user'=>currentUserName(),'judul'=>array_map(fn($r)=>[
    'id'=>(int)$r['id'],'nama'=>$r['nama'],'tipe'=>$r['tipe']?:'tahun','tahun'=>(int)$r['tahun'],'bulan'=>$r['bulan']??'',
    'jml_rhk'=>(int)$r['jml_rhk'],'target'=>(int)$r['target'],'capaian'=>(int)$r['capaian']
  ],$s->fetchAll())]);
}

if ($action==='judul_add'){
  csrf_check();
  $nama=trim($_POST['nama']??''); if($nama==='') jout(['ok'=>false,'error'=>'Judul SKP wajib diisi']);
  $tipe=($_POST['tipe']??'tahun')==='bulan'?'bulan':'tahun';
  if($tipe==='tahun'){ $tahun=(int)($_POST['tahun']??0); if($tahun<2000||$tahun>2100) jout(['ok'=>false,'error'=>'Tahun tidak valid']); $bulan=''; }
  else { $bulan=trim($_POST['bulan']??''); if(!preg_match('/^\d{4}-\d{2}$/',$bulan)) jout(['ok'=>false,'error'=>'Bulan tidak valid']); $tahun=(int)substr($bulan,0,4); }
  $now=date('Y-m-d H:i:s');
  $pdo->prepare("INSERT INTO kegiatan (user_id,nama,tipe,tahun,bulan,created_at) VALUES (?,?,?,?,?,?)")
      ->execute([$uid,mb_substr($nama,0,255),$tipe,$tahun,$bulan,$now]);
  $sid=(int)$pdo->lastInsertId();
  // RHK dari form SKP: JSON [{nama,kategori,pimpinan,indikator:[{aspek,iki,target}]}]
  $rj=json_decode((string)($_POST['rhkjson']??''),true);
  if(is_array($rj)){
    $ins=$pdo->prepare("INSERT INTO subkegiatan (kegiatan_id,nama,pimpinan,kategori,target,satuan,bulan_ke,created_at) VALUES (?,?,?,?,0,'',0,?)");
    $insA=$pdo->prepare("INSERT INTO aspekiki (rhk_id,subsub_id,aspek,iki,target,created_at) VALUES (?,0,?,?,?,?)");
    foreach($rj as $row){
      $nm=trim((string)($row['nama']??'')); if($nm==='')continue;
      $kat=(($row['kategori']??'utama')==='tambahan')?'tambahan':'utama';
      $ins->execute([$sid,mb_substr($nm,0,255),mb_substr(trim((string)($row['pimpinan']??'')),0,255),$kat,$now]);
      $rid=(int)$pdo->lastInsertId();
      foreach(($row['indikator']??[]) as $ind){
        $ikiv=trim((string)($ind['iki']??'')); if($ikiv==='')continue;
        $asp=in_array($ind['aspek']??'',['kualitas','kuantitas','waktu'])?$ind['aspek']:'kuantitas';
        $insA->execute([$rid,$asp,mb_substr($ikiv,0,255),max(0,(int)($ind['target']??0)),$now]);
      }
    }
  }
  [$saved,]=saveDokFiles();
  if($saved){ $es=$pdo->prepare("INSERT INTO ekinfile (kegiatan_id,bulan_ke,sub_id,rhk_id,raksi_id,original_name,stored_name,mime,size,created_at) VALUES (?,0,0,0,0,?,?,?,?,?)");
    foreach($saved as $f) $es->execute([$sid,$f['orig'],$f['stored'],$f['mime'],$f['size'],$now]); }
  jout(['ok'=>true,'id'=>$sid]);
}

/* Daftar RHK sebuah SKP untuk satu BULAN (RHK sama di semua bulan) */
if ($action==='skpbulan_get'){
  $sid=(int)($_GET['judul_id']??0); $bk=(int)($_GET['bulan_ke']??0);
  $k=ownJudul($pdo,$uid,$sid); if(!$k) jout(['ok'=>false,'error'=>'SKP tidak ditemukan']);
  if(($k['tipe']??'tahun')==='bulan'){ $jb=judulBulanKe($k); if($jb) $bk=$jb; }
  if($bk<1||$bk>12) jout(['ok'=>false,'error'=>'Bulan tidak valid']);

  /* Diambil sekaligus per-kelompok (bukan per-RHK) supaya jumlah query tetap
     kecil berapa pun banyak RHK/realisasinya — ini yang dulu bikin perpindahan
     halaman terasa lambat. */
  $s=$pdo->prepare("SELECT * FROM subkegiatan sk WHERE sk.kegiatan_id=?
      AND (sk.bulan_ke=0 OR sk.bulan_ke=?)
      AND NOT EXISTS(SELECT 1 FROM rhkskip z WHERE z.rhk_id=sk.id AND z.bulan_ke=?)
      ORDER BY (CASE WHEN sk.kategori='tambahan' THEN 1 ELSE 0 END), sk.created_at, sk.id");
  $s->execute([$sid,$bk,$bk]);
  $rows=$s->fetchAll();
  if(!$rows) jout(['ok'=>true,'judul'=>['id'=>(int)$k['id'],'nama'=>$k['nama'],'tipe'=>$k['tipe']?:'tahun','tahun'=>(int)$k['tahun'],'bulan'=>$k['bulan']??'','bulan_ke'=>judulBulanKe($k)],
    'bulan_ke'=>$bk,'bulan_nama'=>$BULAN[$bk],'rhk'=>[],'ekin'=>ekinList2($pdo,$sid,$bk)]);

  $rhkIds=array_map(fn($r)=>(int)$r['id'],$rows);
  $inR=implode(',',$rhkIds);

  // rencana aksi bulan ini (1 per RHK)
  $raksi=[];
  $q=$pdo->prepare("SELECT id,subkegiatan_id,nama,target FROM subsub WHERE subkegiatan_id IN ($inR) AND bulan_ke=? ORDER BY id");
  $q->execute([$bk]);
  foreach($q->fetchAll() as $r){ $rid=(int)$r['subkegiatan_id']; if(!isset($raksi[$rid])) $raksi[$rid]=$r; }
  $raksiIds=array_map(fn($r)=>(int)$r['id'],array_values($raksi));
  $inA=$raksiIds?implode(',',$raksiIds):'0';

  // indikator per RHK
  $indik=[];
  foreach($pdo->query("SELECT id,rhk_id,aspek,iki,target FROM aspekiki WHERE rhk_id IN ($inR) ORDER BY id") as $a){
    $indik[(int)$a['rhk_id']][]=['id'=>(int)$a['id'],'aspek'=>$a['aspek']?:'kuantitas','iki'=>$a['iki']??'','target'=>(int)$a['target']];
  }

  // capaian & jumlah realisasi per rencana aksi
  $capA=[]; $jmlA=[];
  foreach($pdo->query("SELECT subsub_id, COALESCE(SUM(jumlah),0) c, COUNT(*) n FROM harian WHERE subsub_id IN ($inA) GROUP BY subsub_id") as $r){
    $capA[(int)$r['subsub_id']]=(int)$r['c']; $jmlA[(int)$r['subsub_id']]=(int)$r['n'];
  }
  // capaian setahun per RHK
  $capThn=[];
  foreach($pdo->query("SELECT subkegiatan_id, COALESCE(SUM(jumlah),0) c FROM harian WHERE subkegiatan_id IN ($inR) GROUP BY subkegiatan_id") as $r){
    $capThn[(int)$r['subkegiatan_id']]=(int)$r['c'];
  }
  // daftar realisasi bulan ini
  $realA=[]; $harIds=[];
  foreach($pdo->query("SELECT id,subsub_id,uraian,jumlah,created_at FROM harian WHERE subsub_id IN ($inA) ORDER BY created_at, id") as $h){
    $hid=(int)$h['id']; $harIds[]=$hid;
    $realA[(int)$h['subsub_id']][]=['id'=>$hid,'uraian'=>$h['uraian']??'','jumlah'=>(int)($h['jumlah']??0),'created_at'=>$h['created_at'],'files'=>[]];
  }
  // bukti dukung untuk semua realisasi itu
  $bukA=[];
  if($harIds){
    $inH=implode(',',$harIds);
    foreach($pdo->query("SELECT id,harian_id,original_name,mime,size FROM berkas WHERE harian_id IN ($inH) ORDER BY id") as $f){
      $bukA[(int)$f['harian_id']][]=['id'=>(int)$f['id'],'name'=>$f['original_name'],'mime'=>$f['mime']];
    }
  }

  $list=[];
  foreach($rows as $r){
    $rid=(int)$r['id'];
    $ra=$raksi[$rid]??null; $raksiId=$ra?(int)$ra['id']:0;
    $ind=$indik[$rid]??[];
    $rl=$raksiId?($realA[$raksiId]??[]):[];
    $jb2=0;
    foreach($rl as $i=>$z){ $fs=$bukA[$z['id']]??[]; $rl[$i]['files']=$fs; $jb2+=count($fs); }
    $list[]=['id'=>$rid,'nama'=>$r['nama'],'kategori'=>$r['kategori']??'utama','pimpinan'=>$r['pimpinan']??'',
      'capaian_tahun'=>$capThn[$rid]??0,
      'target'=>array_sum(array_column($ind,'target')),'indikator'=>$ind,'aspek'=>$ind,
      'raksi_id'=>$raksiId,'raksi_nama'=>$ra?$ra['nama']:'','raksi_target'=>$ra?(int)$ra['target']:0,
      'capaian'=>$raksiId?($capA[$raksiId]??0):0,'jml_realisasi'=>$raksiId?($jmlA[$raksiId]??0):0,
      'jml_berkas'=>$jb2,'realisasi'=>$rl];
  }
  jout(['ok'=>true,'judul'=>['id'=>(int)$k['id'],'nama'=>$k['nama'],'tipe'=>$k['tipe']?:'tahun','tahun'=>(int)$k['tahun'],'bulan'=>$k['bulan']??'','bulan_ke'=>judulBulanKe($k)],
    'bulan_ke'=>$bk,'bulan_nama'=>$BULAN[$bk],'rhk'=>$list,'ekin'=>ekinList2($pdo,$sid,$bk)]);
}


if ($action==='judul_update'){
  csrf_check();
  $id=(int)($_POST['id']??0);
  $k=ownJudul($pdo,$uid,$id); if(!$k) jout(['ok'=>false,'error'=>'Judul tidak ditemukan']);
  $nama=trim($_POST['nama']??''); if($nama==='') jout(['ok'=>false,'error'=>'Judul wajib diisi']);
  $tipe=$k['tipe']?:'tahun';
  if($tipe==='tahun'){ $tahun=(int)($_POST['tahun']??$k['tahun']); if($tahun<2000||$tahun>2100) $tahun=(int)$k['tahun'];
    $pdo->prepare("UPDATE kegiatan SET nama=?, tahun=? WHERE id=? AND user_id=?")->execute([mb_substr($nama,0,255),$tahun,$id,$uid]); }
  else { $bulan=trim($_POST['bulan']??$k['bulan']); if(!preg_match('/^\d{4}-\d{2}$/',$bulan)) $bulan=$k['bulan'];
    $pdo->prepare("UPDATE kegiatan SET nama=?, bulan=?, tahun=? WHERE id=? AND user_id=?")->execute([mb_substr($nama,0,255),$bulan,(int)substr($bulan,0,4),$id,$uid]); }
  jout(['ok'=>true]);
}

if ($action==='judul_delete'){
  csrf_check();
  $id=(int)($_POST['id']??0);
  if(!ownJudul($pdo,$uid,$id)) jout(['ok'=>false,'error'=>'Data tidak ditemukan']);
  $s=$pdo->prepare("SELECT b.stored_name FROM berkas b JOIN subkegiatan sk ON sk.id=b.subkegiatan_id WHERE sk.kegiatan_id=?");
  $s->execute([$id]); foreach($s->fetchAll() as $f){ $p=UPLOAD_DIR.'/'.basename($f['stored_name']); if(is_file($p)) @unlink($p); }
  $es=$pdo->prepare("SELECT stored_name FROM ekinfile WHERE kegiatan_id=?"); $es->execute([$id]);
  foreach($es->fetchAll() as $f){ $p=UPLOAD_DIR.'/'.basename($f['stored_name']); if(is_file($p)) @unlink($p); }
  $pdo->prepare("DELETE FROM ekinfile WHERE kegiatan_id=?")->execute([$id]);
  $pdo->prepare("DELETE FROM berkas WHERE subkegiatan_id IN (SELECT id FROM subkegiatan WHERE kegiatan_id=?)")->execute([$id]);
  $pdo->prepare("DELETE FROM harian WHERE subkegiatan_id IN (SELECT id FROM subkegiatan WHERE kegiatan_id=?)")->execute([$id]);
  $pdo->prepare("DELETE FROM aspekiki WHERE subsub_id IN (SELECT ss.id FROM subsub ss JOIN subkegiatan sk ON sk.id=ss.subkegiatan_id WHERE sk.kegiatan_id=?)")->execute([$id]);
  $pdo->prepare("DELETE FROM subsub WHERE subkegiatan_id IN (SELECT id FROM subkegiatan WHERE kegiatan_id=?)")->execute([$id]);
  $pdo->prepare("DELETE FROM subkegiatan WHERE kegiatan_id=?")->execute([$id]);
  $pdo->prepare("DELETE FROM kegiatan WHERE id=? AND user_id=?")->execute([$id,$uid]);
  jout(['ok'=>true]);
}

if ($action==='judul_get'){
  $id=(int)($_GET['id']??0);
  $k=ownJudul($pdo,$uid,$id); if(!$k) jout(['ok'=>false,'error'=>'Judul tidak ditemukan']);
  $s=$pdo->prepare("SELECT sk.*,
      (SELECT COUNT(*) FROM subsub ss WHERE ss.subkegiatan_id=sk.id) jml_raksi,
      (SELECT COUNT(*) FROM aspekiki a WHERE a.rhk_id=sk.id) jml_ind,
      (SELECT COALESCE(SUM(a.target),0) FROM aspekiki a WHERE a.rhk_id=sk.id) tgt_ind,
      (SELECT COALESCE(SUM(h.jumlah),0) FROM harian h WHERE h.subkegiatan_id=sk.id) capaian
    FROM subkegiatan sk WHERE sk.kegiatan_id=?
    ORDER BY (CASE WHEN sk.kategori='tambahan' THEN 1 ELSE 0 END), sk.created_at, sk.id");
  $s->execute([$id]);
  $rhk=array_map(fn($r)=>['id'=>(int)$r['id'],'nama'=>$r['nama'],'pimpinan'=>$r['pimpinan']??'','kategori'=>$r['kategori']??'utama',
    'target'=>(int)$r['tgt_ind'],'capaian'=>(int)$r['capaian'],'jml_raksi'=>(int)$r['jml_raksi'],'jml_ind'=>(int)$r['jml_ind']],$s->fetchAll());
  // ringkasan 12 bulan (RHK sama di tiap bulan)
  $cap=[]; for($i=1;$i<=12;$i++)$cap[$i]=['bulan_ke'=>$i,'jml_raksi'=>0,'capaian'=>0,'target'=>0,'jml_bukti'=>0,'jml_realisasi'=>0,'tanpa_bukti'=>0];
  $ms=$pdo->prepare("SELECT ss.bulan_ke bk, COUNT(*) c, COALESCE(SUM(ss.target),0) t,
      COALESCE(SUM((SELECT COALESCE(SUM(h.jumlah),0) FROM harian h WHERE h.subsub_id=ss.id)),0) s,
      COALESCE(SUM((SELECT COUNT(*) FROM berkas b JOIN harian h2 ON h2.id=b.harian_id WHERE h2.subsub_id=ss.id)),0) nb
    FROM subsub ss JOIN subkegiatan sk ON sk.id=ss.subkegiatan_id WHERE sk.kegiatan_id=? GROUP BY ss.bulan_ke");
  $ms->execute([$id]);
  foreach($ms->fetchAll() as $r){ $b=(int)$r['bk']; if($b>=1&&$b<=12){ $cap[$b]['jml_raksi']=(int)$r['c']; $cap[$b]['capaian']=(int)$r['s']; $cap[$b]['target']=(int)$r['t']; $cap[$b]['jml_bukti']=(int)$r['nb']; } }
  /* per bulan: jumlah realisasi & berapa yang belum ada bukti (untuk tanda ⚠️ di kartu bulan) */
  $mb=$pdo->prepare("SELECT h.bulan_ke bk, COUNT(*) jr,
      SUM(CASE WHEN nb.c IS NULL OR nb.c=0 THEN 1 ELSE 0 END) tb
    FROM harian h
    LEFT JOIN (SELECT harian_id, COUNT(*) c FROM berkas GROUP BY harian_id) nb ON nb.harian_id=h.id
    WHERE h.subkegiatan_id IN (SELECT id FROM subkegiatan WHERE kegiatan_id=?) GROUP BY h.bulan_ke");
  $mb->execute([$id]);
  foreach($mb->fetchAll() as $r){ $b=(int)$r['bk']; if($b>=1&&$b<=12){ $cap[$b]['jml_realisasi']=(int)$r['jr']; $cap[$b]['tanpa_bukti']=(int)$r['tb']; } }
  jout(['ok'=>true,'judul'=>['id'=>(int)$k['id'],'nama'=>$k['nama'],'tipe'=>$k['tipe']?:'tahun','tahun'=>(int)$k['tahun'],'bulan'=>$k['bulan']??'','bulan_ke'=>judulBulanKe($k)],
    'rhk'=>$rhk,'bulanan'=>array_values($cap),'ekin'=>ekinList2($pdo,$id)]);
}

/* ================= RHK ================= */
if ($action==='rhk_list'){
  $s=$pdo->prepare("SELECT sk.*, k.tahun tahun,
      (SELECT COUNT(*) FROM subsub ss WHERE ss.subkegiatan_id=sk.id) jml_raksi,
      (SELECT COALESCE(SUM(h.jumlah),0) FROM harian h WHERE h.subkegiatan_id=sk.id) capaian
    FROM subkegiatan sk JOIN kegiatan k ON k.id=sk.kegiatan_id WHERE k.user_id=?
    ORDER BY k.tahun DESC, (CASE WHEN sk.kategori='tambahan' THEN 1 ELSE 0 END), sk.created_at, sk.id");
  $s->execute([$uid]);
  jout(['ok'=>true,'user'=>currentUserName(),'rhk'=>array_map(fn($r)=>[
    'id'=>(int)$r['id'],'nama'=>$r['nama'],'pimpinan'=>$r['pimpinan']??'','kategori'=>$r['kategori']??'utama','tahun'=>(int)$r['tahun'],
    'target'=>(int)$r['target'],'capaian'=>(int)$r['capaian'],'jml_raksi'=>(int)$r['jml_raksi']
  ],$s->fetchAll())]);
}

if ($action==='rhk_add'){
  csrf_check();
  $nama=trim($_POST['nama']??''); if($nama==='') jout(['ok'=>false,'error'=>'Rencana Hasil Kerja wajib diisi']);
  $kategori=($_POST['kategori']??'utama')==='tambahan'?'tambahan':'utama';
  $pimpinan=trim($_POST['pimpinan']??''); $target=max(0,(int)($_POST['target']??0));
  $wadah=(int)($_POST['judul_id']??0);
  if(!ownJudul($pdo,$uid,$wadah)) jout(['ok'=>false,'error'=>'Judul tidak ditemukan']);
  $now=date('Y-m-d H:i:s');
  $s=$pdo->prepare("INSERT INTO subkegiatan (kegiatan_id,nama,pimpinan,kategori,target,satuan,bulan_ke,created_at) VALUES (?,?,?,?,?,'',0,?)");
  $s->execute([$wadah,mb_substr($nama,0,255),mb_substr($pimpinan,0,255),$kategori,$target,$now]);
  $rid=(int)$pdo->lastInsertId();
  // indikator (bisa >1) via JSON [{aspek,iki,target}]
  $ij=json_decode((string)($_POST['indjson']??''),true);
  if(is_array($ij)){ $insA=$pdo->prepare("INSERT INTO aspekiki (rhk_id,subsub_id,aspek,iki,target,created_at) VALUES (?,0,?,?,?,?)");
    foreach($ij as $ind){ $ikiv=trim((string)($ind['iki']??'')); if($ikiv==='')continue;
      $asp=in_array($ind['aspek']??'',['kualitas','kuantitas','waktu'])?$ind['aspek']:'kuantitas';
      $insA->execute([$rid,$asp,mb_substr($ikiv,0,255),max(0,(int)($ind['target']??0)),$now]); } }
  [$saved,]=saveDokFiles();
  if($saved){ $es=$pdo->prepare("INSERT INTO ekinfile (kegiatan_id,bulan_ke,sub_id,rhk_id,raksi_id,original_name,stored_name,mime,size,created_at) VALUES (?,0,0,?,0,?,?,?,?,?)");
    foreach($saved as $f) $es->execute([$wadah,$rid,$f['orig'],$f['stored'],$f['mime'],$f['size'],$now]); }
  jout(['ok'=>true,'id'=>$rid]);
}

if ($action==='rhk_update'){
  csrf_check();
  $id=(int)($_POST['id']??0);
  $r=ownRhk($pdo,$uid,$id); if(!$r) jout(['ok'=>false,'error'=>'RHK tidak ditemukan']);
  $nama=trim($_POST['nama']??''); if($nama==='') jout(['ok'=>false,'error'=>'RHK wajib diisi']);
  $kategori=($_POST['kategori']??'utama')==='tambahan'?'tambahan':'utama';
  $pimpinan=trim($_POST['pimpinan']??''); $target=max(0,(int)($_POST['target']??0));
  $pdo->prepare("UPDATE subkegiatan SET nama=?, pimpinan=?, kategori=?, target=? WHERE id=?")
      ->execute([mb_substr($nama,0,255),mb_substr($pimpinan,0,255),$kategori,$target,$id]);
  jout(['ok'=>true]);
}

if ($action==='rhk_kelola'){
  csrf_check();
  $id=(int)($_POST['id']??0);
  $r=ownRhk($pdo,$uid,$id); if(!$r) jout(['ok'=>false,'error'=>'RHK tidak ditemukan']);
  $nama=trim($_POST['nama']??''); if($nama==='') jout(['ok'=>false,'error'=>'Nama RHK wajib diisi']);
  $kategori=(($_POST['kategori']??'utama')==='tambahan')?'tambahan':'utama';
  $cakupan=($_POST['cakupan']??'semua')==='bulan'?'bulan':'semua';
  $bk=(int)($_POST['bulan_ke']??0);
  $ind=json_decode((string)($_POST['indjson']??''),true); if(!is_array($ind)) $ind=[];
  $now=date('Y-m-d H:i:s');
  $pdo->beginTransaction();
  if($cakupan==='bulan' && $bk>=1 && $bk<=12 && (int)$r['bulan_ke']===0){
    // buat salinan khusus bulan ini, dan kecualikan RHK asli di bulan ini
    $ins=$pdo->prepare("INSERT INTO subkegiatan (kegiatan_id,nama,pimpinan,kategori,target,satuan,bulan_ke,created_at) VALUES (?,?,?,?,0,'',?,?)");
    $ins->execute([(int)$r['kegiatan_id'],mb_substr($nama,0,255),$r['pimpinan']??'',$kategori,$bk,$r['created_at']??$now]);
    $baru=(int)$pdo->lastInsertId();
    $ia=$pdo->prepare("INSERT INTO aspekiki (rhk_id,subsub_id,aspek,iki,target,created_at) VALUES (?,0,?,?,?,?)");
    foreach($ind as $a){ $iki=trim((string)($a['iki']??'')); if($iki==='')continue;
      $asp=in_array($a['aspek']??'',['kualitas','kuantitas','waktu'])?$a['aspek']:'kuantitas';
      $ia->execute([$baru,$asp,mb_substr($iki,0,255),max(0,(int)($a['target']??0)),$now]); }
    // pindahkan rencana aksi bulan ini (bila ada) ke RHK salinan
    $pdo->prepare("UPDATE subsub SET subkegiatan_id=? WHERE subkegiatan_id=? AND bulan_ke=?")->execute([$baru,$id,$bk]);
    $pdo->prepare("UPDATE harian SET subkegiatan_id=? WHERE subkegiatan_id=? AND bulan_ke=?")->execute([$baru,$id,$bk]);
    $pdo->prepare("UPDATE berkas SET subkegiatan_id=? WHERE harian_id IN (SELECT id FROM harian WHERE subkegiatan_id=?)")->execute([$baru,$baru]);
    $pdo->prepare("INSERT INTO rhkskip (rhk_id,bulan_ke) VALUES (?,?)")->execute([$id,$bk]);
    $pdo->commit();
    jout(['ok'=>true,'id'=>$baru,'mode'=>'bulan']);
  }
  // cakupan semua bulan (atau RHK ini memang khusus satu bulan)
  $pdo->prepare("UPDATE subkegiatan SET nama=?, kategori=? WHERE id=?")->execute([mb_substr($nama,0,255),$kategori,$id]);
  $pdo->prepare("DELETE FROM aspekiki WHERE rhk_id=?")->execute([$id]);
  $ia=$pdo->prepare("INSERT INTO aspekiki (rhk_id,subsub_id,aspek,iki,target,created_at) VALUES (?,0,?,?,?,?)");
  foreach($ind as $a){ $iki=trim((string)($a['iki']??'')); if($iki==='')continue;
    $asp=in_array($a['aspek']??'',['kualitas','kuantitas','waktu'])?$a['aspek']:'kuantitas';
    $ia->execute([$id,$asp,mb_substr($iki,0,255),max(0,(int)($a['target']??0)),$now]); }
  $pdo->commit();
  jout(['ok'=>true,'id'=>$id,'mode'=>'semua']);
}
if ($action==='rhk_delete'){
  csrf_check();
  $id=(int)($_POST['id']??0);
  if(!ownRhk($pdo,$uid,$id)) jout(['ok'=>false,'error'=>'Data tidak ditemukan']);
  $s=$pdo->prepare("SELECT stored_name FROM berkas WHERE subkegiatan_id=?"); $s->execute([$id]);
  foreach($s->fetchAll() as $f){ $p=UPLOAD_DIR.'/'.basename($f['stored_name']); if(is_file($p)) @unlink($p); }
  $es=$pdo->prepare("SELECT stored_name FROM ekinfile WHERE rhk_id=?"); $es->execute([$id]);
  foreach($es->fetchAll() as $f){ $p=UPLOAD_DIR.'/'.basename($f['stored_name']); if(is_file($p)) @unlink($p); }
  $pdo->prepare("DELETE FROM ekinfile WHERE rhk_id=?")->execute([$id]);
  $pdo->prepare("DELETE FROM berkas WHERE subkegiatan_id=?")->execute([$id]);
  $pdo->prepare("DELETE FROM harian WHERE subkegiatan_id=?")->execute([$id]);
  $pdo->prepare("DELETE FROM aspekiki WHERE subsub_id IN (SELECT id FROM subsub WHERE subkegiatan_id=?)")->execute([$id]);
  $pdo->prepare("DELETE FROM subsub WHERE subkegiatan_id=?")->execute([$id]);
  $pdo->prepare("DELETE FROM rhkskip WHERE rhk_id=?")->execute([$id]);
  $pdo->prepare("DELETE FROM subkegiatan WHERE id=?")->execute([$id]);
  jout(['ok'=>true]);
}

if ($action==='rhk_get'){
  global $BULAN;
  $id=(int)($_GET['id']??0);
  $r=ownRhk($pdo,$uid,$id); if(!$r) jout(['ok'=>false,'error'=>'RHK tidak ditemukan']);
  $cap=(int)$pdo->query("SELECT COALESCE(SUM(jumlah),0) FROM harian WHERE subkegiatan_id=".(int)$id)->fetchColumn();
  $rhk=['id'=>(int)$r['id'],'nama'=>$r['nama'],'pimpinan'=>$r['pimpinan']??'','kategori'=>$r['kategori']??'utama','tahun'=>(int)$r['tahun'],'target'=>(int)$r['target'],'capaian'=>$cap];
  $judul=['id'=>(int)$r['wadah_id'],'nama'=>$r['judul_nama'],'tipe'=>$r['judul_tipe']?:'tahun','tahun'=>(int)$r['tahun'],'bulan'=>$r['judul_bulan']??'',
    'bulan_ke'=>judulBulanKe(['tipe'=>$r['judul_tipe'],'bulan'=>$r['judul_bulan']])];
  $rs=$pdo->prepare("SELECT * FROM subsub WHERE subkegiatan_id=? ORDER BY bulan_ke, created_at, id"); $rs->execute([$id]);
  $list=[]; $usedBulan=[];
  foreach($rs->fetchAll() as $x){
    $rid2=(int)$x['id']; $bk=(int)$x['bulan_ke']; $usedBulan[$bk]=true;
    $c=(int)$pdo->query("SELECT COALESCE(SUM(jumlah),0) FROM harian WHERE subsub_id=$rid2")->fetchColumn();
    $ja=(int)$pdo->query("SELECT COUNT(*) FROM aspekiki WHERE subsub_id=$rid2")->fetchColumn();
    $jr=(int)$pdo->query("SELECT COUNT(*) FROM harian WHERE subsub_id=$rid2")->fetchColumn();
    $jb=(int)$pdo->query("SELECT COUNT(*) FROM berkas b JOIN harian h ON h.id=b.harian_id WHERE h.subsub_id=$rid2")->fetchColumn();
    $nb=(int)$pdo->query("SELECT COUNT(*) FROM harian h WHERE h.subsub_id=$rid2 AND NOT EXISTS(SELECT 1 FROM berkas b WHERE b.harian_id=h.id)")->fetchColumn();
    $list[]=['id'=>$rid2,'bulan_ke'=>$bk,'nama'=>$x['nama'],'target'=>(int)$x['target'],'capaian'=>$c,'jml_aspek'=>$ja,'jml_realisasi'=>$jr,'jml_berkas'=>$jb,'no_bukti'=>$nb];
  }
  $bulanAda=[]; foreach($usedBulan as $b=>$_) if($b>=1&&$b<=12) $bulanAda[]=['bulan_ke'=>$b,'nama'=>$BULAN[$b]];
  $as=$pdo->prepare("SELECT id,aspek,iki,target FROM aspekiki WHERE rhk_id=? ORDER BY id"); $as->execute([$id]);
  $indik=array_map(fn($a)=>['id'=>(int)$a['id'],'aspek'=>$a['aspek']?:'kuantitas','iki'=>$a['iki']??'','target'=>(int)$a['target']],$as->fetchAll());
  $rhk['target']=array_sum(array_column($indik,'target'));
  jout(['ok'=>true,'rhk'=>$rhk,'judul'=>$judul,'indikator'=>$indik,'raksi'=>$list,'bulan_ada'=>$bulanAda,'ekin'=>ekinList($pdo,$id,0)]);
}

/* ================= RENCANA AKSI (subsub) ================= */
if ($action==='raksi_add'){
  csrf_check();
  $rid=(int)($_POST['rhk_id']??0); $bk=(int)($_POST['bulan_ke']??0);
  $rhk=ownRhk($pdo,$uid,$rid); if(!$rhk) jout(['ok'=>false,'error'=>'RHK tidak ditemukan']);
  // Judul bulanan -> bulan dikunci ke bulan judul
  if(($rhk['judul_tipe']??'tahun')==='bulan'){ $jb=judulBulanKe(['tipe'=>'bulan','bulan'=>$rhk['judul_bulan']??'']); if($jb>=1&&$jb<=12) $bk=$jb; }
  if($bk<1||$bk>12) jout(['ok'=>false,'error'=>'Bulan tidak valid']);
  $nama=trim($_POST['nama']??''); $target=max(0,(int)($_POST['target']??0));
  $salin=(int)($_POST['salin_from']??0);
  $now=date('Y-m-d H:i:s');
  $pdo->beginTransaction();
  // Salin dari rencana aksi bulan lain (nama saja; target dikosongkan; tanpa realisasi/bukti)
  if($salin>0){ $src=ownRaksi($pdo,$uid,$salin);
    if($src && (int)$src['rhk_id']===$rid && $nama==='') $nama=$src['nama']; }
  $s=$pdo->prepare("INSERT INTO subsub (subkegiatan_id,bulan_ke,nama,target,satuan,aspek,created_at) VALUES (?,?,?,?,'','',?)");
  $s->execute([$rid,$bk,mb_substr($nama,0,255),$target,$now]);
  $raksiId=(int)$pdo->lastInsertId();
  [$saved,]=saveDokFiles();
  if($saved){ $es=$pdo->prepare("INSERT INTO ekinfile (kegiatan_id,bulan_ke,sub_id,rhk_id,raksi_id,original_name,stored_name,mime,size,created_at) VALUES (?,?,0,?,?,?,?,?,?,?)");
    foreach($saved as $f) $es->execute([(int)$rhk['wadah_id'],$bk,$rid,$raksiId,$f['orig'],$f['stored'],$f['mime'],$f['size'],$now]); }
  $pdo->commit();
  jout(['ok'=>true,'id'=>$raksiId]);
}

if ($action==='raksi_update'){
  csrf_check();
  $id=(int)($_POST['id']??0);
  if(!ownRaksi($pdo,$uid,$id)) jout(['ok'=>false,'error'=>'Rencana Aksi tidak ditemukan']);
  $nama=trim($_POST['nama']??''); $target=max(0,(int)($_POST['target']??0));
  $pdo->prepare("UPDATE subsub SET nama=?, target=? WHERE id=?")->execute([mb_substr($nama,0,255),$target,$id]);
  jout(['ok'=>true]);
}

if ($action==='raksi_delete'){
  csrf_check();
  $id=(int)($_POST['id']??0);
  if(!ownRaksi($pdo,$uid,$id)) jout(['ok'=>false,'error'=>'Data tidak ditemukan']);
  $s=$pdo->prepare("SELECT stored_name FROM berkas b JOIN harian h ON h.id=b.harian_id WHERE h.subsub_id=?"); $s->execute([$id]);
  foreach($s->fetchAll() as $f){ $p=UPLOAD_DIR.'/'.basename($f['stored_name']); if(is_file($p)) @unlink($p); }
  $es=$pdo->prepare("SELECT stored_name FROM ekinfile WHERE raksi_id=?"); $es->execute([$id]);
  foreach($es->fetchAll() as $f){ $p=UPLOAD_DIR.'/'.basename($f['stored_name']); if(is_file($p)) @unlink($p); }
  $pdo->prepare("DELETE FROM ekinfile WHERE raksi_id=?")->execute([$id]);
  $pdo->prepare("DELETE FROM berkas WHERE harian_id IN (SELECT id FROM harian WHERE subsub_id=?)")->execute([$id]);
  $pdo->prepare("DELETE FROM harian WHERE subsub_id=?")->execute([$id]);
  $pdo->prepare("DELETE FROM aspekiki WHERE subsub_id=?")->execute([$id]);
  $pdo->prepare("DELETE FROM subsub WHERE id=?")->execute([$id]);
  jout(['ok'=>true]);
}

if ($action==='raksi_get'){
  global $BULAN;
  $id=(int)($_GET['id']??0);
  $x=ownRaksi($pdo,$uid,$id); if(!$x) jout(['ok'=>false,'error'=>'Rencana Aksi tidak ditemukan']);
  $bk=(int)$x['bulan_ke'];
  $cap=(int)$pdo->query("SELECT COALESCE(SUM(jumlah),0) FROM harian WHERE subsub_id=".(int)$id)->fetchColumn();
  $harian=loadRealisasi($pdo,"subsub_id=?",[$id]);
  $berkas=[]; foreach($harian as $h) foreach($h['files'] as $f) $berkas[]=$f;
  jout(['ok'=>true,
    'raksi'=>['id'=>(int)$x['id'],'bulan_ke'=>$bk,'bulan_nama'=>$BULAN[$bk]??'','nama'=>$x['nama'],'target'=>(int)$x['target'],'capaian'=>$cap],
    'rhk'=>['id'=>(int)$x['rhk_id'],'nama'=>$x['rhk_nama'],'kategori'=>$x['rhk_kategori']??'utama','pimpinan'=>$x['rhk_pimpinan']??'','target'=>(int)$x['rhk_target'],'tahun'=>(int)$x['tahun']],
    'harian'=>$harian,'berkas'=>$berkas,'ekin'=>ekinList($pdo,(int)$x['rhk_id'],$id)]);
}

/* ================= INDIKATOR (aspek + IKI + target tahunan) di level RHK ================= */
if ($action==='indikator_add'){
  csrf_check();
  $rid=(int)($_POST['rhk_id']??0);
  if(!ownRhk($pdo,$uid,$rid)) jout(['ok'=>false,'error'=>'RHK tidak ditemukan']);
  $iki=trim($_POST['iki']??''); if($iki==='') jout(['ok'=>false,'error'=>'Indikator Kinerja Individu wajib diisi']);
  $aspek=in_array($_POST['aspek']??'',['kualitas','kuantitas','waktu'])?$_POST['aspek']:'kuantitas';
  $target=max(0,(int)($_POST['target']??0));
  $pdo->prepare("INSERT INTO aspekiki (rhk_id,subsub_id,aspek,iki,target,created_at) VALUES (?,0,?,?,?,?)")
      ->execute([$rid,$aspek,mb_substr($iki,0,255),$target,date('Y-m-d H:i:s')]);
  jout(['ok'=>true,'id'=>(int)$pdo->lastInsertId()]);
}
if ($action==='indikator_update'){
  csrf_check();
  $id=(int)($_POST['id']??0);
  if(!ownIndikator($pdo,$uid,$id)) jout(['ok'=>false,'error'=>'Indikator tidak ditemukan']);
  $iki=trim($_POST['iki']??''); if($iki==='') jout(['ok'=>false,'error'=>'IKI wajib diisi']);
  $aspek=in_array($_POST['aspek']??'',['kualitas','kuantitas','waktu'])?$_POST['aspek']:'kuantitas';
  $target=max(0,(int)($_POST['target']??0));
  $pdo->prepare("UPDATE aspekiki SET aspek=?, iki=?, target=? WHERE id=?")->execute([$aspek,mb_substr($iki,0,255),$target,$id]);
  jout(['ok'=>true]);
}
if ($action==='indikator_delete'){
  csrf_check();
  $id=(int)($_POST['id']??0);
  if(!ownIndikator($pdo,$uid,$id)) jout(['ok'=>false,'error'=>'Data tidak ditemukan']);
  $pdo->prepare("DELETE FROM aspekiki WHERE id=?")->execute([$id]);
  jout(['ok'=>true]);
}

/* ================= REALISASI (harian) ================= */
if ($action==='realisasi_add'){
  csrf_check();
  $raksiId=(int)($_POST['raksi_id']??0);
  $x=ownRaksi($pdo,$uid,$raksiId); if(!$x) jout(['ok'=>false,'error'=>'Rencana Aksi tidak ditemukan']);
  $rid=(int)$x['rhk_id']; $bk=(int)$x['bulan_ke'];
  $uraian=trim($_POST['uraian']??'');
  $hasFiles = !empty($_FILES['dok']) && is_array($_FILES['dok']['name']) && count(array_filter($_FILES['dok']['name']))>0;
  if($uraian==='' && !$hasFiles) jout(['ok'=>false,'error'=>'Isi realisasi atau lampirkan minimal 1 bukti']);
  $jumlah=max(0,(int)($_POST['jumlah']??0));
  $tgl=trim($_POST['tanggal']??'');
  $created=preg_match('/^\d{4}-\d{2}-\d{2}$/',$tgl) ? $tgl.' '.date('H:i:s') : date('Y-m-d H:i:s');
  $pdo->beginTransaction();
  $s=$pdo->prepare("INSERT INTO harian (subkegiatan_id,subsub_id,uraian,jumlah,bulan_ke,created_at) VALUES (?,?,?,?,?,?)");
  $s->execute([$rid,$raksiId,$uraian,$jumlah,$bk,$created]);
  $hid=(int)$pdo->lastInsertId();
  [$saved,$skipped]=saveBerkasFor($pdo,$hid,$rid);
  $pdo->commit();
  jout(['ok'=>true,'id'=>$hid,'saved_files'=>$saved,'skipped'=>$skipped]);
}
if ($action==='realisasi_add_files'){
  csrf_check();
  $hid=(int)($_POST['harian_id']??0);
  $h=ownRealisasi($pdo,$uid,$hid); if(!$h) jout(['ok'=>false,'error'=>'Realisasi tidak ditemukan']);
  if(empty($_FILES['dok'])||!is_array($_FILES['dok']['name'])||count(array_filter($_FILES['dok']['name']))===0)
    jout(['ok'=>false,'error'=>'Tidak ada berkas dipilih']);
  [$saved,$skipped]=saveBerkasFor($pdo,$hid,(int)$h['subkegiatan_id']);
  jout(['ok'=>true,'saved_files'=>$saved,'skipped'=>$skipped]);
}
if ($action==='realisasi_delete'){
  csrf_check();
  $id=(int)($_POST['id']??0);
  if(!ownRealisasi($pdo,$uid,$id)) jout(['ok'=>false,'error'=>'Data tidak ditemukan']);
  $s=$pdo->prepare("SELECT stored_name FROM berkas WHERE harian_id=?"); $s->execute([$id]);
  foreach($s->fetchAll() as $f){ $p=UPLOAD_DIR.'/'.basename($f['stored_name']); if(is_file($p)) @unlink($p); }
  $pdo->prepare("DELETE FROM berkas WHERE harian_id=?")->execute([$id]);
  $pdo->prepare("DELETE FROM harian WHERE id=?")->execute([$id]);
  jout(['ok'=>true]);
}
if ($action==='realisasi_update'){
  csrf_check();
  $id=(int)($_POST['id']??0);
  $h=ownRealisasi($pdo,$uid,$id); if(!$h) jout(['ok'=>false,'error'=>'Realisasi tidak ditemukan']);
  $uraian=trim($_POST['uraian']??''); $jumlah=max(0,(int)($_POST['jumlah']??0));
  $tgl=trim($_POST['tanggal']??'');
  if(preg_match('/^\d{4}-\d{2}-\d{2}$/',$tgl)){
    $timepart=substr((string)$h['created_at'],11); if(!preg_match('/^\d{2}:\d{2}:\d{2}$/',$timepart)) $timepart='12:00:00';
    $pdo->prepare("UPDATE harian SET uraian=?, jumlah=?, created_at=? WHERE id=?")->execute([$uraian,$jumlah,$tgl.' '.$timepart,$id]);
  } else {
    $pdo->prepare("UPDATE harian SET uraian=?, jumlah=? WHERE id=?")->execute([$uraian,$jumlah,$id]);
  }
  jout(['ok'=>true]);
}

/* ================= EKIN ================= */
if ($action==='ekin_add'){
  csrf_check();
  $rid=(int)($_POST['rhk_id']??0); $raksiId=(int)($_POST['raksi_id']??0); $skpId=(int)($_POST['judul_id']??0);
  // Ekin Tahunan di level SKP
  if($skpId>0 && $rid===0){
    $k=ownJudul($pdo,$uid,$skpId); if(!$k) jout(['ok'=>false,'error'=>'SKP tidak ditemukan']);
    $bkS=(int)($_POST['bulan_ke']??0); if($bkS<0||$bkS>12)$bkS=0;
    [$saved,$skipped]=saveDokFiles();
    if(!$saved) jout(['ok'=>false,'error'=>'Tidak ada berkas'.($skipped?' (terlalu besar)':'')]);
    $now=date('Y-m-d H:i:s');
    $s=$pdo->prepare("INSERT INTO ekinfile (kegiatan_id,bulan_ke,sub_id,rhk_id,raksi_id,original_name,stored_name,mime,size,created_at) VALUES (?,?,0,0,0,?,?,?,?,?)");
    foreach($saved as $f) $s->execute([$skpId,$bkS,$f['orig'],$f['stored'],$f['mime'],$f['size'],$now]);
    jout(['ok'=>true,'saved'=>count($saved),'skipped'=>$skipped]);
  }
  $rhk=ownRhk($pdo,$uid,$rid); if(!$rhk) jout(['ok'=>false,'error'=>'RHK tidak ditemukan']);
  $bk=0;
  if($raksiId>0){ $x=ownRaksi($pdo,$uid,$raksiId); if(!$x||(int)$x['rhk_id']!==$rid) jout(['ok'=>false,'error'=>'Rencana Aksi tidak cocok']); $bk=(int)$x['bulan_ke']; }
  [$saved,$skipped]=saveDokFiles();
  if(!$saved) jout(['ok'=>false,'error'=>'Tidak ada berkas'.($skipped?' (terlalu besar)':'')]);
  $now=date('Y-m-d H:i:s');
  $s=$pdo->prepare("INSERT INTO ekinfile (kegiatan_id,bulan_ke,sub_id,rhk_id,raksi_id,original_name,stored_name,mime,size,created_at) VALUES (?,?,0,?,?,?,?,?,?,?)");
  foreach($saved as $f) $s->execute([(int)$rhk['wadah_id'],$bk,$rid,$raksiId,$f['orig'],$f['stored'],$f['mime'],$f['size'],$now]);
  jout(['ok'=>true,'saved'=>count($saved),'skipped'=>$skipped]);
}
if ($action==='ekin_delete'){
  csrf_check();
  $id=(int)($_POST['id']??0);
  $s=$pdo->prepare("SELECT ef.* FROM ekinfile ef JOIN kegiatan k ON k.id=ef.kegiatan_id WHERE ef.id=? AND k.user_id=?");
  $s->execute([$id,$uid]); $f=$s->fetch(); if(!$f) jout(['ok'=>false,'error'=>'Tidak ditemukan']);
  $p=UPLOAD_DIR.'/'.basename($f['stored_name']); if(is_file($p)) @unlink($p);
  $pdo->prepare("DELETE FROM ekinfile WHERE id=?")->execute([$id]);
  jout(['ok'=>true]);
}

jout(['ok'=>false,'error'=>'Aksi tidak dikenali']);

} catch (Throwable $ex){
  if($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  jout(['ok'=>false,'error'=>'Kesalahan server: '.$ex->getMessage()]);
}
