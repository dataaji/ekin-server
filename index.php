<?php
require __DIR__ . '/bootstrap.php';
if (userCount($pdo) === 0) redirect('setup.php');
requireLogin();
headerAman();
$CSRF = csrf_token();
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#0c2f2c">
<title><?= e(APP_NAME) ?> — <?= e(APP_SUBTITLE) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root{
    --ink:#0f1b2d; --ink2:#334155; --muted:#64748b; --faint:#94a3b8;
    --line:#e7ebf0; --line2:#f0f3f6;
    --bg:#f4f6f8; --card:#ffffff;
    --brand:#0d9488; --brand-d:#0f766e; --brand-l:#e8f7f4; --brand-ring:#cdeae4;
    --accent:#f59e0b; --accent-l:#fef3c7; --accent-ink:#92400e;
    --danger:#e11d48; --danger-l:#ffe4e6;
    --good:#16a34a; --good-l:#dcfce7; --over:#2563eb; --over-l:#dbeafe;
    --sb:#0c2b29; --sb2:#0f3b37; --sb-tx:#a7cbc5; --sb-tx2:#7fb0a9; --sb-active:#12514a;
    --r:16px; --r-sm:11px; --r-lg:20px;
    --sh-sm:0 1px 2px rgba(15,27,45,.05);
    --sh-md:0 1px 3px rgba(15,27,45,.06),0 8px 22px rgba(15,27,45,.05);
    --sh-lg:0 12px 40px rgba(15,27,45,.12);
    --f:'Plus Jakarta Sans',system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
  }
  *{box-sizing:border-box;margin:0;padding:0}
  html,body{height:100%}
  body{font-family:var(--f);color:var(--ink);background:var(--bg);line-height:1.5;-webkit-font-smoothing:antialiased;-webkit-text-size-adjust:100%}
  a{color:inherit}
  ::selection{background:var(--brand-ring)}

  /* ===== App shell ===== */
  .app{display:flex;min-height:100vh}
  .sidebar{position:fixed;top:0;left:0;bottom:0;width:250px;background:linear-gradient(185deg,var(--sb2),var(--sb));
    color:var(--sb-tx);display:flex;flex-direction:column;padding:20px 16px;z-index:50;transition:transform .28s cubic-bezier(.4,0,.2,1),width .2s ease,padding .2s ease;overflow:hidden}
  .content{transition:margin-left .2s ease}
  .sb-brand{display:flex;align-items:center;gap:11px;padding:8px 8px;margin-bottom:12px;border-radius:12px;cursor:pointer;transition:background .12s}
  .sb-brand:hover{background:rgba(255,255,255,.06)}
  .sb-logo{position:relative}
  .sb-brand:hover .sb-logo::after{content:'⇔';position:absolute;right:-7px;bottom:-7px;font-size:11px;background:#0b3b38;color:#7fb0a9;border-radius:50%;width:16px;height:16px;display:grid;place-items:center}
  .sb-logo{width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#14b8a6,#0d9488);display:grid;place-items:center;font-size:22px;box-shadow:0 6px 16px rgba(13,148,136,.4);flex:none}
  .sb-brand h1{font-size:19px;font-weight:800;color:#fff;letter-spacing:-.3px;line-height:1.1}
  .sb-brand span{font-size:11.5px;color:var(--sb-tx2);font-weight:500}
  .sb-nav{display:flex;flex-direction:column;gap:4px;margin-top:6px}
  .sb-label{font-size:10.5px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--sb-tx2);padding:6px 10px;margin-top:6px}
  .navitem{display:flex;align-items:center;gap:11px;padding:11px 12px;border-radius:11px;color:var(--sb-tx);
    font-size:14.5px;font-weight:600;cursor:pointer;text-decoration:none;transition:background .15s,color .15s;border:none;background:none;width:100%;text-align:left;font-family:inherit}
  .navitem .ni-ic{font-size:18px;width:22px;text-align:center;flex:none}
  .navitem:hover{background:rgba(255,255,255,.06);color:#eafaf7}
  .navitem.active{background:var(--sb-active);color:#fff;box-shadow:inset 3px 0 0 #2dd4bf}
  .sb-spacer{flex:1}
  .sb-clock{padding:12px;border-radius:12px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.07);margin-bottom:12px}
  .sb-clock .cd{font-size:12px;color:var(--sb-tx2);font-weight:600}
  .sb-clock .ct{font-size:22px;color:#fff;font-weight:800;font-variant-numeric:tabular-nums;letter-spacing:.5px;margin-top:1px}
  .lockbtn{display:flex;align-items:center;justify-content:center;gap:7px;width:100%;padding:10px;border-radius:11px;border:1px solid rgba(255,255,255,.12);
    background:rgba(255,255,255,.05);color:var(--sb-tx);font:inherit;font-size:13px;font-weight:700;cursor:pointer;transition:all .15s;margin-bottom:10px}
  .lockbtn:hover{background:rgba(255,255,255,.1);color:#fff}
  .lockbtn.on{background:var(--accent);border-color:var(--accent);color:#3a2a05}
  .sb-user{display:flex;align-items:center;gap:10px;padding:9px;border-radius:12px;background:rgba(255,255,255,.05)}
  .sb-ava{width:36px;height:36px;border-radius:10px;background:#14b8a6;color:#04201d;display:grid;place-items:center;font-weight:800;font-size:15px;flex:none;text-transform:uppercase}
  .sb-user .un{flex:1;min-width:0;font-size:13.5px;font-weight:700;color:#eafaf7;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .sb-out{color:var(--sb-tx2);text-decoration:none;font-size:16px;padding:4px;border-radius:8px;flex:none}
  .sb-out:hover{color:#fff;background:rgba(255,255,255,.08)}

  /* ===== Content ===== */
  .content{flex:1;margin-left:250px;min-width:0;display:flex;flex-direction:column}
  .topbar{display:flex;align-items:center;gap:12px;padding:10px 16px;background:var(--card);border-bottom:1px solid var(--line);position:sticky;top:0;z-index:30}
  .hamb{width:38px;height:38px;border-radius:10px;border:1px solid var(--line);background:#fff;font-size:16px;cursor:pointer;display:grid;place-items:center;color:var(--ink2);flex:none;transition:all .12s}
  .hamb:hover{border-color:var(--brand-ring);color:var(--brand-d);background:var(--brand-l)}
  .topbar .tb-brand{font-weight:800;font-size:16px}
  .miniclock{margin-left:auto;display:flex;align-items:center;gap:8px;background:var(--bg);border:1px solid var(--line);border-radius:999px;padding:5px 13px}
  .miniclock .mc-t{font-size:14px;font-weight:800;font-variant-numeric:tabular-nums;color:var(--ink);letter-spacing:.3px}
  .miniclock .mc-d{font-size:11.5px;color:var(--muted);font-weight:600}
  .only-desktop{display:inline-grid}
  @media(min-width:861px){.only-mobile{display:none!important}}
  @media(max-width:860px){.only-desktop{display:none!important}}
  /* sidebar minimize (desktop) */
  @media(min-width:861px){
    body.sb-collapsed .sidebar{width:76px;padding:20px 12px}
    body.sb-collapsed .content{margin-left:76px}
    body.sb-collapsed .sb-brandtx,body.sb-collapsed .ni-tx,body.sb-collapsed .sb-label,body.sb-collapsed .sb-user .un,body.sb-collapsed .sb-out{display:none}
    body.sb-collapsed .sb-brand{justify-content:center;padding:8px 0}
    body.sb-collapsed .sb-brand{justify-content:center}
    body.sb-collapsed .navitem{justify-content:center;padding:12px 0}
    body.sb-collapsed .navitem.active{box-shadow:none;background:var(--sb-active)}
    body.sb-collapsed .sb-user{justify-content:center;padding:9px 0}
  }
  .wrap{width:100%;max-width:980px;margin:0 auto;padding:26px 22px 70px}
  @media(max-width:860px){
    .sidebar{transform:translateX(-100%);box-shadow:var(--sh-lg)}
    body.nav-open .sidebar{transform:translateX(0)}
    .content{margin-left:0}
    .topbar{display:flex}
    .backdrop{position:fixed;inset:0;background:rgba(6,20,18,.5);z-index:40;opacity:0;pointer-events:none;transition:opacity .25s}
    body.nav-open .backdrop{opacity:1;pointer-events:auto}
    .wrap{padding:18px 15px 70px}
  }
  @media(min-width:861px){.backdrop{display:none}}
  @media(max-width:480px){.miniclock .mc-d{display:none}.miniclock{padding:5px 11px}.topbar .tb-brand{font-size:15px}}

  /* ===== Breadcrumb ===== */
  .crumbs{display:flex;align-items:center;gap:7px;flex-wrap:wrap;font-size:13px;margin-bottom:16px}
  .crumbs a{color:var(--brand-d);text-decoration:none;font-weight:600;cursor:pointer}
  .crumbs a:hover{text-decoration:underline}
  .crumbs .sep{color:#cbd5e1}
  .crumbs .cur{color:var(--muted);font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:220px}

  /* ===== Typography / headings ===== */
  .page-h{margin-bottom:18px}
  .page-h h2{font-size:23px;font-weight:800;letter-spacing:-.5px}
  .page-h .sub{font-size:13.5px;color:var(--muted);margin-top:2px}

  /* ===== Buttons ===== */
  .btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;cursor:pointer;border:none;border-radius:11px;
    padding:11px 17px;font:inherit;font-weight:700;font-size:14px;transition:transform .06s,filter .15s,box-shadow .15s;text-decoration:none;white-space:nowrap}
  .btn:active{transform:translateY(1px)}
  .btn:disabled{opacity:.55;cursor:wait}
  .btn-primary{background:var(--brand);color:#fff;box-shadow:0 4px 12px rgba(13,148,136,.28)}
  .btn-primary:hover{filter:brightness(1.05);box-shadow:0 6px 16px rgba(13,148,136,.34)}
  .btn-block{width:100%}
  .btn-sm{padding:8px 13px;font-size:13px;border-radius:9px}
  .btn-ghost{background:#fff;border:1.5px solid var(--line);color:var(--ink2)}
  .btn-ghost:hover{border-color:var(--brand-ring);color:var(--brand-d);background:var(--brand-l)}
  .btn-soft{background:var(--brand-l);color:var(--brand-d)}
  .btn-soft:hover{filter:brightness(.98)}
  .btn-accentsoft{background:#eef2ff;color:#4338ca;border:1.5px solid #e0e7ff}
  .btn-accentsoft:hover{filter:brightness(.98)}
  .backbtn{margin-bottom:14px}

  /* ===== Cards ===== */
  .card{background:var(--card);border:1px solid var(--line);border-radius:var(--r);box-shadow:var(--sh-sm)}
  .pad{padding:18px}

  /* ===== Dashboard ===== */
  .hero{background:linear-gradient(120deg,#0f3b37,#0d5a52 65%,#0d9488);color:#fff;border-radius:var(--r-lg);
    padding:22px 24px;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:20px;box-shadow:0 12px 30px rgba(13,90,82,.28)}
  .hero h2{font-size:22px;font-weight:800;letter-spacing:-.4px}
  .hero .hd{font-size:13.5px;color:#bfe9e2;margin-top:3px;font-weight:500}
  .hero-emo{font-size:52px;opacity:.9;filter:drop-shadow(0 4px 10px rgba(0,0,0,.2))}
  @media(max-width:520px){.hero-emo{display:none}}

  .kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:13px;margin-bottom:20px}
  .kpi{background:var(--card);border:1px solid var(--line);border-radius:var(--r);padding:16px;box-shadow:var(--sh-sm);position:relative;overflow:hidden}
  .kpi .kic{width:40px;height:40px;border-radius:11px;display:grid;place-items:center;font-size:20px;margin-bottom:12px}
  .kpi .knum{font-size:28px;font-weight:800;letter-spacing:-1px;line-height:1}
  .kpi .klbl{font-size:12.5px;color:var(--muted);font-weight:600;margin-top:3px}
  .ic-teal{background:var(--brand-l);color:var(--brand-d)}
  .ic-blue{background:#e6effe;color:#2563eb}
  .ic-violet{background:#f0eafe;color:#7c3aed}
  .ic-amber{background:var(--accent-l);color:var(--accent-ink)}

  .dash-2col{display:grid;grid-template-columns:1.55fr 1fr;gap:16px;margin-bottom:20px}
  @media(max-width:760px){.kpi-grid{grid-template-columns:repeat(2,1fr)}.dash-2col{grid-template-columns:1fr}}
  .panel-h{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:14px}
  .panel-h h3{font-size:15.5px;font-weight:800;letter-spacing:-.2px}
  .panel-h .hint{font-size:12px;color:var(--faint);font-weight:600}

  /* chart */
  #chartWrap{position:relative}
  .barchart{width:100%;height:auto;display:block;overflow:visible}
  .barchart .gl{stroke:var(--line);stroke-width:1}
  .barchart .yl{fill:var(--faint);font-size:10px;text-anchor:end;font-family:var(--f)}
  .barchart .xl{fill:var(--muted);font-size:11px;text-anchor:middle;font-weight:600;font-family:var(--f)}
  .barchart .vl{fill:var(--ink2);font-size:11px;text-anchor:middle;font-weight:700;font-family:var(--f)}
  .barchart .brk{fill:var(--brand);transition:fill .15s}
  .barchart .bar:hover .brk{fill:var(--brand-d)}
  #chartTip{position:absolute;transform:translate(-50%,-100%);background:var(--ink);color:#fff;font-size:12px;font-weight:600;
    padding:6px 10px;border-radius:8px;pointer-events:none;opacity:0;transition:opacity .12s;white-space:nowrap;z-index:5;box-shadow:var(--sh-md)}

  /* bulan ini panel */
  .mini{display:flex;align-items:center;gap:12px;padding:11px 0;border-bottom:1px solid var(--line2)}
  .mini:last-of-type{border-bottom:none}
  .mini .mic{width:34px;height:34px;border-radius:9px;display:grid;place-items:center;font-size:16px;flex:none}
  .mini .mn{font-size:18px;font-weight:800;line-height:1}
  .mini .ml{font-size:12.5px;color:var(--muted);font-weight:600}

  /* feed */
  .feed{display:flex;flex-direction:column}
  .feed-item{display:flex;gap:13px;padding:13px 6px;border-radius:12px;cursor:pointer;transition:background .12s;text-align:left;border:none;background:none;font-family:inherit;width:100%}
  .feed-item:hover{background:var(--brand-l)}
  .feed-item+.feed-item{border-top:1px solid var(--line2)}
  .fdate{flex:none;width:46px;text-align:center;background:var(--brand-l);border:1px solid var(--brand-ring);border-radius:10px;padding:5px 4px;align-self:flex-start}
  .fdate .d{font-size:17px;font-weight:800;color:var(--brand-d);line-height:1}
  .fdate .m{font-size:9.5px;text-transform:uppercase;color:var(--muted);font-weight:700}
  .fbody{flex:1;min-width:0}
  .fbody .ft{font-size:14px;font-weight:600;color:var(--ink);word-break:break-word}
  .fbody .fm{font-size:12px;color:var(--muted);margin-top:3px;display:flex;align-items:center;gap:6px;flex-wrap:wrap}
  .tagpill{background:var(--line2);color:var(--ink2);border-radius:6px;padding:2px 7px;font-size:11px;font-weight:600}

  /* ===== Nav cards (kegiatan / sub) ===== */
  .navcard{display:flex;align-items:center;gap:13px;background:var(--card);border:1px solid var(--line);border-radius:var(--r);
    padding:14px 15px;margin-bottom:11px;cursor:pointer;box-shadow:var(--sh-sm);transition:border-color .15s,box-shadow .15s,transform .06s}
  .navcard:hover{border-color:var(--brand-ring);box-shadow:var(--sh-md)}
  .navcard:active{transform:translateY(1px)}
  .navcard .ic{width:46px;height:46px;border-radius:12px;background:var(--brand-l);display:grid;place-items:center;font-size:23px;flex:none}
  .navcard .body{flex:1;min-width:0}
  .navcard .body h4{font-size:15.5px;font-weight:700;word-break:break-word}
  .navcard .body .meta{font-size:12.5px;color:var(--muted);margin-top:2px}
  .navcard .chev{color:#cbd5e1;font-size:22px;flex:none}
  .iconbtn{background:#fff;border:1px solid var(--line);border-radius:9px;width:34px;height:34px;cursor:pointer;font-size:14px;display:grid;place-items:center;color:var(--muted);flex:none;transition:all .12s}
  .iconbtn:hover{border-color:var(--danger);color:var(--danger);background:var(--danger-l)}
  .iconbtn.edit:hover{border-color:#4338ca;color:#4338ca;background:#eef2ff}
  a.iconbtn{text-decoration:none}
  .iconbtn.dl:hover{border-color:var(--brand);color:var(--brand-d);background:var(--brand-l)}
  .ctl{display:none;gap:5px}
  .navcard.revealed .ctl,.harian.revealed .ctl{display:flex}
  .itemlock{background:none;border:1px solid transparent;cursor:pointer;font-size:15px;padding:5px 7px;border-radius:9px;flex:none;opacity:.5;transition:opacity .12s,background .12s;line-height:1}
  .itemlock:hover{opacity:1;background:var(--line2)}
  .navcard.revealed .itemlock,.harian.revealed .itemlock{opacity:1;background:var(--accent-l);border-color:#fde68a}

  .monthgroup{margin-bottom:22px}
  .monthgroup>.mh{font-size:12px;font-weight:800;color:var(--faint);text-transform:uppercase;letter-spacing:1px;margin:0 4px 10px}
  .viewhead{display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap;margin:2px 0 16px}
  .viewhead h2{font-size:21px;font-weight:800;letter-spacing:-.4px;word-break:break-word}
  .viewhead .meta{font-size:13px;color:var(--muted);margin-top:3px}
  .actions{display:flex;gap:7px;flex-wrap:wrap}

  /* garis progres saat memuat data */
  #muatbar{position:fixed;top:0;left:0;height:3px;width:0;background:linear-gradient(90deg,var(--brand),#38bdf8);z-index:9999;opacity:0;transition:opacity .15s ease}
  body.memuat #muatbar{opacity:1;animation:muatJalan 1.1s ease-in-out infinite}
  @keyframes muatJalan{0%{width:0;margin-left:0}50%{width:70%;margin-left:0}100%{width:0;margin-left:100%}}
  body.memuat{cursor:progress}
  .tiphint{font-size:12.5px;color:var(--muted);background:var(--card);border:1px dashed var(--line);border-radius:11px;padding:9px 13px;margin-bottom:14px;display:block;line-height:1.6}
  .tiphint b{white-space:nowrap}

  /* Panel kelengkapan bulan */
  .lengkap-ok{background:var(--good-l);border:1px solid #bbf7d0;color:#166534;border-radius:12px;padding:11px 14px;margin-bottom:14px;font-size:13.5px;font-weight:600}
  .lengkap-warn{background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;border-radius:12px;padding:11px 14px;margin-bottom:14px}
  .lengkap-warn .lw-head{font-weight:800;font-size:13.5px;margin-bottom:6px}
  .lengkap-warn ul{margin:0;padding-left:20px;font-size:13px;line-height:1.7}
  .lengkap-warn li b{color:#c2410c}
  .mc-warn{position:absolute;top:6px;left:8px;font-size:13px;filter:drop-shadow(0 1px 1px rgba(0,0,0,.15))}

  /* ===== Form / sub view ===== */
  .form-lbl{font-size:12px;font-weight:800;color:var(--brand-d);text-transform:uppercase;letter-spacing:.6px;margin-bottom:12px;display:flex;align-items:center;gap:7px}
  label{display:block;font-size:13px;font-weight:600;margin-bottom:6px;color:var(--ink2)}
  input[type=text],textarea,input[type=month],input[type=number],select{width:100%;border:1.5px solid var(--line);border-radius:11px;padding:11px 13px;font:inherit;color:var(--ink);background:#fff;transition:border-color .15s,box-shadow .15s}
  textarea{resize:vertical;min-height:70px}
  input:focus,textarea:focus,select:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 3px var(--brand-ring)}
  .subrow{display:flex;flex-direction:column;gap:6px;margin-bottom:12px;padding-bottom:11px;border-bottom:1px dashed var(--line)}
  .subrow-main{display:flex;gap:8px}
  .subrow-main input{flex:1}
  .subrow-tgt{display:flex;gap:8px}
  .subrow-tgt input{flex:1;min-width:0}
  .subrow-x{background:var(--danger-l);color:var(--danger);border:none;border-radius:9px;width:42px;cursor:pointer;font-size:14px;font-weight:800;flex:none}
  .subrow-x:hover{filter:brightness(.96)}
  .ktipe{font-size:10px;font-weight:800;padding:2px 7px;border-radius:6px;vertical-align:middle;text-transform:uppercase;letter-spacing:.3px;margin-left:6px}
  .ktipe.y{background:#ede9fe;color:#6d28d9}
  .ktipe.b{background:var(--brand-l);color:var(--brand-d)}
  /* Grid bulan gaya kalender (kotak) */
  .month-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
  @media(max-width:640px){.month-grid{grid-template-columns:repeat(3,1fr);gap:7px}}
  @media(max-width:380px){.month-grid{grid-template-columns:repeat(2,1fr)}}
  .monthcard{position:relative;min-height:150px;background:#fff;border:1px solid var(--line);border-radius:14px;padding:10px;cursor:pointer;box-shadow:var(--sh-sm);
    display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:4px;transition:border-color .12s,box-shadow .12s,transform .06s}
  .monthcard:hover{border-color:var(--brand-ring);box-shadow:var(--sh-md)}
  .monthcard:active{transform:translateY(1px)}
  .monthcard.has{background:var(--brand-l);border-color:var(--brand-ring)}
  .monthcard.done{background:var(--good-l);border-color:#bbf7d0}
  .monthcard.over{background:var(--over-l);border-color:#bfdbfe}
  .monthcard.st-merah{background:#fff7f7;border-color:#fecaca}
  .monthcard.st-biru{background:#f5f9ff;border-color:#bfdbfe}
  .monthcard.st-hijau{background:var(--good-l);border-color:#bbf7d0}
  .monthcard.st-emas{background:#fffbeb;border-color:#fde68a}
  .monthcard.st-none{background:#fff;border-color:var(--line)}
  .mc-nm{font-size:15px;font-weight:800;color:var(--ink);line-height:1.15}
  .mc-meta{font-size:11px;color:var(--muted);line-height:1.25}
  .monthcard.locked{background:#f5f6f8;border-color:var(--line);cursor:default;opacity:.78}
  .monthcard.locked .mc-nm{color:var(--faint)}
  .monthcard.current{outline:2px solid var(--brand);outline-offset:-2px}
  .mc-now{display:block;font-size:9px;font-weight:800;background:var(--brand);color:#fff;padding:1px 6px;border-radius:6px;text-transform:uppercase;letter-spacing:.3px;margin-top:2px}
  .mc-done{display:block;font-size:9px;font-weight:800;background:#e2e8f0;color:#475569;padding:1px 6px;border-radius:6px;text-transform:uppercase;letter-spacing:.3px;margin-top:2px}
  .mc-lock{position:absolute;right:7px;top:7px;background:rgba(255,255,255,.9);border:1px solid var(--line);border-radius:8px;width:26px;height:26px;cursor:pointer;font-size:12px;display:grid;place-items:center;transition:all .12s}
  .mc-lock:hover{border-color:var(--accent);background:var(--accent-l)}
  .mc-miss{position:absolute;left:7px;top:7px;font-size:11px}
  .mc-gembok{position:absolute;right:8px;top:7px;font-size:13px;opacity:.85}
  .ic-danger{background:var(--danger-l);color:var(--danger)}
  .iconbtn.tgt:hover{border-color:var(--accent);color:var(--accent-ink);background:var(--accent-l)}
  .navcard.need-target{border-left:4px solid var(--danger)}
  .need-tgt-chip{display:inline-block;margin-top:7px;font-size:12px;font-weight:700;color:#be123c;background:#fff1f2;border:1px solid #fecdd3;border-radius:8px;padding:4px 10px;cursor:pointer}
  .need-tgt-chip:hover{filter:brightness(.97)}
  /* progress hijau saat tercapai, biru saat lewat target */
  .prog-fill.over{background:linear-gradient(90deg,var(--good),var(--over))}
  .monthcard.done{background:var(--good-l);border-color:#bbf7d0}
  .monthcard.over{background:var(--over-l);border-color:#bfdbfe}
  .monthcard.st-merah{background:#fff7f7;border-color:#fecaca}
  .monthcard.st-biru{background:#f5f9ff;border-color:#bfdbfe}
  .monthcard.st-hijau{background:var(--good-l);border-color:#bbf7d0}
  .monthcard.st-emas{background:#fffbeb;border-color:#fde68a}
  .monthcard.st-none{background:#fff;border-color:var(--line)}
  /* Ekin bar ringkas */
  .ekinbar{display:flex;align-items:center;gap:9px;flex-wrap:wrap;background:var(--card);border:1px solid var(--line);border-radius:12px;padding:9px 12px;margin-bottom:14px;box-shadow:var(--sh-sm)}
  .ekinbar .eb-lbl{font-size:12.5px;font-weight:800;color:var(--brand-d);display:flex;align-items:center;gap:6px;white-space:nowrap}
  .ekinbar .eb-empty{font-size:12.5px;color:var(--faint)}
  .ekinbar .eb-spacer{flex:1;min-width:8px}
  .ekinbar .chips{margin-top:0}
  /* RHK + Indikator (SKP) */
  .rhkcard{background:var(--card);border:1px solid var(--line);border-radius:var(--r);box-shadow:var(--sh-sm);margin-bottom:12px;overflow:hidden}
  .rhk-head{display:flex;align-items:flex-start;gap:10px;padding:13px 15px;background:var(--brand-l);border-bottom:1px solid var(--brand-ring)}
  .rhk-body{flex:1;min-width:0}
  .rhk-pimp{font-size:11.5px;color:var(--muted);font-weight:600;line-height:1.35;word-break:break-word}
  .rhk-head h4{font-size:15px;font-weight:800;color:var(--ink);margin-top:2px;word-break:break-word;line-height:1.3}
  .rhk-ctl{display:flex;gap:5px;flex:none}
  .iconbtn.add:hover{border-color:var(--brand);color:var(--brand-d);background:#fff}
  .ind-list{display:flex;flex-direction:column}
  .ind-empty{padding:12px 15px;font-size:12.5px;color:var(--faint)}
  .indrow{display:flex;align-items:flex-start;gap:10px;padding:12px 15px;border-top:1px solid var(--line2);cursor:pointer;border-left:4px solid transparent;transition:background .12s}
  .indrow:first-child{border-top:none}
  .indrow:hover{background:var(--bg)}
  .indrow.need-target{border-left-color:var(--danger)}
  .ind-main{flex:1;min-width:0}
  .ind-top{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
  .ind-iki{font-size:14px;font-weight:700;color:var(--ink);word-break:break-word}
  .ind-meta{font-size:12px;color:var(--muted);margin-top:3px}
  .ind-ra{font-size:12.5px;color:var(--ink2);margin-top:4px;background:var(--line2);border-radius:8px;padding:5px 9px;word-break:break-word}
  .ind-ctl{display:flex;gap:5px;flex:none}
  .indrow .chev{align-self:center;color:#cbd5e1;font-size:20px;flex:none}
  .aspek{font-size:10px;font-weight:800;padding:2px 8px;border-radius:6px;text-transform:uppercase;letter-spacing:.3px;flex:none}
  .aspek.kuantitas{background:#dbeafe;color:#1d4ed8}
  .aspek.kualitas{background:#ede9fe;color:#6d28d9}
  .aspek.waktu{background:#fef3c7;color:#92400e}
  /* baris RHK di modal kinerja */
  .krow{background:var(--bg);border:1px solid var(--line);border-radius:12px;padding:11px;margin-bottom:10px}
  .krow textarea{min-height:40px;margin-bottom:6px}
  .kr-line{display:flex;gap:8px;margin-bottom:6px}
  .kr-line:last-child{margin-bottom:0}
  .kr-line>select{max-width:130px;flex:none}
  .kr-line .kr-target{max-width:130px}
  /* ===== Polesan keterbacaan ===== */
  .backbtn{border:1.5px solid var(--brand-ring)!important;background:var(--brand-l)!important;color:var(--brand-d)!important;
    font-weight:800!important;padding:9px 16px!important;font-size:13.5px!important;box-shadow:var(--sh-sm)}
  .backbtn:hover{background:#dcf3ee!important;border-color:var(--brand)!important}
  .crumbs{font-size:13.5px;background:var(--card);border:1px solid var(--line);border-radius:11px;padding:9px 13px;box-shadow:var(--sh-sm)}
  .crumbs a{color:var(--brand-d);font-weight:700}
  .crumbs .cur{color:var(--ink);font-weight:700;max-width:none}
  .viewhead h2{font-size:22px}
  .sectlabel{font-size:12.5px;color:var(--ink2)}
  /* tabel lebih terbaca */
  .skptable{font-size:13px;line-height:1.5}
  .skptable th,.skptable td{padding:9px 10px}
  .skptable tbody tr:hover td{background:#fbfdff}
  .skptable .grouprow td{background:#e8eef5;font-size:12.5px;letter-spacing:.4px;text-transform:uppercase}
  .skptable .c-rhk-nama{font-size:13.5px;font-weight:700;color:var(--ink);line-height:1.4}
  .skptable .c-iki{color:var(--ink2)}
  .skptable .c-tgt{font-size:15px;color:var(--ink)}
  .skptable thead th{font-size:11.5px;padding:10px 8px}
  .ra-txt{font-weight:600;color:var(--ink)}
  .rz-txt{color:var(--ink)}
  .tb-btn{padding:4px 11px;font-size:11.5px;border-radius:7px}
  /* ===== Status capaian: merah / biru / hijau / emas ===== */
  .st-merah{--sc:#ef4444;--scl:#fee2e2}
  .st-biru{--sc:#2563eb;--scl:#dbeafe}
  .st-hijau{--sc:#16a34a;--scl:#dcfce7}
  .st-emas{--sc:#d97706;--scl:#fef3c7}
  .st-none{--sc:#94a3b8;--scl:#f1f5f9}
  .bmini{margin-top:6px}
  .bmini-top{display:flex;justify-content:space-between;gap:6px;font-size:10.5px;font-weight:700;color:var(--muted);line-height:1.3}
  .bmini-lbl{text-transform:uppercase;letter-spacing:.3px}
  .bmini-val{color:var(--sc);white-space:nowrap}
  .bmini-bar{height:6px;border-radius:999px;background:var(--scl);overflow:hidden;margin-top:2px}
  .bmini-bar>i{display:block;height:100%;border-radius:999px;background:var(--sc);transition:width .4s ease}
  .stat-tile.st-merah,.stat-tile.st-biru,.stat-tile.st-hijau,.stat-tile.st-emas{border-color:var(--sc)}
  .stat-tile.st-merah .st-n,.stat-tile.st-biru .st-n,.stat-tile.st-hijau .st-n,.stat-tile.st-emas .st-n{color:var(--sc)}
  /* persentase di kartu bulan */
  .mc-pct{margin-top:6px;width:100%;padding:0 4px}
  .mc-pct .bmini{margin-top:0}
  .mc-badge{display:inline-block;font-size:10px;font-weight:800;padding:1px 8px;border-radius:999px;background:var(--scl);color:var(--sc);margin-top:4px}
  /* dinding warna status di kolom NO (setinggi blok RHK) */
  .skptable td.c-no{position:relative;font-size:15px;padding-left:14px}
  .skptable td.c-no.st-merah,.skptable td.c-no.st-biru,.skptable td.c-no.st-hijau,
  .skptable td.c-no.st-emas,.skptable td.c-no.st-none{
    background:var(--scl);color:var(--sc);border-left:6px solid var(--sc)}
  /* kolom bukti dukung */
  .c-rhk-nama{font-weight:600}
  .bd-ringkas{font-size:11.5px;font-weight:700;color:var(--brand-d);margin-bottom:5px}
  .bd-head{display:flex;justify-content:space-between;gap:6px;align-items:center}
  .bd-tgl{font-size:11.5px;font-weight:700;color:var(--ink2);white-space:nowrap}
  .bd-jml{font-size:10.5px;font-weight:800;padding:1px 7px;border-radius:999px;white-space:nowrap}
  .bd-jml.ada{background:var(--good-l);color:#166534}
  .bd-jml.kosong{background:var(--danger-l);color:#be123c}
  .bd-act{margin-top:3px}
  .tb-open{background:var(--brand)!important;color:#fff!important}
  .bd-files[hidden]{display:none!important}
  .bd-files{margin-top:5px;padding-top:5px;border-top:1px dashed var(--line);display:flex;flex-wrap:wrap;gap:4px 8px}
  .bd-files a{display:inline-block;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  /* ===== Tabel SKP (mirip formulir resmi) ===== */
  .skpwrap{overflow-x:auto;-webkit-overflow-scrolling:touch;border:1px solid var(--line);border-radius:12px;background:#fff;box-shadow:var(--sh-sm)}
  .skptable{border-collapse:collapse;width:100%;min-width:1180px;font-size:12.5px;line-height:1.45}
  .skptable th,.skptable td{border:1px solid #cbd5e1;padding:7px 8px;vertical-align:top;text-align:left}
  .skptable thead th{background:var(--brand-l);color:var(--brand-d);font-weight:800;font-size:11px;text-transform:uppercase;letter-spacing:.3px;text-align:center;vertical-align:middle;position:sticky;top:0;z-index:2}
  .skptable .grouprow td{background:#eef2f6;font-weight:800;font-size:12px;color:var(--ink2)}
  .skptable .kosong{color:var(--faint);font-style:italic;text-align:center;padding:12px}
  .skptable .c-no{text-align:center;font-weight:700;vertical-align:middle}
  .skptable .c-aspek{text-align:center;vertical-align:middle}
  .skptable .c-tgt{text-align:center;font-weight:700;vertical-align:middle}
  .skptable .c-iki,.skptable .c-rhk,.skptable .c-pimp{white-space:pre-wrap;word-break:break-word}
  .ra-txt{white-space:pre-wrap;word-break:break-word}
  .ra-tgt{margin-top:6px;font-size:12px}
  .ra-kosong{color:var(--danger);font-size:11.5px;margin-bottom:6px}
  .kosong-kecil{color:var(--faint);font-size:11.5px;font-style:italic}
  .tb-btn{display:inline-block;border:none;border-radius:6px;padding:3px 10px;font:inherit;font-size:11px;font-weight:700;color:#fff;cursor:pointer;margin:3px 3px 0 0}
  .tb-add{background:#16a34a}.tb-add:hover{filter:brightness(1.08)}
  .tb-edit{background:#f59e0b}.tb-edit:hover{filter:brightness(1.08)}
  .tb-del{background:#ef4444}.tb-del:hover{filter:brightness(1.08)}
  .tb-neutral{background:#64748b}.tb-neutral:hover{filter:brightness(1.12)}
  /* Kartu bukti dukung: dipisah garis, diberi dinding warna + nomor yang sama
     dengan realisasi pasangannya. */
  .bd-item{margin-top:8px;padding:7px 8px 7px 10px;border-left:4px solid var(--pc,var(--line));
    border-top:1px solid var(--line);border-right:1px solid var(--line);border-bottom:1px solid var(--line);
    border-radius:0 8px 8px 0;background:#fff}
  .bd-item:first-of-type{margin-top:4px}
  .bd-files{font-size:11.5px;word-break:break-word;margin-bottom:2px}
  .bd-files a{color:var(--brand-d);font-weight:600}
  .rz-item{margin-bottom:8px;padding:7px 8px 7px 10px;border-left:4px solid var(--pc,var(--line));
    border-top:1px solid var(--line);border-right:1px solid var(--line);border-bottom:1px solid var(--line);
    border-radius:0 8px 8px 0;background:#fff}
  /* nomor pasangan realisasi <-> bukti dukung */
  .pair-no{display:inline-flex;align-items:center;justify-content:center;min-width:17px;height:17px;
    padding:0 4px;margin-right:6px;border-radius:5px;background:var(--pc,#94a3b8);color:#fff;
    font-size:10.5px;font-weight:800;line-height:1;vertical-align:1px;flex:none}
  .rz-txt{margin-top:3px;white-space:pre-wrap;word-break:break-word}
  @media(max-width:860px){ .skptable{font-size:12px} }
  /* indikator bersarang di dalam baris RHK */
  .indwrap{margin-top:8px;padding:9px 10px;background:#fff;border:1px dashed var(--line);border-radius:10px}
  .indlbl{font-size:11.5px;font-weight:800;color:var(--brand-d);text-transform:uppercase;letter-spacing:.4px;margin-bottom:7px}
  .indlbl span{font-weight:600;color:var(--muted);text-transform:none;letter-spacing:0}
  .irow{display:flex;gap:6px;margin-bottom:6px;align-items:flex-start}
  .irow>select{max-width:112px;flex:none}
  .irow textarea{min-height:38px;flex:1}
  .irow .ji-target{max-width:96px;flex:none}
  /* baris aspek di modal rencana aksi */
  .arow{display:flex;gap:8px;margin-bottom:8px}
  .arow>select{max-width:120px;flex:none}
  .arow textarea{min-height:40px;flex:1}
  .arow .ar-x{flex:none;align-self:flex-start}
  .aspekcard .ic{background:transparent!important;width:auto;min-width:44px;padding:0}
  .ekinchip{padding-right:5px}
  .chip-x{background:none;border:none;color:var(--danger);font-weight:800;cursor:pointer;font-size:13px;padding:0 2px;margin-left:2px}
  .field{margin-bottom:14px}
  .drop{border:2px dashed #cbd5e1;border-radius:13px;padding:18px;text-align:center;cursor:pointer;transition:all .15s;background:#fbfdfe;color:var(--muted);font-size:13.5px}
  .drop:hover,.drop.over{border-color:var(--brand);background:var(--brand-l);color:var(--brand-d)}
  .drop .ic{font-size:25px;display:block;margin-bottom:3px}
  .filelist{margin-top:10px;display:flex;flex-direction:column;gap:6px;max-height:34vh;overflow-y:auto}
  .fileitem{display:flex;align-items:center;gap:8px;background:var(--bg);border:1px solid var(--line);border-radius:9px;padding:8px 11px;font-size:13px}
  .fileitem .nm{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .fileitem .sz{color:var(--muted);font-size:11.5px;flex:none}
  .fileitem .x{cursor:pointer;color:var(--danger);font-weight:800;background:none;border:none;font-size:15px;flex:none}
  .sectlabel{font-size:12px;font-weight:800;color:var(--faint);text-transform:uppercase;letter-spacing:1px;margin:24px 4px 12px;display:flex;align-items:center;gap:8px}
  .sectlabel .n{font-weight:600;color:var(--muted);text-transform:none;letter-spacing:0}
  .harian{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:14px 15px;margin-bottom:10px;box-shadow:var(--sh-sm)}
  .harian-top{display:flex;align-items:flex-start;gap:12px}
  .datechip{flex:none;text-align:center;background:var(--brand-l);border:1px solid var(--brand-ring);border-radius:11px;padding:7px 9px;min-width:52px}
  .datechip .d{font-size:19px;font-weight:800;line-height:1;color:var(--brand-d)}
  .datechip .m{font-size:10px;text-transform:uppercase;color:var(--muted);font-weight:700}
  .harian-body{flex:1;min-width:0}
  .harian-time{font-size:12px;color:var(--muted);font-weight:500}
  .harian-desc{font-size:14px;color:#1e293b;margin-top:4px;white-space:pre-wrap;word-break:break-word}
  .harian{border-left-width:4px}
  .harian.has-bukti{border-left-color:var(--good)}
  .harian.no-bukti{border-left-color:var(--danger)}
  .harian-foot{display:flex;align-items:center;gap:9px;margin-top:11px;flex-wrap:wrap}
  .bukti-badge{font-size:11.5px;font-weight:700;padding:3px 10px;border-radius:999px;white-space:nowrap}
  .bukti-badge.ok{background:#dcfce7;color:#15803d}
  .bukti-badge.no{background:var(--danger-l);color:#be123c}
  .addbukti{background:none;border:1px dashed var(--line);color:var(--brand-d);font:inherit;font-size:12px;font-weight:700;padding:4px 11px;border-radius:8px;cursor:pointer;transition:all .12s}
  .addbukti:hover{border-color:var(--brand);background:var(--brand-l)}
  .addbukti.dup{color:var(--muted)}
  .addbukti.dup:hover{border-color:var(--muted);background:var(--line2);color:var(--ink2)}
  .jml-pill{font-size:11.5px;font-weight:800;padding:3px 10px;border-radius:999px;background:#eef2ff;color:#4338ca;white-space:nowrap}
  .prog{margin-top:9px}
  .prog-bar{height:8px;background:var(--line);border-radius:999px;overflow:hidden}
  .prog-fill{height:100%;background:var(--brand);border-radius:999px;transition:width .4s ease}
  .prog-fill.done{background:var(--good)}
  .prog-fill.over{background:linear-gradient(90deg,var(--good),#2563eb)}
  .prog-tx{font-size:12px;color:var(--muted);margin-top:5px;font-weight:600}
  .prog-fill.miss{background:#ef4444}
  .prog-pct{color:var(--brand-d)}.prog-pct.done{color:var(--good)}.prog-pct.over{color:#2563eb}.prog-pct.miss{color:#dc2626}
  .statrow{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:16px}
  @media(max-width:560px){.statrow{grid-template-columns:repeat(2,1fr)}}
  .stat-tile{display:flex;align-items:center;gap:10px;background:var(--card);border:1px solid var(--line);border-radius:14px;padding:11px 13px;box-shadow:var(--sh-sm);text-decoration:none;color:inherit;transition:border-color .12s,box-shadow .12s}
  a.stat-tile:hover{border-color:var(--brand-ring);box-shadow:var(--sh-md)}
  .st-ic{width:36px;height:36px;border-radius:10px;display:grid;place-items:center;font-size:17px;flex:none}
  .st-n{font-size:18px;font-weight:800;line-height:1}.st-n small{font-size:11px;font-weight:600;color:var(--muted)}
  .st-l{font-size:11px;color:var(--muted);font-weight:600;margin-top:3px}
  .mini-btn{display:inline-flex;align-items:center;gap:5px;background:var(--bg);border:1px solid var(--line);border-radius:9px;padding:5px 11px;font:inherit;font-size:12px;font-weight:600;color:var(--ink2);cursor:pointer;text-decoration:none;transition:all .12s}
  .mini-btn:hover{border-color:var(--brand-ring);background:var(--brand-l);color:var(--brand-d)}
  .mini-btn.on{background:var(--brand);border-color:var(--brand);color:#fff}
  .mini-btn.no{color:#be123c;background:var(--danger-l);border-color:#fecdd3;cursor:default}
  .harian-bukti{margin-top:10px;padding-top:10px;border-top:1px dashed var(--line)}
  .thumb{display:inline-block;width:66px;height:66px;border-radius:10px;overflow:hidden;border:1px solid var(--line);background:var(--bg)}
  .thumb img{width:100%;height:100%;object-fit:cover;display:block}
  .thumb:hover{border-color:var(--brand)}
  .cal-nav{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}
  .cal-title{font-size:16px;font-weight:800}
  .cal-nav .iconbtn{font-size:18px}
  .cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:6px}
  .cal-head{margin-bottom:8px}
  .cal-hd{text-align:center;font-size:11px;font-weight:700;color:var(--faint);text-transform:uppercase;letter-spacing:.5px}
  .cal-cell{position:relative;aspect-ratio:1/1;border:1px solid var(--line);border-radius:10px;padding:6px;cursor:pointer;transition:border-color .12s,background .12s;min-height:42px}
  .cal-cell.empty{border:none;cursor:default}
  .cal-cell:not(.empty):hover{border-color:var(--brand)}
  .cal-cell.has{background:var(--brand-l);border-color:var(--brand-ring)}
  .cal-cell.today{outline:2px solid var(--brand);outline-offset:-2px}
  .cal-d{font-size:13px;font-weight:700;color:var(--ink2)}
  .cal-dot{position:absolute;right:4px;bottom:4px;background:var(--brand);color:#fff;font-size:10px;font-weight:800;min-width:16px;height:16px;border-radius:999px;display:grid;place-items:center;padding:0 4px}
  @media(max-width:520px){.cal-cell{padding:3px;min-height:36px;border-radius:8px}.cal-d{font-size:11.5px}.cal-grid{gap:4px}}
  .chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:9px}
  .chip{display:inline-flex;align-items:center;gap:6px;background:var(--brand-l);color:var(--brand-d);border:1px solid var(--brand-ring);border-radius:8px;padding:5px 9px;font-size:12.5px;font-weight:600;text-decoration:none;max-width:100%}
  .chip:hover{filter:brightness(.97)}
  .chip .nm{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:170px}

  .empty{text-align:center;padding:42px 20px;color:var(--muted)}
  .empty .ic{font-size:44px;display:block;margin-bottom:12px;opacity:.55}
  .empty h3{color:var(--ink2);font-weight:700;margin-bottom:5px;font-size:16px}

  #toast{position:fixed;left:50%;bottom:24px;transform:translateX(-50%) translateY(20px);background:var(--ink);color:#fff;padding:12px 20px;border-radius:12px;font-size:14px;font-weight:600;opacity:0;pointer-events:none;transition:all .25s;z-index:99;box-shadow:var(--sh-lg);max-width:90vw}
  #toast.show{opacity:1;transform:translateX(-50%) translateY(0)}
  #toast.err{background:var(--danger)}
  .modal-bg{position:fixed;inset:0;background:rgba(9,25,23,.55);display:none;place-items:center;z-index:80;padding:20px;backdrop-filter:blur(2px)}
  .modal-bg.show{display:grid}
  .modal{background:#fff;border-radius:18px;padding:24px;max-width:430px;width:100%;box-shadow:var(--sh-lg);max-height:90vh;overflow:auto}
  .modal h3{font-size:17px;margin-bottom:16px;font-weight:800}
  .modal p{font-size:14px;color:var(--muted);margin-bottom:18px}
  .modal .row{display:flex;gap:8px;justify-content:flex-end;margin-top:10px;
    position:sticky;bottom:-24px;background:#fff;padding:12px 0 2px;margin-bottom:-2px;z-index:2}
  /* ===== Responsif: HP / tablet / laptop ===== */
  @media(max-width:1100px){
    .skptable{min-width:1000px}
    .wrap{max-width:100%}
  }
  @media(max-width:860px){
    .wrap{padding:14px 12px 70px}
    .viewhead{flex-direction:column;align-items:stretch;gap:10px}
    .viewhead h2{font-size:19px}
    .viewhead .actions{flex-wrap:wrap}
    .viewhead .actions .btn{flex:1 1 auto;justify-content:center}
    .statrow{grid-template-columns:repeat(2,1fr);gap:8px}
    .stat-tile{padding:10px}
    .st-n{font-size:16px}
    .st-l{font-size:10.5px}
    .actions .btn{font-size:12.5px;padding:9px 12px}
    .crumbs{font-size:12.5px;padding:8px 11px}
    .skpwrap{border-radius:10px}
    .skptable{min-width:860px;font-size:12px}
    .skptable th,.skptable td{padding:7px 8px}
    .skptable thead th{font-size:10.5px}
    .modal{padding:18px;border-radius:14px}
    .tiphint{font-size:12px;padding:8px 11px}
    .sectlabel{margin:18px 2px 10px}
  }
  @media(max-width:560px){
    .wrap{padding:12px 10px 70px}
    .statrow{grid-template-columns:repeat(2,1fr)}
    .month-grid{grid-template-columns:repeat(2,1fr);gap:8px}
    .monthcard{min-height:132px;padding:9px}
    .mc-nm{font-size:14px}
    .skptable{min-width:760px;font-size:11.5px}
    .tb-btn{padding:4px 9px;font-size:11px}
    .modal-bg{padding:10px}
    .modal{max-width:100%!important;padding:16px}
    .modal h3{font-size:16px}
    .kr-line,.irow,.arow{flex-wrap:wrap}
    .kr-line>select,.irow>select,.arow>select,.irow .ji-target,.kr-line .kr-target{max-width:none;width:100%;flex:1 1 100%}
    .viewhead .actions .btn{width:100%}
    .backbtn{width:100%;justify-content:center}
  }
  /* geser tabel: beri isyarat bisa digulir */
  .skpwrap{position:relative}
  .skpwrap::after{content:'';position:sticky;right:0;top:0;display:block;width:22px;height:100%;
    background:linear-gradient(90deg,transparent,rgba(15,27,45,.06));pointer-events:none}
  .skphint{font-size:11.5px;color:var(--faint);margin:6px 2px 0;display:none}
  @media(max-width:1100px){.skphint{display:block}}
</style>
</head>
<body>
<div class="app">
  <!-- ===== Sidebar ===== -->
  <aside class="sidebar" id="sidebar">
    <div class="sb-brand" id="brandBtn" title="Klik untuk perkecil / perbesar menu">
      <div class="sb-logo">📋</div>
      <div class="sb-brandtx"><h1><?= e(APP_NAME) ?></h1><span><?= e(APP_SUBTITLE) ?></span></div>
    </div>
    <nav class="sb-nav">
      <div class="sb-label">Menu</div>
      <button class="navitem" data-nav="dashboard" title="Dashboard"><span class="ni-ic">📊</span><span class="ni-tx">Dashboard</span></button>
      <button class="navitem" data-nav="kegiatan" title="Kinerja (judul & RHK)"><span class="ni-ic">🎯</span><span class="ni-tx">Kinerja</span></button>
      <button class="navitem" data-nav="kalender" title="Kalender"><span class="ni-ic">📅</span><span class="ni-tx">Kalender</span></button>
    </nav>
    <div class="sb-spacer"></div>
    <div class="sb-user">
      <div class="sb-ava"><?= e(mb_substr(currentUserName(),0,1)) ?></div>
      <div class="un"><?= e(currentUserName()) ?></div>
      <a class="sb-out" href="profil.php" title="Akun / ganti password">⚙️</a>
      <a class="sb-out" href="logout.php" title="Keluar">⏻</a>
    </div>
  </aside>
  <div class="backdrop" id="backdrop"></div>

  <!-- ===== Content ===== -->
  <div class="content">
    <div class="topbar">
      <button class="hamb only-mobile" id="menuBtn" aria-label="Menu">☰</button>
      <div class="tb-brand only-mobile"><?= e(APP_NAME) ?></div>
      <div class="miniclock"><span class="mc-t js-time">--:--:--</span><span class="mc-d js-date">—</span></div>
    </div>
    <div class="wrap">
      <div class="crumbs" id="crumbs" hidden></div>
      <div id="view"><div class="card empty"><span class="ic">⏳</span><h3>Memuat...</h3></div></div>
    </div>
  </div>
</div>

<div id="muatbar"></div>
<div id="toast"></div>

<!-- Modal: SKP (wadah Tahunan/Bulanan + RHK) -->
<div class="modal-bg" id="mJudul"><div class="modal" style="max-width:560px;max-height:88vh;overflow:auto">
  <h3 id="judulTitle">➕ Buat SKP</h3>
  <div class="field"><label>Judul SKP</label><input type="text" id="judulNama" placeholder="mis. SKP Persandian 2026" autocomplete="off"></div>
  <div class="field"><label>Jenis</label>
    <select id="judulTipe"><option value="tahun">Tahunan (Januari–Desember)</option><option value="bulan">Bulanan (1 bulan)</option></select></div>
  <div class="field" id="judulTahunWrap"><label>Tahun</label><input type="number" id="judulTahun" min="2000" max="2100" placeholder="2026"></div>
  <div class="field" id="judulBulanWrap" hidden><label>Bulan</label><input type="month" id="judulBulan"></div>
  <div id="judulRhkWrap" style="border-top:1px solid var(--line);margin-top:6px;padding-top:12px">
    <div class="form-lbl">🎯 Rencana Hasil Kerja <span style="font-weight:600;color:var(--muted);text-transform:none;letter-spacing:0">(bisa &gt;1)</span></div>
    <div id="judulRhkList"></div>
    <button type="button" class="btn btn-ghost btn-sm" id="judulRhkAdd">➕ Tambah RHK</button>
    <div style="font-size:12px;color:var(--muted);margin-top:8px" id="judulRhkHint">RHK ini akan muncul di semua bulan (Januari–Desember). Bisa ditambah / diedit lagi nanti.</div>
  </div>
  <div class="field" id="judulEkinWrap" style="margin-top:14px"><label>Berkas Ekin Tahunan (opsional)</label>
    <div class="drop" id="judulEkinDrop"><span class="ic">📄</span>Klik / seret file Ekin (dari MyASN)<br><small>bisa ditambah nanti</small></div>
    <input type="file" id="judulEkinFile" multiple hidden><div class="filelist" id="judulEkinPending"></div></div>
  <div class="row"><button class="btn btn-ghost btn-sm" data-close="mJudul">Batal</button>
    <button class="btn btn-primary btn-sm" id="judulSave">Simpan</button></div>
</div></div>
<!-- Modal: RHK -->
<div class="modal-bg" id="mRhk"><div class="modal" style="max-width:480px;max-height:88vh;overflow:auto">
  <h3 id="rhkTitle">➕ Rencana Hasil Kerja</h3>
  <div class="field"><label>Jenis</label><select id="rhkKategori"><option value="utama">Utama</option><option value="tambahan">Tambahan</option></select></div>
  <div class="field"><label>RHK Pimpinan yang Diintervensi (opsional)</label>
    <textarea id="rhkPimpinan" placeholder="mis. Terselenggaranya Operasionalisasi Jaring Komunikasi Sandi..." style="min-height:50px"></textarea></div>
  <div class="field"><label>Rencana Hasil Kerja (RHK)</label>
    <textarea id="rhkNama" placeholder="mis. Mengelola dan memverifikasi akun TTE ASN..." style="min-height:50px"></textarea></div>
  <div class="field"><label>Target Tahunan</label><input type="text" inputmode="numeric" id="rhkTarget" placeholder="mis. 100"></div>
  <div class="field" id="rhkEkinWrap"><label>Berkas Ekin Tahunan (opsional)</label>
    <div class="drop" id="rhkEkinDrop"><span class="ic">📄</span>Klik / seret file Ekin Tahunan (dari MyASN)<br><small>bisa ditambah nanti</small></div>
    <input type="file" id="rhkEkinFile" multiple hidden><div class="filelist" id="rhkEkinPending"></div></div>
  <div class="row"><button class="btn btn-ghost btn-sm" data-close="mRhk">Batal</button>
    <button class="btn btn-primary btn-sm" id="rhkSave">Simpan</button></div>
</div></div>
<!-- Modal: Rencana Aksi -->
<div class="modal-bg" id="mRaksi"><div class="modal" style="max-width:500px;max-height:88vh;overflow:auto">
  <h3 id="raksiTitle">➕ Rencana Aksi</h3>
  <div class="field" id="raksiSalinWrap"><label>Salin dari bulan lain (opsional)</label>
    <select id="raksiSalin"><option value="0">— tidak, buat baru —</option></select>
    <div style="font-size:12px;color:var(--muted);margin-top:5px">Menyalin nama & aspek/IKI. Target dikosongkan; bukti & realisasi tidak ikut.</div></div>
  <div class="field" style="display:flex;gap:10px;margin-bottom:0">
    <div style="flex:1"><label>Bulan</label><select id="raksiBulan"></select></div>
    <div style="flex:1"><label>Target bulan</label><input type="text" inputmode="numeric" id="raksiTarget" placeholder="mis. 20"></div>
  </div>
  <div class="field" style="margin-top:14px"><label>Rencana Aksi (apa yang dikerjakan bulan ini)</label>
    <textarea id="raksiNama" placeholder="mis. Pendampingan pendaftaran & verifikasi akun TTE pada OPD..." style="min-height:54px"></textarea></div>
  <p style="font-size:12px;color:var(--muted);margin:2px 0 6px">Aspek & indikator mengikuti RHK — tidak perlu diisi lagi di sini.</p>
  <div class="field" id="raksiEkinWrap" style="margin-top:14px"><label>Berkas Ekin Bulanan (opsional)</label>
    <div class="drop" id="raksiEkinDrop"><span class="ic">📄</span>Klik / seret file Ekin Bulanan (dari MyASN)<br><small>bisa ditambah nanti</small></div>
    <input type="file" id="raksiEkinFile" multiple hidden><div class="filelist" id="raksiEkinPending"></div></div>
  <div class="row"><button class="btn btn-ghost btn-sm" data-close="mRaksi">Batal</button>
    <button class="btn btn-primary btn-sm" id="raksiSave">Simpan</button></div>
</div></div>
<!-- Modal: Kelola RHK (edit nama/kategori + indikator sekaligus) -->
<div class="modal-bg" id="mKelola"><div class="modal" style="max-width:560px;max-height:88vh;overflow:auto">
  <h3 id="kelolaTitle">⚙ Kelola RHK</h3>
  <div class="field" style="display:flex;gap:10px;margin-bottom:0">
    <div style="flex:1"><label>Jenis</label><select id="kelolaKat"><option value="utama">Utama</option><option value="tambahan">Tambahan</option></select></div>
  </div>
  <div class="field" style="margin-top:14px"><label>Rencana Hasil Kerja (RHK)</label>
    <textarea id="kelolaNama" style="min-height:52px"></textarea></div>
  <div id="kelolaIndWrap" style="border-top:1px solid var(--line);margin-top:6px;padding-top:12px">
    <div class="form-lbl">📐 Indikator Kinerja Individu <span style="font-weight:600;color:var(--muted);text-transform:none;letter-spacing:0">(bisa &gt;1, tiap indikator punya target tahunan)</span></div>
    <div id="kelolaIndList"></div>
    <button type="button" class="btn btn-ghost btn-sm" id="kelolaIndAdd">➕ Indikator</button>
  </div>
  <div class="field" style="margin-top:14px;background:var(--accent-l);border:1px solid #fde68a;border-radius:11px;padding:11px">
    <label style="color:var(--accent-ink)">Perubahan ini berlaku untuk:</label>
    <select id="kelolaCakupan">
      <option value="semua">Semua bulan (Januari–Desember)</option>
      <option value="bulan">Bulan ini saja</option>
    </select>
    <div style="font-size:12px;color:var(--accent-ink);margin-top:6px" id="kelolaCakupanHint">RHK dipakai bersama tiap bulan, jadi perubahan akan terlihat di semua bulan.</div>
  </div>
  <div class="row"><button class="btn btn-ghost btn-sm" data-close="mKelola">Batal</button>
    <button class="btn btn-sm" id="kelolaHapus" style="background:var(--danger);color:#fff">🗑️ Hapus RHK</button>
    <button class="btn btn-primary btn-sm" id="kelolaSave">Simpan</button></div>
</div></div>
<!-- Modal: Indikator (aspek + IKI + target tahunan) -->
<div class="modal-bg" id="mAspek"><div class="modal">
  <h3 id="aspekTitle">➕ Indikator Kinerja Individu</h3>
  <div class="field"><label>Aspek</label>
    <select id="aspekSel"><option value="kuantitas">Kuantitas</option><option value="kualitas">Kualitas</option><option value="waktu">Waktu</option></select></div>
  <div class="field"><label>Indikator Kinerja Individu (IKI)</label>
    <textarea id="aspekIki" placeholder="mis. Jumlah akun ASN TTE yang diverifikasi dan aktif" style="min-height:56px"></textarea></div>
  <div class="field"><label>Target Tahunan</label><input type="text" inputmode="numeric" id="aspekTarget" placeholder="mis. 100"></div>
  <div class="row"><button class="btn btn-ghost btn-sm" data-close="mAspek">Batal</button>
    <button class="btn btn-primary btn-sm" id="aspekSave">Simpan</button></div>
</div></div>
<!-- Modal: edit realisasi -->
<div class="modal-bg" id="mHarian"><div class="modal">
  <h3>✏️ Edit Realisasi</h3>
  <div class="field"><label>Realisasi (hasil yang dicapai)</label><textarea id="harUraian" style="min-height:90px"></textarea></div>
  <div class="field" style="display:flex;gap:10px;margin-bottom:0">
    <div style="flex:1"><label>Tanggal</label><input type="date" id="harTanggal"></div>
    <div style="flex:1"><label>Jumlah / volume</label><input type="text" inputmode="numeric" id="harJumlah" placeholder="mis. 3"></div></div>
  <p style="font-size:12px;color:var(--muted);margin:8px 0 12px">Untuk mengubah lampiran: hapus realisasi ini lalu buat ulang.</p>
  <div class="row"><button class="btn btn-ghost btn-sm" data-close="mHarian">Batal</button>
    <button class="btn btn-primary btn-sm" id="harSave">Simpan</button></div>
</div></div>
<!-- Modal: buat realisasi baru -->
<div class="modal-bg" id="mCatatan"><div class="modal">
  <h3>✍️ Realisasi Baru</h3>
  <div class="field"><label>Realisasi (hasil yang kamu capai)</label>
    <textarea id="catUraian" placeholder="mis. 22 akun baru yang terverifikasi berdasarkan DATA..."></textarea></div>
  <div class="field" style="display:flex;gap:10px;margin-bottom:6px">
    <div style="flex:1"><label>Tanggal</label><input type="date" id="catTanggal"></div>
    <div style="flex:1"><label>Jumlah / volume</label><input type="text" inputmode="numeric" id="catJumlah" placeholder="mis. 3" value="1"></div></div>
  <div style="font-size:12px;color:var(--muted);margin:0 0 12px">Tanggal boleh diubah (isi mundur bila telat, atau maju bila sudah direncanakan). Angka dijumlahkan menuju target.</div>
  <div class="field"><label>Bukti dukung (opsional, boleh &gt;1)</label>
    <div class="drop" id="catDrop"><span class="ic">📎</span>Klik atau seret berkas ke sini<br>
      <small>Untuk "<b id="catSubNama"></b>" — maks <?= (int)MAX_UPLOAD_MB ?> MB/berkas</small></div>
    <input type="file" id="catFile" multiple hidden><div class="filelist" id="catPending"></div></div>
  <div class="row"><button class="btn btn-ghost btn-sm" data-close="mCatatan">Batal</button>
    <button class="btn btn-primary btn-sm" id="catSave">💾 Simpan Realisasi</button></div>
</div></div>
<!-- Modal: lihat bukti dukung -->
<div class="modal-bg" id="mLihatBukti"><div class="modal" style="max-width:580px;max-height:88vh;overflow:auto">
  <h3>📎 Bukti Dukung</h3>
  <p id="lbSub" style="font-size:13px;color:var(--muted);margin-bottom:14px"></p>
  <div id="lbList" class="chips"></div>
  <div class="row"><button class="btn btn-ghost btn-sm" data-close="mLihatBukti">Tutup</button>
    <button class="btn btn-soft btn-sm" id="lbUnduh">⤓ Unduh semua (ZIP)</button></div>
</div></div>
<!-- Modal: tambah bukti -->
<div class="modal-bg" id="mBukti"><div class="modal">
  <h3>📎 Tambah Bukti Dukung</h3>
  <p style="margin-bottom:14px">Menambahkan berkas ke catatan tanggal <b id="bkTgl"></b>.</p>
  <div class="field">
    <div class="drop" id="bkDrop"><span class="ic">📎</span>Klik atau seret berkas ke sini<br>
      <small>Foto, PDF, dll. — maks <?= (int)MAX_UPLOAD_MB ?> MB/berkas</small></div>
    <input type="file" id="bkFile" multiple hidden><div class="filelist" id="bkPending"></div></div>
  <div class="row"><button class="btn btn-ghost btn-sm" data-close="mBukti">Batal</button>
    <button class="btn btn-primary btn-sm" id="bkSave">⬆️ Unggah</button></div>
</div></div>
<!-- Modal: tambah berkas Ekin -->
<div class="modal-bg" id="mEkin"><div class="modal">
  <h3 id="ekinTitle">📄 Tambah Berkas Ekin</h3>
  <p style="margin-bottom:12px" id="ekinDesc"></p>
  <div class="field"><div class="drop" id="ekinDrop"><span class="ic">📄</span>Klik / seret file Ekin ke sini<br>
    <small>Foto/PDF hasil dari MyASN — maks <?= (int)MAX_UPLOAD_MB ?> MB/berkas</small></div>
    <input type="file" id="ekinFile" multiple hidden><div class="filelist" id="ekinPending"></div></div>
  <div class="row"><button class="btn btn-ghost btn-sm" data-close="mEkin">Batal</button>
    <button class="btn btn-primary btn-sm" id="ekinSave">⬆️ Unggah</button></div>
</div></div>
<!-- Modal: konfirmasi -->
<div class="modal-bg" id="mConfirm"><div class="modal">
  <h3 id="cfTitle">Hapus?</h3><p id="cfMsg"></p>
  <div class="row"><button class="btn btn-ghost btn-sm" data-close="mConfirm">Batal</button>
    <button class="btn btn-sm" id="cfOk" style="background:var(--danger);color:#fff">Hapus</button></div>
</div></div>

<script>
"use strict";
const CSRF=<?= json_encode($CSRF) ?>;
const BULAN=["Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
const BULAN_S=["Jan","Feb","Mar","Apr","Mei","Jun","Jul","Agu","Sep","Okt","Nov","Des"];
const HARI=["Minggu","Senin","Selasa","Rabu","Kamis","Jumat","Sabtu"];
const $=s=>document.querySelector(s);
const $$=s=>document.querySelectorAll(s);

/* ---------- Jam WIB ---------- */
function wibParts(){const p=x=>String(x).padStart(2,'0');const f=new Intl.DateTimeFormat('en-CA',{timeZone:'Asia/Jakarta',year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:false});const o={};f.formatToParts(new Date()).forEach(x=>o[x.type]=x.value);o.dow=new Date(o.year+'-'+o.month+'-'+o.day+'T00:00:00').getDay();return o;}
function tick(){const o=wibParts();const ds=`${HARI[o.dow]}, ${+o.day} ${BULAN[+o.month-1]} ${o.year}`;const ts=`${o.hour}:${o.minute}:${o.second}`;$$('.js-date').forEach(e=>e.textContent=ds);$$('.js-time').forEach(e=>e.textContent=ts);}
tick();setInterval(tick,1000);
function nowMonthWIB(){const o=wibParts();return o.year+'-'+o.month;}
function todayISO(){const o=wibParts();return o.year+'-'+o.month+'-'+o.day;}
function greeting(){const h=+wibParts().hour;return h<11?'Selamat pagi':h<15?'Selamat siang':h<18?'Selamat sore':'Selamat malam';}

/* ---------- Util ---------- */
function toast(m,err){const t=$('#toast');t.textContent=m;t.className='show'+(err?' err':'');clearTimeout(t._t);t._t=setTimeout(()=>t.className='',2800);}
function esc(s){return String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
function fmtSize(b){if(b<1024)return b+' B';if(b<1048576)return (b/1024).toFixed(1)+' KB';return (b/1048576).toFixed(1)+' MB';}
function monthLabel(k){const [y,m]=k.split('-');return `${BULAN[+m-1]} ${y}`;}
function monthShort(k){const [y,m]=k.split('-');return BULAN_S[+m-1];}
function parseWIB(s){const [d,t]=s.split(' ');const [y,mo,da]=d.split('-').map(Number);const [h,mi]=t.split(':').map(Number);return {y,mo:mo-1,da,h,mi,dow:new Date(y,mo-1,da).getDay()};}
function fileIcon(type,name){const n=(name||'').toLowerCase();type=type||'';
  if(type.startsWith('image/'))return '🖼️';if(type.includes('pdf')||n.endsWith('.pdf'))return '📕';
  if(n.match(/\.(docx?|odt)$/))return '📘';if(n.match(/\.(xlsx?|csv|ods)$/))return '📗';
  if(n.match(/\.(pptx?|odp)$/))return '📙';if(n.match(/\.(zip|rar|7z)$/))return '🗜️';return '📄';}
/* Garis progres tipis di atas layar: muncul kalau permintaan lebih dari 120 ms,
   supaya perpindahan menu tidak terasa "diam" saat database lambat. */
let _sibuk=0,_barT=null;
function mulaiSibuk(){ _sibuk++; if(_sibuk===1&&!_barT) _barT=setTimeout(()=>document.body.classList.add('memuat'),120); }
function selesaiSibuk(){ _sibuk=Math.max(0,_sibuk-1); if(!_sibuk){ clearTimeout(_barT); _barT=null; document.body.classList.remove('memuat'); } }
async function getJSON(url,opts){
  mulaiSibuk();
  try{
    const r=await fetch(url,opts);
    if(r.status===401){location.href='login.php';throw new Error('login');}
    const j=await r.json();
    if(!j.ok)throw new Error(j.error||'Gagal');
    return j;
  } finally { selesaiSibuk(); }
}
function fd(obj){const f=new FormData();f.append('csrf',CSRF);for(const k in obj)f.append(k,obj[k]);return f;}
function showError(msg){$('#view').innerHTML=`<div class="card empty"><span class="ic">⚠️</span><h3>Terjadi kesalahan</h3><p>${esc(msg)}</p></div>`;}

/* ---------- Navigation shell ---------- */
function setNav(name){$$('.navitem').forEach(a=>a.classList.toggle('active',a.dataset.nav===name));}
function closeDrawer(){document.body.classList.remove('nav-open');}
$('#menuBtn').onclick=()=>document.body.classList.toggle('nav-open');
$('#backdrop').onclick=closeDrawer;
$$('.navitem').forEach(a=>a.onclick=()=>{closeDrawer();const n=a.dataset.nav;n==='dashboard'?goDashboard():n==='kalender'?goKalender():goHome();});
function showCrumbs(items){const c=$('#crumbs');if(!items){c.hidden=true;return;}c.hidden=false;
  c.innerHTML=items.map((it,i)=>{const last=i===items.length-1;return (last?`<span class="cur">${esc(it.label)}</span>`:`<a data-nav="${i}">${esc(it.label)}</a>`)+(last?'':'<span class="sep">›</span>');}).join('');
  c.querySelectorAll('[data-nav]').forEach(a=>a.onclick=()=>items[+a.dataset.nav].go());}

let state={view:'dashboard'};
/* --- Alamat halaman (biar refresh tetap di tempat) --- */
let _hashDiam=false;
function setHash(h){ try{ _hashDiam=true; if(location.hash!=='#'+h) history.replaceState(null,'','#'+h); }finally{ setTimeout(()=>_hashDiam=false,0); } }
function bacaHash(){ const h=(location.hash||'').replace(/^#/,''); if(!h) return null;
  const p={}; h.split('&').forEach(x=>{const[a,b]=x.split('=');p[a]=b===undefined?true:decodeURIComponent(b);}); return p; }
async function bukaDariHash(){ const p=bacaHash();
  try{
    if(!p) { goDashboard(); return; }
    if(p.kalender!==undefined) { goKalender(p.bulan||undefined); return; }
    if(p.raksi) { await openRaksi(+p.raksi); return; }
    if(p.rhk)   { await openRhk(+p.rhk); return; }
    if(p.skp && p.bulan) { await openSkpBulan(+p.skp,+p.bulan); return; }
    if(p.skp!==undefined && p.skp!==true) { await openJudul(+p.skp); return; }
    if(p.daftar!==undefined) { goHome(); return; }
    goDashboard();
  }catch(e){ goDashboard(); }
}
window.addEventListener('hashchange',()=>{ if(!_hashDiam) bukaDariHash(); });

/* ================= DASHBOARD ================= */
async function goDashboard(){
  state={view:'dashboard'}; setNav('dashboard'); showCrumbs(null); setHash('dashboard');
  try{ const j=await getJSON('api.php?action=dashboard'); renderDashboard(j); }
  catch(e){ if(e.message!=='login')showError(e.message); }
}
function barChart(series){
  const W=560,H=210,pl=32,pr=12,pt=16,pb=28, plotW=W-pl-pr, plotH=H-pt-pb;
  const max=Math.max(...series.map(s=>s.count),0);
  const nice=max<=4?4:Math.ceil(max/4)*4;
  const slot=plotW/series.length, bw=Math.min(42,slot*0.5), lines=4;
  const path=(x,y,w,h,r)=>{r=Math.min(r,h,w/2);return `M${x},${y+h} L${x},${y+r} Q${x},${y} ${x+r},${y} L${x+w-r},${y} Q${x+w},${y} ${x+w},${y+r} L${x+w},${y+h} Z`;};
  let g='',b='';
  for(let i=0;i<=lines;i++){const val=Math.round(nice*i/lines),y=pt+plotH-(i/lines)*plotH;
    g+=`<line x1="${pl}" y1="${y}" x2="${W-pr}" y2="${y}" class="gl"/><text x="${pl-7}" y="${y+3}" class="yl">${val}</text>`;}
  series.forEach((s,i)=>{const cx=pl+slot*i+slot/2,bh=nice?(s.count/nice)*plotH:0,y=pt+plotH-bh;
    if(s.count>0)b+=`<g class="bar" data-lbl="${monthLabel(s.ym)}" data-val="${s.count}"><path d="${path(cx-bw/2,y,bw,bh,4)}" class="brk"/><text x="${cx}" y="${y-6}" class="vl">${s.count}</text></g>`;
    b+=`<text x="${cx}" y="${H-9}" class="xl">${monthShort(s.ym)}</text>`;});
  return `<svg viewBox="0 0 ${W} ${H}" class="barchart" preserveAspectRatio="xMidYMid meet" role="img" aria-label="Grafik catatan harian per bulan">${g}${b}</svg>`;
}
function renderDashboard(j){
  const t=j.totals, bi=j.bulan_ini, v=$('#view');
  let html=`<div class="hero">
      <div><h2>${greeting()}, ${esc(j.user)} 👋</h2><div class="hd js-date">—</div></div>
      <div class="hero-emo">📈</div>
    </div>`;
  html+=`<div class="kpi-grid">
    ${kpi('ic-teal','🎯',t.rhk,'RHK')}
    ${kpi('ic-blue','🗓️',t.raksi,'Rencana Aksi')}
    ${kpi('ic-violet','📝',t.realisasi,'Realisasi')}
    ${kpi('ic-amber','🗂️',t.berkas,'Berkas')}
  </div>`;
  html+=`<div class="dash-2col">
    <div class="card pad">
      <div class="panel-h"><h3>Aktivitas 6 Bulan Terakhir</h3><span class="hint">realisasi / bulan</span></div>
      <div id="chartWrap">${barChart(j.series)}<div id="chartTip"></div></div>
    </div>
    <div class="card pad">
      <div class="panel-h"><h3>Bulan Ini</h3><span class="hint">${monthLabel(bi.ym)}</span></div>
      <div class="mini"><div class="mic ic-blue">🗓️</div><div><div class="mn">${bi.raksi}</div><div class="ml">Rencana Aksi</div></div></div>
      <div class="mini"><div class="mic ic-violet">📝</div><div><div class="mn">${bi.realisasi}</div><div class="ml">Realisasi</div></div></div>
      <div class="mini"><div class="mic ic-amber">🗂️</div><div><div class="mn">${bi.berkas}</div><div class="ml">Berkas diunggah</div></div></div>
      <button class="btn btn-primary btn-block btn-sm" id="dashAddKeg" style="margin-top:16px">➕ Buat SKP</button>
    </div>
  </div>`;
  if(j.tahunan && j.tahunan.length){
    html+=`<div class="card pad" style="margin-bottom:20px"><div class="panel-h"><h3>🎯 Capaian Tahunan</h3><span class="hint">total ÷ target</span></div>`;
    j.tahunan.forEach(t=>{html+=`<button class="feed-item" data-rhk="${t.id}" style="align-items:center">
        <div class="fdate" style="background:var(--brand-l)"><div class="d" style="font-size:13px">${t.target>0?Math.min(100,Math.round(t.capaian/t.target*100)):0}%</div></div>
        <div class="fbody"><div class="ft">${esc(t.nama)} <span style="color:var(--muted);font-weight:600">· ${t.kategori==='tambahan'?'Tambahan':'Utama'} ${t.tahun}</span></div>${progressHTML(t.capaian,t.target,'')}</div></button>`;});
    html+=`</div>`;
  }
  html+=`<div class="card pad">
    <div class="panel-h"><h3>Aktivitas Terbaru</h3><span class="hint">${j.recent.length?'klik untuk membuka':''}</span></div>`;
  if(!j.recent.length){
    html+=`<div class="empty" style="padding:26px"><span class="ic" style="font-size:34px">🗒️</span><p>Belum ada realisasi. Mulai dari menu <b>RHK</b>.</p></div>`;
  }else{
    html+='<div class="feed">';
    j.recent.forEach(r=>{const w=parseWIB(r.created_at);
      html+=`<button class="feed-item" data-raksi="${r.raksi_id}">
        <div class="fdate"><div class="d">${w.da}</div><div class="m">${BULAN_S[w.mo]}</div></div>
        <div class="fbody"><div class="ft">${esc(r.uraian||'(tanpa uraian)')}</div>
          <div class="fm"><span class="tagpill">🎯 ${esc(r.rhk_nama)}</span>${r.files?`<span>· 📎 ${r.files}</span>`:''}</div></div>
      </button>`;});
    html+='</div>';
  }
  html+='</div>';
  v.innerHTML=html; tick();
  $('#dashAddKeg').onclick=openAddJudul;
  v.querySelectorAll('.feed-item[data-rhk]').forEach(b=>b.onclick=()=>openRhk(+b.dataset.rhk));
  v.querySelectorAll('.feed-item[data-raksi]').forEach(b=>b.onclick=()=>openRaksi(+b.dataset.raksi));
  // hover chart
  const tip=$('#chartTip'),wrap=$('#chartWrap');
  v.querySelectorAll('.bar').forEach(gp=>{
    gp.addEventListener('mouseenter',()=>{tip.textContent=`${gp.dataset.lbl}: ${gp.dataset.val} realisasi`;tip.style.opacity=1;});
    gp.addEventListener('mousemove',e=>{const r=wrap.getBoundingClientRect();tip.style.left=(e.clientX-r.left)+'px';tip.style.top=(e.clientY-r.top-6)+'px';});
    gp.addEventListener('mouseleave',()=>{tip.style.opacity=0;});
  });
}
function kpi(cls,ic,num,lbl){return `<div class="kpi"><div class="kic ${cls}">${ic}</div><div class="knum">${num}</div><div class="klbl">${lbl}</div></div>`;}
function progressHTML(cap,tgt,sat,fase){const c=+cap||0,t=+tgt||0,pct=t>0?Math.round(c/t*100):0,bar=Math.min(100,pct),over=(t>0&&c>t),done=(t>0&&c>=t);
  // periode yang sudah lewat & belum sampai target -> merah (bukan "sedang berjalan")
  const lewatWaktu=(fase==='lalu'&&t>0&&c<t);
  const cls=over?'over':(done?'done':(lewatWaktu?'miss':''));
  const ket=over?' • lewat target 🎯':(done?' • tercapai ✅':(lewatWaktu?(c>0?' • tidak tercapai':' • tidak dikerjakan'):''));
  return `<div class="prog"><div class="prog-bar"><div class="prog-fill ${cls}" style="width:${bar}%"></div></div>
    <div class="prog-tx"><b>${c}</b> / ${t} ${esc(sat||'')} <span class="prog-pct ${cls}">${pct}%${ket}</span></div></div>`;}

/* ================= KALENDER ================= */
let calMonth=null;
const HARI_H=['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
async function goKalender(m){
  state={view:'kalender'}; setNav('kalender'); showCrumbs(null);
  calMonth = m || calMonth || nowMonthWIB(); setHash('kalender&bulan='+calMonth);
  try{ const j=await getJSON('api.php?action=calendar&month='+calMonth); calMonth=j.month; renderCalendar(j); }
  catch(e){ if(e.message!=='login')showError(e.message); }
}
function shiftMonth(ym,delta){let [y,m]=ym.split('-').map(Number);m+=delta;if(m<1){m=12;y--;}if(m>12){m=1;y++;}return y+'-'+String(m).padStart(2,'0');}
function renderCalendar(j){
  const v=$('#view'); const [y,m]=j.month.split('-').map(Number);
  const byDay={}; j.items.forEach(it=>{const d=+it.created_at.slice(8,10);(byDay[d] ||=[]).push(it);});
  const first=new Date(y,m-1,1).getDay(), dim=new Date(y,m,0).getDate();
  const tw=wibParts(), isThis=(+tw.year===y&&+tw.month===m), today=+tw.day;
  let cells='';
  for(let i=0;i<first;i++)cells+=`<div class="cal-cell empty"></div>`;
  for(let d=1;d<=dim;d++){const items=byDay[d]||[];
    cells+=`<div class="cal-cell${items.length?' has':''}${isThis&&d===today?' today':''}" data-day="${d}"><div class="cal-d">${d}</div>${items.length?`<div class="cal-dot">${items.length}</div>`:''}</div>`;}
  let html=`<div class="page-h"><h2>Kalender</h2><div class="sub">Catatan kegiatan per tanggal — angka = jumlah catatan hari itu</div></div>`;
  html+=`<div class="card pad">
    <div class="cal-nav"><button class="iconbtn" id="calPrev" title="Bulan sebelumnya">‹</button>
      <div class="cal-title">${monthLabel(j.month)}</div>
      <button class="iconbtn" id="calNext" title="Bulan berikutnya">›</button></div>
    <div class="cal-grid cal-head">${HARI_H.map(h=>`<div class="cal-hd">${h}</div>`).join('')}</div>
    <div class="cal-grid">${cells}</div></div>`;
  html+=`<div id="calDetail"></div>`;
  v.innerHTML=html;
  $('#calPrev').onclick=()=>goKalender(shiftMonth(j.month,-1));
  $('#calNext').onclick=()=>goKalender(shiftMonth(j.month,1));
  v.querySelectorAll('[data-day]').forEach(c=>{if(c.classList.contains('empty'))return;c.onclick=()=>showCalDay(byDay[+c.dataset.day]||[],+c.dataset.day,j.month);});
  if(isThis && byDay[today]) showCalDay(byDay[today],today,j.month);
}
function showCalDay(items,day,ym){
  const [y,m]=ym.split('-').map(Number), dow=HARI[new Date(y,m-1,day).getDay()];
  let html=`<div class="sectlabel">📌 ${dow}, ${day} ${monthLabel(ym)} <span class="n">(${items.length} realisasi)</span></div>`;
  if(!items.length){html+=`<div class="card empty" style="padding:22px"><span class="ic" style="font-size:30px">🗓️</span><p>Tidak ada realisasi di tanggal ini.</p></div>`;}
  else{html+='<div class="card pad"><div class="feed">'+items.map(it=>{const w=parseWIB(it.created_at),p=x=>String(x).padStart(2,'0');
    return `<button class="feed-item" data-raksi="${it.raksi_id}"><div class="fdate"><div class="d">${p(w.h)}</div><div class="m">${p(w.mi)}</div></div>
      <div class="fbody"><div class="ft">${esc(it.uraian||'(tanpa uraian)')}</div>
        <div class="fm"><span class="tagpill">🎯 ${esc(it.rhk_nama)}</span>${it.files?`<span>· 📎 ${it.files}</span>`:''}</div></div></button>`;}).join('')+'</div></div>';}
  $('#calDetail').innerHTML=html;
  $('#calDetail').querySelectorAll('.feed-item').forEach(b=>b.onclick=()=>openRaksi(+b.dataset.raksi));
}

/* ================= DAFTAR JUDUL (Tahunan / Bulanan) ================= */
let curJudul=null, curRhk=null, curRaksi=null, curRhkRaksi=[], curBulanKe=0;
const unlockedBulan=new Set();   // dibuka manual
const lockedBulan=new Set();     // dikunci manual (mis. bulan berjalan sengaja dikunci)
function kunciKey(skpId,bk){return skpId+'-'+bk;}
function isBulanBerjalan(bk,tahun){const t=wibParts();return (+t.month===+bk)&&(!tahun||+t.year===+tahun);}
function isBulanTerbuka(skpId,bk,tahun){
  const key=kunciKey(skpId,bk);
  if(lockedBulan.has(key)) return false;
  if(unlockedBulan.has(key)) return true;
  return isBulanBerjalan(bk,tahun);           // default: bulan berjalan terbuka
}
function bukaBulan(skpId,bk){const key=kunciKey(skpId,bk);unlockedBulan.add(key);lockedBulan.delete(key);}
function kunciBulan(skpId,bk){const key=kunciKey(skpId,bk);lockedBulan.add(key);unlockedBulan.delete(key);}
/* daftar RHK satu bulan, tampil inline di bawah grid bulan */
async function goHome(){
  state={view:'judul-list'}; setNav('kegiatan'); setHash('daftar');
  try{ const j=await getJSON('api.php?action=judul_list'); showCrumbs([{label:'🎯 Kinerja'}]); renderJudulList(j.judul); }
  catch(e){ if(e.message!=='login')showError(e.message); }
}
function renderJudulList(list){
  const v=$('#view');
  let html=`<div class="viewhead"><div><h2>Kinerja</h2><div class="meta">${list.length} SKP</div></div>
    <div class="actions"><button class="btn btn-primary btn-sm" id="addJudul">➕ Buat SKP</button></div></div>`;
  html+=`<div class="tiphint">📁 Buat <b>SKP</b> (Tahunan / Bulanan) beserta <b>RHK</b>-nya. Buka SKP → Januari–Desember → tiap bulan berisi RHK tadi.</div>`;
  if(!list.length){
    html+=`<div class="card empty"><span class="ic">📁</span><h3>Belum ada SKP</h3><p>Klik <b>➕ Buat SKP</b>, pilih Tahunan atau Bulanan, lalu isi RHK-nya.</p></div>`;
  }else{
    list.forEach(k=>{const isY=k.tipe!=='bulan';
      html+=`<div class="navcard" data-judul="${k.id}"><div class="ic">${isY?'📁':'📅'}</div>
      <div class="body"><h4>${esc(k.nama)}<span class="ktipe ${isY?'y':'b'}">${isY?'Tahunan '+k.tahun:'Bulanan'}</span></h4>
        <div class="meta">${isY?'Januari–Desember '+k.tahun:monthLabel(k.bulan)} · ${k.jml_rhk} RHK</div>${k.target>0?progressHTML(k.capaian,k.target,'',isY?faseTahun(k.tahun):faseBulan(k.tahun,+(String(k.bulan||'').slice(5,7))||0)):''}</div>
      <div class="ctl"><button class="iconbtn edit" data-editjudul="${k.id}" title="Edit">✏️</button>
        <button class="iconbtn" data-deljudul="${k.id}" data-nama="${esc(k.nama)}" title="Hapus">🗑️</button></div>
      <button class="itemlock" data-lockitem title="Buka kunci (edit / hapus)">⚙️</button>
      <div class="chev">›</div></div>`;});
  }
  v.innerHTML=html;
  $('#addJudul').onclick=openAddJudul;
  v.querySelectorAll('[data-judul]').forEach(c=>c.onclick=e=>{if(e.target.closest('.ctl')||e.target.closest('.itemlock'))return;openJudul(+c.dataset.judul);});
  v.querySelectorAll('[data-editjudul]').forEach(b=>b.onclick=e=>{e.stopPropagation();openEditJudul(list.find(x=>x.id==b.dataset.editjudul));});
  v.querySelectorAll('[data-deljudul]').forEach(b=>b.onclick=e=>{e.stopPropagation();
    askConfirm('Hapus SKP?',`"<b>${esc(b.dataset.nama)}</b>" beserta semua RHK, rencana aksi, realisasi & bukti akan dihapus permanen.`,async()=>{
      await getJSON('api.php?action=judul_delete',{method:'POST',body:fd({id:b.dataset.deljudul})});toast('SKP dihapus');goHome();});});
  wireItemLocks(v);
}

/* ================= SKP → 12 BULAN (tahunan) / langsung bulan (bulanan) ================= */
async function openJudul(id){
  setNav('kegiatan');
  try{ const j=await getJSON('api.php?action=judul_get&id='+id); curJudul=j.judul; curRhk=null; curRaksi=null;
    if(j.judul.tipe==='bulan'){ openSkpBulan(id,j.judul.bulan_ke||(+wibParts().month)); return; }
    state={view:'judul',judulId:id}; setHash('skp='+id);
    showCrumbs([{label:'🎯 Kinerja',go:goHome},{label:j.judul.nama}]);
    renderJudul(j);
  }catch(e){ if(e.message!=='login')showError(e.message); }
}
function renderJudul(j){
  const k=j.judul,list=j.rhk,months=j.bulanan||[],v=$('#view');
  const tw=wibParts(); const curBk=(+tw.year===k.tahun)?+tw.month:0;
  let html=`<button class="btn btn-ghost btn-sm backbtn" id="backBtn">← Daftar Kinerja</button>`;
  html+=`<div class="viewhead"><div><h2>📁 ${esc(k.nama)}</h2><div class="meta">Tahunan · Januari–Desember ${k.tahun} · ${list.length} RHK</div></div>
      <div class="actions"><button class="btn btn-ghost btn-sm" id="addRhk">➕ RHK</button></div></div>`;
  html+=`<div class="actions" style="margin-bottom:12px">
    <button class="btn btn-soft btn-sm" data-unduh="zip.php?judul=${k.id}" data-unduhlbl="semua bukti dukung 1 tahun (Januari–Desember)">⤓ Unduh semua bukti 1 tahun</button>
    <button class="btn btn-ghost btn-sm" data-unduh="zip.php?judul=${k.id}&ekin=1" data-unduhlbl="semua berkas Ekin">📄 Unduh berkas Ekin</button></div>`;
  html+=ekinBarSkp(k.id,j.ekin||[]);
  html+=`<div class="sectlabel">📅 Bulan <span class="n" style="text-transform:none;letter-spacing:0">(klik bulan untuk membuka hasil kerjanya · 🔒 = terkunci, bisa dibuka di dalam)</span></div><div class="month-grid">`;
  const rhkN=list.length;
  months.forEach(mo=>{const bk=mo.bulan_ke, terbuka=isBulanTerbuka(k.id,bk,k.tahun);
    const fs=faseBulan(k.tahun,bk);
    const st=statusCapaian(mo.capaian,mo.target,fs);
    // deteksi "belum lengkap" untuk bulan berjalan/lampau
    const perluTgt=(mo.jml_raksi<rhkN)||(mo.jml_raksi>0&&(+mo.target||0)<=0);
    const perluReal=(mo.jml_raksi>0&&(+mo.jml_realisasi||0)===0);
    const perluBukti=(+mo.tanpa_bukti||0)>0;
    const belumLengkap=(rhkN>0&&fs!=='depan'&&(perluTgt||perluReal||perluBukti));
    const ket=[]; if(perluTgt)ket.push('target/rencana aksi belum lengkap'); if(perluReal)ket.push('belum ada realisasi'); if(perluBukti)ket.push(mo.tanpa_bukti+' realisasi tanpa bukti dukung');
    html+=`<div class="monthcard ${st.kelas}${bk===curBk?' current':''}" data-open="${bk}">
      <div class="mc-nm">${BULAN[bk-1]}</div>
      <div class="mc-meta">${rhkN} RHK${mo.jml_bukti?` · ${mo.jml_bukti} bukti`:''}</div>
      <div class="mc-pct">${barMini(mo.capaian,mo.target,'Capaian',fs)}</div>
      <span class="mc-badge">${mo.target>0?st.label:'belum ada target'}</span>
      ${bk===curBk?'<span class="mc-now">berjalan</span>':(fs==='lalu'?'<span class="mc-done">selesai</span>':'')}
      ${belumLengkap?`<span class="mc-warn" title="Belum lengkap: ${ket.join(', ')}">⚠️</span>`:''}
      <span class="mc-gembok" title="${terbuka?'Terbuka untuk diedit':'Terkunci'}">${terbuka?'🔓':'🔒'}</span></div>`;});
  html+=`</div>`;
  if(!list.length) html+=`<div class="card empty" style="margin-top:16px"><span class="ic">🎯</span><h3>Belum ada RHK</h3><p>Klik <b>➕ RHK</b> untuk menambah — RHK akan muncul di semua bulan.</p></div>`;
  v.innerHTML=html;
  $('#backBtn').onclick=goHome;
  $('#addRhk').onclick=openAddRhk;
  v.querySelectorAll('[data-open]').forEach(c=>c.onclick=()=>openSkpBulan(k.id,+c.dataset.open));

  wireUnduh(v); wireEkin(v); wireItemLocks(v);
}

/* ================= BULAN → daftar RHK bulan itu ================= */
/* Setelah menyimpan/mengunggah, tabel digambar ulang. Tanpa ini posisi gulir
   balik ke pojok kiri-atas, sehingga kolom Bukti Dukung yang tadi dilihat
   "loncat" pergi. Posisi disimpan lalu dikembalikan bila masih di bulan yang sama. */
let _gulir=null;
function simpanGulir(){
  const w=document.querySelector('.skpwrap');
  _gulir={ kunci:(state&&state.view==='skpbulan')?state.judulId+'-'+state.bulanKe:null,
           y:window.scrollY||0, x:w?w.scrollLeft:0 };
}
function pulihGulir(kunci){
  const g=_gulir; _gulir=null;
  if(!g||g.kunci!==kunci||(!g.x&&!g.y)) return;
  // Tabel butuh sesaat untuk diukur browser; kalau dipasang terlalu cepat nilainya
  // dipotong jadi 0. Karena itu dicoba ulang sampai posisinya benar-benar menempel.
  let coba=0;
  const pasang=()=>{
    const w=document.querySelector('.skpwrap');
    if(w&&g.x) w.scrollLeft=g.x;
    if(g.y) window.scrollTo(0,g.y);
    const lekat=(!g.x)||(w&&Math.abs(w.scrollLeft-g.x)<2);
    if(!lekat&&coba++<15) setTimeout(pasang,40);
  };
  pasang();                 // langsung, selagi tabel baru saja dipasang
  setTimeout(pasang,0);     // sekali lagi setelah browser selesai mengukur
}
async function openSkpBulan(skpId,bk){
  setNav('kegiatan');
  simpanGulir();
  try{ const j=await getJSON('api.php?action=skpbulan_get&judul_id='+skpId+'&bulan_ke='+bk); curJudul=j.judul; curBulanKe=j.bulan_ke;
    state={view:'skpbulan',judulId:skpId,bulanKe:j.bulan_ke}; setHash('skp='+skpId+'&bulan='+j.bulan_ke);
    const isY=j.judul.tipe!=='bulan';
    showCrumbs(isY
      ?[{label:'🎯 Kinerja',go:goHome},{label:j.judul.nama,go:()=>openJudul(skpId)},{label:j.bulan_nama}]
      :[{label:'🎯 Kinerja',go:goHome},{label:j.judul.nama}]);
    renderSkpBulan(j);
    pulihGulir(skpId+'-'+j.bulan_ke);
  }catch(e){ if(e.message!=='login')showError(e.message); }
}

/* ===== Status capaian: merah belum · biru proses · hijau tercapai · emas lebih ===== */
/* Fase waktu: 'lalu' = sudah lewat (dinilai final), 'kini' = sedang berjalan,
   'depan' = belum tiba. Dipakai supaya status "proses" hanya muncul di bulan
   yang sedang berjalan; bulan yang sudah lewat langsung dinilai tercapai/tidak. */
function faseBulan(tahun,bk){
  const t=wibParts(), y=+t.year, m=+t.month;
  const th=+tahun||0, b=+bk||0;
  if(!th||!b) return 'kini';
  if(th<y) return 'lalu';
  if(th>y) return 'depan';
  if(b<m)  return 'lalu';
  if(b>m)  return 'depan';
  return 'kini';
}
function faseTahun(tahun){
  const y=+wibParts().year, th=+tahun||0;
  if(!th) return 'kini';
  return th<y?'lalu':(th>y?'depan':'kini');
}
function statusCapaian(cap,tgt,fase){
  const c=+cap||0,t=+tgt||0,f=fase||'kini';
  if(t<=0) return {kelas:'st-none',pct:0,label:'belum ada target'};
  const pct=Math.round(c/t*100);
  if(c>t)  return {kelas:'st-emas', pct,label:'lewat target'};
  if(c>=t) return {kelas:'st-hijau',pct,label:'tercapai'};
  // belum sampai target — penilaiannya tergantung waktu
  if(f==='lalu')  return {kelas:'st-merah',pct,label:c>0?'tidak tercapai':'tidak dikerjakan'};
  if(f==='depan') return {kelas:'st-none', pct,label:'belum mulai'};
  if(c>0)  return {kelas:'st-biru', pct,label:'proses'};
  return {kelas:'st-merah',pct:0,label:'belum'};
}
function barMini(cap,tgt,label,fase){
  const s=statusCapaian(cap,tgt,fase);
  return `<div class="bmini ${s.kelas}">
    <div class="bmini-top"><span class="bmini-lbl">${label}</span><span class="bmini-val">${(+cap||0)} / ${(+tgt||0)} · ${s.pct}%</span></div>
    <div class="bmini-bar"><i style="width:${Math.min(100,s.pct)}%"></i></div></div>`;
}
function tglPendek(s){ if(!s)return''; const w=parseWIB(s); return `${w.da} ${BULAN_S[w.mo]}`; }

/* ===== Tabel SKP per bulan (mirip formulir resmi) ===== */
/* Warna pasangan: realisasi ke-N dan bukti dukung ke-N memakai warna & nomor
   yang sama, supaya jelas bukti itu milik realisasi yang mana. */
const WARNA_PASANG=['#0ea5e9','#f59e0b','#8b5cf6','#059669','#e11d48','#0d9488','#7c3aed','#ea580c'];
function skpTableHTML(j,locked){
  const list=j.rhk||[];
  // tahun SKP dipakai untuk menentukan apakah bulan/tahun ini sudah lewat
  const thnSkp=(j.judul&&j.judul.tahun)||0;
  const fsBln=faseBulan(thnSkp,j.bulan_ke), fsThn=faseTahun(thnSkp);
  if(!list.length) return `<div class="card empty"><span class="ic">🎯</span><h3>Belum ada RHK</h3><p>Tambah RHK dulu — RHK akan muncul di semua bulan.</p></div>`;
  const g={utama:[],tambahan:[]}; list.forEach(x=>{(g[x.kategori==='tambahan'?'tambahan':'utama']).push(x);});
  let no=0, body='';
  [['utama','Utama'],['tambahan','Tambahan']].forEach(([key,lbl])=>{
    body+=`<tr class="grouprow"><td colspan="8">${lbl}</td></tr>`;
    if(!g[key].length){ body+=`<tr><td colspan="8" class="kosong">— belum ada RHK ${lbl.toLowerCase()} —</td></tr>`; return; }
    g[key].forEach(x=>{
      no++;
      const ind=(x.indikator&&x.indikator.length)?x.indikator:[{aspek:'',iki:'',target:0}];
      const rs=ind.length;
      const tgtThn=x.target||0, capThn=x.capaian_tahun||0;
      const tgtBln=x.raksi_target||0, capBln=x.capaian||0;
      const stBln=statusCapaian(capBln,tgtBln,fsBln), stThn=statusCapaian(capThn,tgtThn,fsThn);
      const judulTip=`Bulan ini: ${capBln}/${tgtBln} (${stBln.pct}% · ${stBln.label}) — Tahun: ${capThn}/${tgtThn} (${stThn.pct}% · ${stThn.label})`;
      const kolomRhk=`<div class="c-rhk-nama">${esc(x.nama)}</div>
        ${locked?'':`<button class="tb-btn tb-neutral" data-kelolarhk="${x.id}" title="Edit RHK & indikator">⚙ Kelola</button>`}`;
      const ra = x.raksi_id
        ? `<div class="ra-txt">${esc(x.raksi_nama||'(tanpa judul)')}</div>
           <div class="ra-tgt">Target : <b>${tgtBln}</b></div>
           ${locked?'':`<button class="tb-btn tb-edit" data-editraksi="${x.raksi_id}">Edit</button>`}`
        : `<div class="ra-kosong">belum ada rencana aksi bulan ini</div>
           ${locked?'':`<button class="tb-btn tb-add" data-addraksi="${x.id}">+ Rencana Aksi</button>`}`;
      let real='', bukti='';
      const rl=x.realisasi||[];
      if(!x.raksi_id){
        real=`<span class="kosong-kecil">—</span>`;
        bukti=`<span class="kosong-kecil">${locked?'—':'buat rencana aksi dulu'}</span>`;
      }else{
        real = locked?'':`<button class="tb-btn tb-add" data-addreal="${x.raksi_id}">+ Realisasi</button>`;
        rl.forEach((r,i)=>{
          real+=`<div class="rz-item" style="--pc:${WARNA_PASANG[i%WARNA_PASANG.length]}">
            <div class="rz-txt"><span class="pair-no">${i+1}</span>${esc(r.uraian||'(tanpa uraian)')}${r.jumlah>0?` <b>(${r.jumlah})</b>`:''}</div>
            ${locked?'':`<button class="tb-btn tb-edit" data-editreal="${r.id}">Edit</button>
            <button class="tb-btn tb-del" data-delreal="${r.id}">Hapus</button>`}</div>`;
        });
        if(!rl.length) real+=`<div class="kosong-kecil">belum ada realisasi</div>`;
        const totBukti=rl.reduce((a,r)=>a+((r.files||[]).length),0);
        bukti=`<div class="bd-ringkas"><b>${totBukti}</b> berkas · ${rl.length} entri</div>`;
        rl.forEach((r,i)=>{
          const nf=(r.files||[]).length;
          bukti+=`<div class="bd-item" style="--pc:${WARNA_PASANG[i%WARNA_PASANG.length]}">
            <div class="bd-head"><span class="pair-no">${i+1}</span><span class="bd-tgl">📅 ${tglPendek(r.created_at)}</span>
              <span class="bd-jml ${nf?'ada':'kosong'}">${nf?nf+' berkas':'belum ada'}</span></div>
            <div class="bd-act">
              ${nf?`<button class="tb-btn tb-neutral" data-eyebd="${r.id}">👁 Lihat</button>
                    <button class="tb-btn tb-neutral" data-unduh="zip.php?harian=${r.id}" data-unduhlbl="bukti dukung tanggal ${tglPendek(r.created_at)} (${nf} berkas)">⤓ Unduh</button>`:''}
              ${locked?'':`<button class="tb-btn tb-add" data-addbukti2="${r.id}" data-tgl="${(r.created_at||'').slice(0,10)}">+ Bukti</button>`}</div>
          </div>`;
        });
        if(!rl.length) bukti+=`<div class="kosong-kecil">belum ada bukti dukung</div>`;
      }
      ind.forEach((a,i)=>{
        body+=`<tr>`;
        if(i===0){
          body+=`<td rowspan="${rs}" class="c-no ${stBln.kelas}" title="${judulTip}">${no}</td>
            <td rowspan="${rs}" class="c-rhk">${kolomRhk}</td>`;
        }
        body+=`<td class="c-aspek">${a.aspek?`<span class="aspek ${a.aspek}">${a.aspek}</span>`:''}</td>
          <td class="c-iki">${esc(a.iki||'')}</td>
          <td class="c-tgt">${a.target||''}</td>`;
        if(i===0){
          body+=`<td rowspan="${rs}" class="c-ra">${ra}</td>
            <td rowspan="${rs}" class="c-real">${real}</td>
            <td rowspan="${rs}" class="c-bukti">${bukti}</td>`;
        }
        body+=`</tr>`;
      });
    });
  });
  return `<div class="skpwrap"><table class="skptable">
    <thead><tr>
      <th style="width:34px">NO.</th>
      <th style="width:19%">RENCANA HASIL KERJA</th>
      <th style="width:72px">ASPEK</th>
      <th style="width:17%">INDIKATOR KINERJA INDIVIDU</th>
      <th style="width:70px">TARGET TAHUNAN</th>
      <th style="width:17%">RENCANA AKSI</th>
      <th style="width:16%">REALISASI</th>
      <th style="width:15%">BUKTI DUKUNG</th>
    </tr></thead><tbody>${body}</tbody></table></div><div class="skphint">↔ Geser tabel ke samping untuk melihat kolom lainnya.</div>`;
}
/* unduh selalu lewat konfirmasi (hasilnya berkas ZIP) */
function wireUnduh(root){
  root.querySelectorAll('[data-unduh]').forEach(b=>b.onclick=()=>{
    const url=b.dataset.unduh, lbl=b.dataset.unduhlbl||'berkas ini';
    askConfirm('Unduh berkas?',`Akan mengunduh <b>${esc(lbl)}</b> dalam satu berkas <b>ZIP</b>. Lanjutkan?`,
      ()=>{ window.location.href=url; },{okText:'Ya, unduh',danger:false});});
}
function openLihatBukti(r){
  const nf=(r.files||[]).length;
  $('#lbSub').innerHTML=`<b>${esc(r.uraian||'(tanpa uraian)')}</b> · 📅 ${tglPendek(r.created_at)} · <b>${nf}</b> berkas`;
  $('#lbList').innerHTML = nf ? (r.files||[]).map(fileChip).join('') : '<i style="color:var(--muted)">Belum ada bukti dukung.</i>';
  $('#lbUnduh').hidden = !nf;
  $('#lbUnduh').onclick=()=>askConfirm('Unduh berkas?',
    `Akan mengunduh <b>${nf} bukti dukung</b> (${esc(tglPendek(r.created_at))}) dalam satu berkas <b>ZIP</b>. Lanjutkan?`,
    ()=>{ window.location.href='zip.php?harian='+r.id; },{okText:'Ya, unduh',danger:false});
  $('#mLihatBukti').classList.add('show');
}
function wireMataBukti(root,j){
  root.querySelectorAll('[data-eyebd]').forEach(b=>b.onclick=()=>{
    let found=null;((j&&j.rhk)||[]).forEach(x=>(x.realisasi||[]).forEach(r=>{if(r.id==b.dataset.eyebd)found=r;}));
    if(found)openLihatBukti(found);});
}
function wireSkpTable(root,j,reload){
  wireUnduh(root); wireMataBukti(root,j);
  root.querySelectorAll('[data-kelolarhk]').forEach(b=>b.onclick=()=>{
    const x=(j.rhk||[]).find(r=>r.id==b.dataset.kelolarhk); if(!x)return;
    curJudul=j.judul; curBulanKe=j.bulan_ke; openKelolaRhk(x,j,reload);});
  root.querySelectorAll('[data-addraksi]').forEach(b=>b.onclick=()=>{
    const x=(j.rhk||[]).find(r=>r.id==b.dataset.addraksi); if(!x)return;
    curRhk={id:x.id,nama:x.nama}; curRhkRaksi=[]; curJudul=j.judul; curBulanKe=j.bulan_ke;
    openAddRaksi(j.bulan_ke,reload);});
  root.querySelectorAll('[data-editraksi]').forEach(b=>b.onclick=()=>{
    const x=(j.rhk||[]).find(r=>r.raksi_id==b.dataset.editraksi); if(!x)return;
    curRhk={id:x.id,nama:x.nama}; curJudul=j.judul;
    openEditRaksi({id:x.raksi_id,nama:x.raksi_nama,target:x.raksi_target,bulan_ke:j.bulan_ke},reload);});
  root.querySelectorAll('[data-addreal]').forEach(b=>b.onclick=()=>{
    curRaksi={id:+b.dataset.addreal,nama:''}; openNewCatatan(reload);});
  root.querySelectorAll('[data-editreal]').forEach(b=>b.onclick=()=>{
    let found=null;(j.rhk||[]).forEach(x=>(x.realisasi||[]).forEach(r=>{if(r.id==b.dataset.editreal)found=r;}));
    if(found)openEditHarian(found,reload);});
  root.querySelectorAll('[data-addbukti2]').forEach(b=>b.onclick=()=>openBukti(b.dataset.addbukti2,b.dataset.tgl,reload));
  root.querySelectorAll('[data-delreal]').forEach(b=>b.onclick=()=>{
    askConfirm('Hapus realisasi ini?','Realisasi beserta bukti dukungnya akan dihapus permanen. Yakin?',async()=>{
      await getJSON('api.php?action=realisasi_delete',{method:'POST',body:fd({id:b.dataset.delreal})});toast('Realisasi dihapus');if(reload)reload();});});
}
/* Ringkasan apa saja yang MASIH KOSONG di bulan ini — dari data yang sudah ada
   di skpbulan_get (rhk[], ekin[]). Tidak perlu query tambahan. */
function kelengkapanBulanHTML(j){
  const list=j.rhk||[]; if(!list.length) return '';
  let noTgt=0,noReal=0,noBukti=0;
  list.forEach(x=>{
    if(!x.raksi_id || (+x.raksi_target||0)<=0){ noTgt++; return; }
    const rl=x.realisasi||[];
    if(!rl.length){ noReal++; return; }
    noBukti += rl.filter(r=>!((r.files||[]).length)).length;
  });
  const ekinKosong = !((j.ekin||[]).length);
  const it=[];
  if(noTgt)  it.push(`<b>${noTgt}</b> RHK belum diisi <b>target / rencana aksi</b>`);
  if(noReal) it.push(`<b>${noReal}</b> RHK belum ada <b>realisasi</b>`);
  if(noBukti)it.push(`<b>${noBukti}</b> realisasi belum dilampiri <b>bukti dukung</b>`);
  if(ekinKosong) it.push(`<b>berkas Ekin</b> bulan ini belum diunggah`);
  if(!it.length)
    return `<div class="lengkap-ok">✅ <b>${esc(j.bulan_nama)}</b> sudah lengkap — target, realisasi, bukti dukung, dan Ekin sudah terisi semua.</div>`;
  return `<div class="lengkap-warn"><div class="lw-head">⚠️ Yang masih perlu dilengkapi di <b>${esc(j.bulan_nama)}</b>:</div>`
       + `<ul>${it.map(x=>`<li>${x}</li>`).join('')}</ul></div>`;
}
function renderSkpBulan(j){
  const k=j.judul,v=$('#view'),isY=k.tipe!=='bulan',bk=j.bulan_ke;
  const terbuka=isBulanTerbuka(k.id,bk,k.tahun);
  const reload=()=>openSkpBulan(k.id,bk);
  const list=j.rhk||[];
  const tgtBln=list.reduce((a,x)=>a+(x.raksi_target||0),0);
  const capBln=list.reduce((a,x)=>a+(x.capaian||0),0);
  const jmlBukti=list.reduce((a,x)=>a+(x.realisasi||[]).reduce((b,r)=>b+((r.files||[]).length),0),0);
  const jmlReal=list.reduce((a,x)=>a+(x.realisasi||[]).length,0);
  const s=statusCapaian(capBln,tgtBln,faseBulan(isY?k.tahun:(k.tahun||+wibParts().year),bk));
  let html=`<button class="btn btn-ghost btn-sm backbtn" id="backBtn">← ${isY?esc(k.nama):'Daftar Kinerja'}</button>`;
  html+=`<div class="viewhead"><div><h2>🗓️ ${j.bulan_nama}${isY?' '+k.tahun:''}</h2><div class="meta">${esc(k.nama)} · ${list.length} RHK</div></div>
      <div class="actions">${terbuka?'<button class="btn btn-ghost btn-sm" id="addRhk">➕ RHK</button>':''}
        <button class="btn btn-sm ${terbuka?'btn-soft':'btn-primary'}" id="gembokBtn">${terbuka?'🔓 Terbuka — Kunci lagi':'🔒 Terkunci — Buka untuk edit'}</button></div></div>`;
  html+=`<div class="statrow">
    <div class="stat-tile"><div class="st-ic ic-blue">🎯</div><div><div class="st-n">${tgtBln}</div><div class="st-l">target bulan ini</div></div></div>
    <div class="stat-tile"><div class="st-ic ic-teal">▲</div><div><div class="st-n">${capBln}</div><div class="st-l">capaian</div></div></div>
    <div class="stat-tile ${s.kelas}"><div class="st-ic ic-violet">％</div><div><div class="st-n">${s.pct}%</div><div class="st-l">${s.label}</div></div></div>
    <div class="stat-tile"><div class="st-ic ic-amber">📎</div><div><div class="st-n">${jmlBukti}</div><div class="st-l">bukti · ${jmlReal} realisasi</div></div></div>
  </div>`;
  html+=kelengkapanBulanHTML(j);
  html+=`<div class="actions" style="margin-bottom:12px">
    <button class="btn btn-soft btn-sm" data-unduh="zip.php?judul=${k.id}&bulan=${bk}" data-unduhlbl="semua bukti dukung bulan ${j.bulan_nama}">⤓ Unduh bukti ${j.bulan_nama}</button>
    <button class="btn btn-ghost btn-sm" data-unduh="zip.php?judul=${k.id}" data-unduhlbl="semua bukti dukung 1 tahun (Januari–Desember)">⤓ Unduh 1 tahun</button>
    <button class="btn btn-ghost btn-sm" data-unduh="zip.php?judul=${k.id}&ekin=1" data-unduhlbl="semua berkas Ekin">📄 Unduh berkas Ekin</button></div>`;
  html+=`<div class="tiphint" style="${terbuka?'border-color:#bbf7d0;background:var(--good-l);color:#166534':'border-color:#fde68a;background:var(--accent-l);color:var(--accent-ink)'}">
    ${terbuka?`🔓 <b>${j.bulan_nama}</b> sedang terbuka — kamu bisa menambah / mengubah isinya. Kunci lagi kalau sudah selesai.`
             :`🔒 <b>${j.bulan_nama}</b> terkunci. Klik <b>Buka untuk edit</b> di kanan atas kalau mau mengisi atau mengubah.`}</div>`;
  html+=ekinBarSkp(k.id,j.ekin||[],bk,j.bulan_nama);
  html+=`<div class="sectlabel">📋 Hasil Kerja</div>`;
  html+=skpTableHTML(j,!terbuka);
  v.innerHTML=html;
  $('#backBtn').onclick=()=> isY?openJudul(k.id):goHome();
  if(terbuka) $('#addRhk').onclick=openAddRhk;
  $('#gembokBtn').onclick=()=>{
    if(terbuka){
      askConfirm('Kunci kembali '+j.bulan_nama+'?','Setelah dikunci, isi bulan ini tidak bisa diubah sampai dibuka lagi.',
        ()=>{kunciBulan(k.id,bk);reload();},{okText:'Ya, kunci',danger:false});
    }else{
      askConfirm('Buka kunci '+j.bulan_nama+'?','Setelah dibuka kamu bisa menambah / mengubah rencana aksi, realisasi & bukti dukung bulan ini.',
        ()=>{bukaBulan(k.id,bk);reload();},{okText:'Ya, buka',danger:false});
    }};
  wireUnduh(v); wireEkin(v);
  if(terbuka) wireSkpTable(v,j,reload); else { wireUnduh(v); wireMataBukti(v,j); }
}

/* ================= RHK → daftar Rencana Aksi ================= */
async function openRhk(id){
  setNav('kegiatan');
  try{ const j=await getJSON('api.php?action=rhk_get&id='+id); curRhk=j.rhk; curRaksi=null; curRhkRaksi=j.raksi; if(j.judul)curJudul=j.judul;
    state={view:'rhk',rhkId:id}; setHash('rhk='+id);
    showCrumbs([{label:'🎯 Kinerja',go:goHome},{label:(j.judul?j.judul.nama:'Judul'),go:()=>openJudul(j.judul.id)},{label:j.rhk.nama}]);
    renderRhk(j);
  }catch(e){ if(e.message!=='login')showError(e.message); }
}
function renderRhk(j){
  const k=j.rhk,list=j.raksi,v=$('#view');
  let html=`<button class="btn btn-ghost btn-sm backbtn" id="backBtn">← ${esc((j.judul&&j.judul.nama)||'Daftar RHK')}</button>`;
  if(k.pimpinan) html+=`<div class="tiphint" style="margin-bottom:12px"><b>RHK Pimpinan:</b> ${esc(k.pimpinan)}</div>`;
  html+=`<div class="viewhead"><div><h2>🎯 ${esc(k.nama)}</h2><div class="meta">${k.kategori==='tambahan'?'Tambahan':'Utama'} · Tahun ${k.tahun}</div></div>
      <div class="actions"><button class="btn btn-primary btn-sm" id="addRaksi">➕ Rencana Aksi</button></div></div>`;
  if(k.target>0) html+=`<div class="card pad" style="margin-bottom:14px"><div class="panel-h"><h3>🎯 Capaian Tahunan</h3><span class="hint">${k.capaian>=k.target?'tercapai 🎉':'total ÷ target'}</span></div>${progressHTML(k.capaian,k.target,'')}</div>`;
  html+=`<div class="actions" style="margin-bottom:12px"><a class="btn btn-soft btn-sm" href="zip.php?subkegiatan=${k.id}">📦 Download (ZIP)</a></div>`;
  // Indikator Kinerja Individu (aspek + IKI + target tahunan)
  const indl=j.indikator||[];
  html+=`<div class="sectlabel">📐 Indikator Kinerja Individu <span class="n">(${indl.length})</span> <button class="btn btn-ghost btn-sm" id="addInd" style="margin-left:8px;padding:3px 10px">➕ Indikator</button></div>`;
  if(!indl.length){ html+=`<div class="card empty" style="padding:20px"><span class="ic" style="font-size:28px">📐</span><p>Belum ada indikator. Tiap indikator punya target tahunan sendiri.</p></div>`; }
  else{ indl.forEach(a=>{ html+=`<div class="navcard aspekcard"><div class="ic"><span class="aspek ${a.aspek}">${a.aspek}</span></div>
      <div class="body"><h4 style="font-size:14px;font-weight:600">${esc(a.iki)}</h4><div class="meta">🎯 target tahunan: <b>${a.target||0}</b></div></div>
      <div class="ctl"><button class="iconbtn edit" data-editind="${a.id}" title="Edit">✏️</button>
        <button class="iconbtn" data-delind="${a.id}" title="Hapus">🗑️</button></div>
      <button class="itemlock" data-lockitem title="Buka kunci">⚙️</button></div>`;});}
  html+=ekinBar(k.id,0,j.ekin||[],'Ekin Tahunan');
  html+=`<div class="sectlabel">🗓️ Rencana Aksi <span class="n">(${list.length})</span></div>`;
  if(!list.length){html+=`<div class="card empty"><span class="ic">🗓️</span><h3>Belum ada rencana aksi</h3><p>Klik <b>➕ Rencana Aksi</b>, pilih bulan & target. Bisa "Salin dari" bulan lain.</p></div>`;}
  else{ let curM=0;
    list.forEach(s=>{ if(s.bulan_ke!==curM){curM=s.bulan_ke; html+=`<div class="mh" style="margin:14px 4px 8px">${BULAN[curM-1]}</div>`;}
      const over=(s.target>0&&s.capaian>s.target), done=(s.target>0&&s.capaian>=s.target);
      html+=`<div class="navcard" data-raksi="${s.id}"><div class="ic">${over?'🔵':(done?'✅':'🗓️')}</div>
        <div class="body"><h4>${esc(s.nama||'(tanpa judul)')}</h4><div class="meta">${s.jml_aspek} aspek · ${s.jml_realisasi} realisasi · ${s.jml_berkas} bukti${s.no_bukti>0?` · <span style="color:var(--danger)">${s.no_bukti} tanpa bukti</span>`:''}</div>${s.target>0?progressHTML(s.capaian,s.target,''):'<div class="meta" style="margin-top:3px;color:var(--danger)">🔴 target bulan belum diisi</div>'}</div>
        <div class="ctl"><a class="iconbtn dl" href="zip.php?harian=0&raksi=${s.id}" title="Download bukti (ZIP)" style="display:none"></a>
          <button class="iconbtn edit" data-editraksi="${s.id}" title="Edit">✏️</button>
          <button class="iconbtn" data-delraksi="${s.id}" data-nama="${esc(s.nama||'')}" title="Hapus">🗑️</button></div>
        <button class="itemlock" data-lockitem title="Buka kunci (edit / hapus)">⚙️</button>
        <div class="chev">›</div></div>`;});
  }
  v.innerHTML=html;
  $('#backBtn').onclick=()=>{ if(j.judul)openJudul(j.judul.id); else goHome(); };
  $('#addRaksi').onclick=openAddRaksi;
  $('#addInd').onclick=openAddInd;
  v.querySelectorAll('[data-editind]').forEach(b=>b.onclick=e=>{e.stopPropagation();openEditInd((j.indikator||[]).find(x=>x.id==b.dataset.editind));});
  v.querySelectorAll('[data-delind]').forEach(b=>b.onclick=e=>{e.stopPropagation();
    askConfirm('Hapus indikator?','Indikator & target tahunannya akan dihapus.',async()=>{
      await getJSON('api.php?action=indikator_delete',{method:'POST',body:fd({id:b.dataset.delind})});toast('Indikator dihapus');openRhk(k.id);});});
  v.querySelectorAll('[data-raksi]').forEach(c=>c.onclick=e=>{if(e.target.closest('.ctl')||e.target.closest('.itemlock'))return;openRaksi(+c.dataset.raksi);});
  v.querySelectorAll('[data-editraksi]').forEach(b=>b.onclick=e=>{e.stopPropagation();openEditRaksi(list.find(x=>x.id==b.dataset.editraksi));});
  v.querySelectorAll('[data-delraksi]').forEach(b=>b.onclick=e=>{e.stopPropagation();
    askConfirm('Hapus rencana aksi?',`"<b>${esc(b.dataset.nama)||'Rencana aksi'}</b>" beserta aspek, realisasi & bukti akan dihapus.`,async()=>{
      await getJSON('api.php?action=raksi_delete',{method:'POST',body:fd({id:b.dataset.delraksi})});toast('Rencana aksi dihapus');openRhk(k.id);});});
  wireEkin(v); wireItemLocks(v);
}

/* ================= RENCANA AKSI → Aspek/IKI + Realisasi ================= */
async function openRaksi(id){
  setNav('kegiatan');
  try{ const j=await getJSON('api.php?action=raksi_get&id='+id); curRaksi=j.raksi; curRhk=j.rhk;
    state={view:'raksi',raksiId:id}; setHash('raksi='+id);
    showCrumbs([{label:'🎯 RHK',go:goHome},{label:j.rhk.nama,go:()=>openRhk(j.rhk.id)},{label:j.raksi.bulan_nama}]);
    renderRaksi(j);
  }catch(e){ if(e.message!=='login')showError(e.message); }
}
function renderRaksi(j){
  const r=j.raksi,rhk=j.rhk,harian=j.harian,v=$('#view');
  const noBukti=harian.filter(h=>!(h.files&&h.files.length)).length;
  let html=`<button class="btn btn-ghost btn-sm backbtn" id="backBtn">← ${esc(rhk.nama.slice(0,40))}${rhk.nama.length>40?'…':''}</button>`;
  html+=`<div class="viewhead"><div><h2>🗓️ ${r.bulan_nama} — ${esc(r.nama||'Rencana Aksi')}</h2><div class="meta">${esc(rhk.nama)}</div></div></div>`;
  html+=`<div class="statrow">
    <div class="stat-tile"><div class="st-ic ic-violet">📝</div><div><div class="st-n">${harian.length}</div><div class="st-l">realisasi</div></div></div>
    <div class="stat-tile"><div class="st-ic ic-teal">▲</div><div><div class="st-n">${r.capaian}</div><div class="st-l">capaian</div></div></div>
    <div class="stat-tile"><div class="st-ic ic-amber">🎯</div><div><div class="st-n">${r.target||0}</div><div class="st-l">target bulan</div></div></div>
    <a class="stat-tile" href="zip.php?subkegiatan=${rhk.id}"><div class="st-ic ic-blue">📦</div><div><div class="st-n">${j.berkas.length}</div><div class="st-l">bukti · unduh</div></div></a>
  </div>`;
  if(r.target>0) html+=`<div class="card pad" style="margin-bottom:14px">${progressHTML(r.capaian,r.target,'')}</div>`;
  else html+=`<div class="tiphint" style="border-color:#fecdd3;color:#be123c;background:#fff1f2;margin-bottom:14px">🔴 Target bulan belum diisi. Klik ⚙️ lalu ✏️ pada judul untuk mengisi target.</div>`;
  html+=ekinBar(rhk.id,r.id,j.ekin||[],'Ekin Bulanan '+r.bulan_nama);
  // Realisasi
  html+=`<div class="sectlabel">📝 Realisasi <span class="n">(${harian.length}${noBukti?` · <span style="color:var(--danger)">${noBukti} belum ada bukti</span>`:''})</span> <button class="btn btn-primary btn-sm" id="newCatatan" style="margin-left:8px;padding:3px 12px">➕ Realisasi</button></div>`;
  html+=realisasiCardsHTML(harian);
  v.innerHTML=html;
  $('#backBtn').onclick=()=>openRhk(rhk.id);
  $('#newCatatan').onclick=openNewCatatan;
  wireEkin(v); wireRealisasi(v,harian); wireItemLocks(v);
}

let tblReload=null;
function reloadRealisasi(){ if(tblReload){tblReload();return;} if(curRaksi)openRaksi(curRaksi.id); }
function fileChip(f){
  if((f.mime||'').startsWith('image/'))
    return `<a class="thumb" href="download.php?id=${f.id}" target="_blank" title="${esc(f.name)}"><img src="download.php?id=${f.id}" alt="${esc(f.name)}" loading="lazy"></a>`;
  return `<a class="chip" href="download.php?id=${f.id}" target="_blank" title="Klik untuk cek isi berkas"><span>${fileIcon(f.mime,f.name)}</span><span class="nm">${esc(f.name)}</span><span style="opacity:.55">↗</span></a>`;}
function realisasiCardsHTML(harian){
  if(!harian.length) return `<div class="card empty" style="padding:26px"><span class="ic" style="font-size:32px">📝</span><h3>Belum ada realisasi</h3><p>Klik <b>➕ Realisasi</b> di kanan atas.</p></div>`;
  let html='<div>';
  harian.forEach((h)=>{const w=parseWIB(h.created_at),p=x=>String(x).padStart(2,'0');
    const has=h.files&&h.files.length; const files=(h.files||[]).map(fileChip).join('');
    html+=`<div class="harian ${has?'has-bukti':'no-bukti'}"><div class="harian-top">
      <div class="datechip"><div class="d">${w.da}</div><div class="m">${BULAN_S[w.mo]}</div></div>
      <div class="harian-body">
        <div class="harian-time">${HARI[w.dow]}, ${w.da} ${BULAN_S[w.mo]} · ${p(w.h)}:${p(w.mi)}${(h.jumlah&&+h.jumlah>0)?` · <b style="color:#4338ca">+${h.jumlah}</b>`:''}</div>
        ${h.uraian?`<div class="harian-desc">${esc(h.uraian)}</div>`:''}
        <div class="harian-foot">
          ${has?`<button class="mini-btn" data-eye="${h.id}">👁 ${h.files.length} bukti</button><a class="mini-btn" href="zip.php?harian=${h.id}">📦 Unduh</a>`:`<span class="mini-btn no">⚠ belum ada bukti</span>`}
          <button class="mini-btn" data-addbukti="${h.id}" data-tgl="${w.da} ${BULAN_S[w.mo]} ${w.y}">📎 Tambah Bukti</button>
          <button class="mini-btn" data-dup="${h.id}">⧉ Duplikat</button>
        </div>
        ${has?`<div class="harian-bukti" data-buktiwrap="${h.id}" hidden><div class="chips">${files}</div></div>`:''}
      </div>
      <div class="ctl"><button class="iconbtn edit" data-edith="${h.id}" title="Edit">✏️</button>
        <button class="iconbtn" data-delh="${h.id}" title="Hapus">🗑️</button></div>
      <button class="itemlock" data-lockitem title="Buka kunci (edit / hapus)">⚙️</button></div></div>`;});
  return html+'</div>';
}
function wireRealisasi(v,harian){
  v.querySelectorAll('[data-eye]').forEach(b=>b.onclick=()=>{const w=v.querySelector('[data-buktiwrap="'+b.dataset.eye+'"]');if(w){w.hidden=!w.hidden;b.classList.toggle('on',!w.hidden);}});
  v.querySelectorAll('[data-addbukti]').forEach(b=>b.onclick=()=>openBukti(b.dataset.addbukti,b.dataset.tgl));
  v.querySelectorAll('[data-dup]').forEach(b=>b.onclick=()=>openDuplicate(harian.find(x=>x.id==b.dataset.dup)));
  v.querySelectorAll('[data-edith]').forEach(b=>b.onclick=()=>openEditHarian(harian.find(x=>x.id==b.dataset.edith)));
  v.querySelectorAll('[data-delh]').forEach(b=>b.onclick=()=>{
    askConfirm('Hapus realisasi ini?','Realisasi & bukti di dalamnya akan dihapus.',async()=>{
      await getJSON('api.php?action=realisasi_delete',{method:'POST',body:fd({id:b.dataset.delh})});toast('Realisasi dihapus');reloadRealisasi();});});
}

/* dropzone generik */
function makeDrop(dropId,inputId,listId,getArr){
  const drop=$('#'+dropId),fi=$('#'+inputId),list=$('#'+listId);
  function render(){const arr=getArr();list.innerHTML='';arr.forEach((f,i)=>{const el=document.createElement('div');el.className='fileitem';
    el.innerHTML=`<span>${fileIcon(f.type,f.name)}</span><span class="nm">${esc(f.name)}</span><span class="sz">${fmtSize(f.size)}</span><button class="x" data-i="${i}">✕</button>`;list.appendChild(el);});
    list.querySelectorAll('.x').forEach(b=>b.onclick=()=>{getArr().splice(+b.dataset.i,1);render();});}
  drop.onclick=()=>fi.click();
  fi.onchange=e=>{for(const f of e.target.files)getArr().push(f);fi.value='';render();};
  ['dragover','dragenter'].forEach(ev=>drop.addEventListener(ev,e=>{e.preventDefault();drop.classList.add('over');}));
  ['dragleave','drop'].forEach(ev=>drop.addEventListener(ev,e=>{e.preventDefault();drop.classList.remove('over');}));
  drop.addEventListener('drop',e=>{for(const f of e.dataTransfer.files)getArr().push(f);render();});
  return render;
}

/* ---------- Modal: buat realisasi ---------- */
let catFiles=[];
const catRender=makeDrop('catDrop','catFile','catPending',()=>catFiles);
function openNewCatatan(reload){tblReload=reload||null;if(!curRaksi){toast('Buka rencana aksi dulu',true);return;}catFiles=[];catRender();$('#catUraian').value='';$('#catJumlah').value='1';$('#catTanggal').value=todayISO();$('#catSubNama').textContent=curRaksi.nama||'';$('#mCatatan').classList.add('show');setTimeout(()=>$('#catUraian').focus(),50);}
function openDuplicate(h){if(!h)return;catFiles=[];catRender();$('#catUraian').value=h.uraian||'';$('#catJumlah').value=(h.jumlah&&+h.jumlah>0)?h.jumlah:'1';$('#catTanggal').value=(h.created_at||'').slice(0,10)||todayISO();$('#catSubNama').textContent=curRaksi?curRaksi.nama:'';$('#mCatatan').classList.add('show');setTimeout(()=>$('#catUraian').focus(),50);toast('Salinan siap — cek tanggal lalu Simpan');}
$('#catSave').onclick=async()=>{
  const uraian=$('#catUraian').value.trim();
  if(!uraian && !catFiles.length){toast('Isi realisasi atau lampirkan bukti',true);return;}
  if(!curRaksi){toast('Buka rencana aksi dulu',true);return;}
  const btn=$('#catSave');btn.disabled=true;const o=btn.innerHTML;btn.innerHTML='⏳ Menyimpan...';
  const f=new FormData();f.append('csrf',CSRF);f.append('raksi_id',curRaksi.id);f.append('uraian',uraian);f.append('jumlah',parseInt($('#catJumlah').value,10)||0);f.append('tanggal',$('#catTanggal').value||'');
  catFiles.forEach(x=>f.append('dok[]',x,x.name));
  try{const j=await getJSON('api.php?action=realisasi_add',{method:'POST',body:f});
    let m='✓ Realisasi tersimpan';
    if(j.skipped&&j.skipped.length){ m+=` — ${j.skipped.length} bukti GAGAL diunggah`; toast(j.skipped.join(' • '),true); }
    else if(j.saved_files) m+=` (${j.saved_files} bukti)`;
    $('#mCatatan').classList.remove('show');toast(m);reloadRealisasi();}
  catch(e){toast(e.message,true);}finally{btn.disabled=false;btn.innerHTML=o;}
};

/* ---------- Modal: tambah bukti ---------- */
let bkFiles=[],bkHarianId=null;
const bkRender=makeDrop('bkDrop','bkFile','bkPending',()=>bkFiles);
function openBukti(id,tgl,reload){tblReload=reload||null;bkHarianId=id;bkFiles=[];bkRender();$('#bkTgl').textContent=tgl||'';$('#mBukti').classList.add('show');}
$('#bkSave').onclick=async()=>{
  if(!bkFiles.length){toast('Pilih minimal 1 berkas',true);return;}
  const btn=$('#bkSave');btn.disabled=true;const o=btn.innerHTML;btn.innerHTML='⏳ Mengunggah...';
  const f=new FormData();f.append('csrf',CSRF);f.append('harian_id',bkHarianId);
  bkFiles.forEach(x=>f.append('dok[]',x,x.name));
  try{const j=await getJSON('api.php?action=realisasi_add_files',{method:'POST',body:f});
    let m=`✓ ${j.saved_files||0} bukti ditambahkan`;
    if(j.skipped&&j.skipped.length){ m+=` — ${j.skipped.length} GAGAL`; toast(j.skipped.join(' • '),true); }
    $('#mBukti').classList.remove('show');toast(m);reloadRealisasi();}
  catch(e){toast(e.message,true);}finally{btn.disabled=false;btn.innerHTML=o;}
};

/* ---------- Gembok per item ---------- */
function wireItemLocks(v){v.querySelectorAll('[data-lockitem]').forEach(b=>b.onclick=e=>{e.stopPropagation();
  const card=b.closest('.navcard,.harian');if(!card)return;card.classList.toggle('revealed');});}

/* ---------- Minimize sidebar ---------- */
function lsGet(k){try{return localStorage.getItem(k);}catch(e){return null;}}
function lsSet(k,v){try{localStorage.setItem(k,v);}catch(e){}}
function applyCollapse(){document.body.classList.toggle('sb-collapsed',lsGet('ekin_sb')==='1');}
$('#brandBtn').onclick=()=>{lsSet('ekin_sb',lsGet('ekin_sb')==='1'?'0':'1');applyCollapse();};
applyCollapse();

/* ---------- Modals umum ---------- */
$$('[data-close]').forEach(b=>b.onclick=()=>$('#'+b.dataset.close).classList.remove('show'));
$$('.modal-bg').forEach(m=>m.onclick=e=>{if(e.target===m)m.classList.remove('show');});
let confirmCb=null;
function askConfirm(title,msg,cb,opts){opts=opts||{};$('#cfTitle').textContent=title;$('#cfMsg').innerHTML=msg;
  const ok=$('#cfOk');ok.textContent=opts.okText||'Hapus';ok.style.background=opts.danger===false?'var(--brand)':'var(--danger)';
  confirmCb=cb;$('#mConfirm').classList.add('show');}
$('#cfOk').onclick=async()=>{const cb=confirmCb;confirmCb=null;$('#mConfirm').classList.remove('show');if(cb)await cb();};

/* ---------- Berkas Ekin ---------- */
let ekinFiles=[], ekinCtx={skp:0,rhk:0,raksi:0};
const ekinRender=makeDrop('ekinDrop','ekinFile','ekinPending',()=>ekinFiles);
function openEkin(rhkId,raksiId,label){ekinCtx={skp:0,rhk:rhkId,raksi:raksiId};ekinFiles=[];ekinRender();$('#ekinTitle').textContent='📄 Tambah '+label;$('#ekinDesc').textContent='';$('#mEkin').classList.add('show');}
function openEkinSkp(skpId,bk,label){ekinCtx={skp:skpId,rhk:0,raksi:0,bk:bk||0};ekinFiles=[];ekinRender();$('#ekinTitle').textContent='📄 Tambah '+(label||'Ekin Tahunan');$('#ekinDesc').textContent='';$('#mEkin').classList.add('show');}
$('#ekinSave').onclick=async()=>{
  if(!ekinFiles.length){toast('Pilih minimal 1 berkas',true);return;}
  const btn=$('#ekinSave');btn.disabled=true;const o=btn.innerHTML;btn.innerHTML='⏳ Mengunggah...';
  const f=new FormData();f.append('csrf',CSRF);f.append('rhk_id',ekinCtx.rhk);f.append('raksi_id',ekinCtx.raksi);f.append('judul_id',ekinCtx.skp);f.append('bulan_ke',ekinCtx.bk||0);
  ekinFiles.forEach(x=>f.append('dok[]',x,x.name));
  try{await getJSON('api.php?action=ekin_add',{method:'POST',body:f});$('#mEkin').classList.remove('show');toast('✓ Berkas Ekin ditambahkan');
    if(ekinCtx.skp>0){ if(ekinCtx.bk>0) openSkpBulan(ekinCtx.skp,ekinCtx.bk); else openJudul(ekinCtx.skp); }
    else if(ekinCtx.raksi>0) openRaksi(ekinCtx.raksi); else openRhk(ekinCtx.rhk);
  }catch(e){toast(e.message,true);}finally{btn.disabled=false;btn.innerHTML=o;}
};
function ekinBarSkp(skpId,list,bk,bulanNama){
  bk=bk||0;
  const label = bk?('Ekin Bulanan '+(bulanNama||'')):'Ekin Tahunan';
  const chips = list.length ? `<div class="chips">`+list.map(f=>ekinChip(f)).join('')+`</div>` : `<span class="eb-empty">belum ada berkas</span>`;
  return `<div class="ekinbar"><span class="eb-lbl">📄 ${esc(label)}</span>${chips}<span class="eb-spacer"></span><button class="btn btn-soft btn-sm" data-ekinaddskp="${skpId}" data-ekinbk="${bk}">⬆️ Tambah</button></div>`;
}
function ekinBar(rhkId,raksiId,list,label){
  const chips = list.length ? `<div class="chips">`+list.map(f=>ekinChip(f)).join('')+`</div>` : `<span class="eb-empty">belum ada berkas</span>`;
  return `<div class="ekinbar"><span class="eb-lbl">📄 ${esc(label)}</span>${chips}<span class="eb-spacer"></span><button class="btn btn-soft btn-sm" data-ekinadd="${rhkId}|${raksiId}|${esc(label)}">⬆️ Tambah</button></div>`;
}
function ekinChip(f){
  const img=(f.mime||'').startsWith('image/');
  const inner=img?`<span>🖼️</span>`:`<span>${fileIcon(f.mime,f.name)}</span>`;
  return `<span class="chip ekinchip"><a href="download.php?ekin=${f.id}" target="_blank" title="Buka/cek" style="display:flex;align-items:center;gap:6px;text-decoration:none;color:inherit">${inner}<span class="nm">${esc(f.name)}</span></a><button class="chip-x" data-ekindel="${f.id}" title="Hapus">✕</button></span>`;
}
function wireEkin(v){
  v.querySelectorAll('[data-ekinadd]').forEach(b=>b.onclick=()=>{const [rhkId,raksiId,label]=b.dataset.ekinadd.split('|');openEkin(+rhkId,+raksiId,label);});
  v.querySelectorAll('[data-ekinaddskp]').forEach(b=>b.onclick=()=>{const bk=+(b.dataset.ekinbk||0);
    openEkinSkp(+b.dataset.ekinaddskp,bk,bk?('Ekin Bulanan '+BULAN[bk-1]):'Ekin Tahunan');});
  v.querySelectorAll('[data-ekindel]').forEach(b=>b.onclick=()=>askConfirm('Hapus berkas Ekin?','Berkas ini akan dihapus permanen.',async()=>{
    await getJSON('api.php?action=ekin_delete',{method:'POST',body:fd({id:b.dataset.ekindel})});toast('Berkas dihapus');
    (state.view==='skpbulan')?openSkpBulan(curJudul.id,curBulanKe)
     :((state.view==='judul')?openJudul(curJudul.id):((state.view==='raksi')?openRaksi(curRaksi.id):openRhk(curRhk.id)));}));
}

/* ---------- Modal: SKP (wadah + RHK) ---------- */
let judulEditId=null, judulEkinFiles=[];
const judulEkinRender=makeDrop('judulEkinDrop','judulEkinFile','judulEkinPending',()=>judulEkinFiles);
function toggleJudulTipe(){const isY=$('#judulTipe').value==='tahun';$('#judulTahunWrap').hidden=!isY;$('#judulBulanWrap').hidden=isY;
  $('#judulRhkHint').textContent=isY?'RHK ini akan muncul di semua bulan (Januari–Desember). Bisa ditambah / diedit lagi nanti.':'RHK untuk bulan SKP ini. Bisa ditambah / diedit lagi nanti.';}
$('#judulTipe').onchange=toggleJudulTipe;
function indRowHTML(){return `<div class="irow"><select class="ji-aspek"><option value="kuantitas">Kuantitas</option><option value="kualitas">Kualitas</option><option value="waktu">Waktu</option></select><textarea class="ji-iki" placeholder="Indikator Kinerja Individu (IKI)"></textarea><input type="text" inputmode="numeric" class="ji-target" placeholder="Target thn"><button type="button" class="subrow-x ji-x" title="Hapus indikator">✕</button></div>`;}
function addIndRow(host){const d=document.createElement('div');d.innerHTML=indRowHTML();const row=d.firstElementChild;row.querySelector('.ji-x').onclick=()=>row.remove();host.appendChild(row);return row;}
function addJudulRhkRow(){const d=document.createElement('div');d.className='krow';
  d.innerHTML=`<div class="kr-line"><select class="jr-kat"><option value="utama">Utama</option><option value="tambahan">Tambahan</option></select><textarea class="jr-nama" placeholder="Rencana Hasil Kerja (RHK)"></textarea><button type="button" class="subrow-x jr-x" title="Hapus RHK">✕</button></div>
    <div class="indwrap"><div class="indlbl">Indikator Kinerja Individu <span>(bisa &gt;1, tiap indikator punya target tahunan)</span></div>
      <div class="ji-list"></div>
      <button type="button" class="btn btn-ghost btn-sm ji-add" style="padding:3px 10px;font-size:12px">➕ Indikator</button></div>`;
  d.querySelector('.jr-x').onclick=()=>d.remove();
  const host=d.querySelector('.ji-list');
  d.querySelector('.ji-add').onclick=()=>{addIndRow(host).querySelector('.ji-iki').focus();};
  addIndRow(host);
  $('#judulRhkList').appendChild(d);return d;}
$('#judulRhkAdd').onclick=()=>{addJudulRhkRow().querySelector('.jr-nama').focus();};
function openAddJudul(){judulEditId=null;$('#judulTitle').textContent='➕ Buat SKP';$('#judulNama').value='';$('#judulTipe').value='tahun';$('#judulTipe').disabled=false;$('#judulTahun').value=String(new Date().getFullYear());$('#judulBulan').value=nowMonthWIB();
  $('#judulRhkList').innerHTML='';addJudulRhkRow();$('#judulRhkWrap').hidden=false;
  judulEkinFiles=[];judulEkinRender();$('#judulEkinWrap').hidden=false;
  toggleJudulTipe();$('#mJudul').classList.add('show');setTimeout(()=>$('#judulNama').focus(),50);}
function openEditJudul(k){judulEditId=k.id;$('#judulTitle').textContent='✏️ Edit SKP';$('#judulNama').value=k.nama||'';$('#judulTipe').value=k.tipe||'tahun';$('#judulTipe').disabled=true;$('#judulTahun').value=String(k.tahun||new Date().getFullYear());$('#judulBulan').value=(k.bulan&&/^\d{4}-\d{2}$/.test(k.bulan))?k.bulan:nowMonthWIB();
  $('#judulRhkWrap').hidden=true;$('#judulEkinWrap').hidden=true;toggleJudulTipe();$('#mJudul').classList.add('show');setTimeout(()=>$('#judulNama').focus(),50);}
$('#judulSave').onclick=()=>{const btn=$('#judulSave');if(btn.disabled)return;
  const nama=$('#judulNama').value.trim();if(!nama){toast('Judul SKP wajib diisi',true);return;}
  const tipe=$('#judulTipe').value;
  const f=new FormData();f.append('csrf',CSRF);f.append('nama',nama);f.append('tipe',tipe);if(judulEditId)f.append('id',judulEditId);
  if(tipe==='tahun'){const t=parseInt($('#judulTahun').value,10)||0;if(t<2000||t>2100){toast('Tahun tidak valid (2000–2100)',true);return;}f.append('tahun',t);}
  else{const b=$('#judulBulan').value;if(!b){toast('Pilih bulan',true);return;}f.append('bulan',b);}
  if(!judulEditId){
    const rows=[];
    $('#judulRhkList').querySelectorAll('.krow').forEach(r=>{const nm=r.querySelector('.jr-nama').value.trim();if(!nm)return;
      const ind=[];
      r.querySelectorAll('.irow').forEach(i=>{const iki=i.querySelector('.ji-iki').value.trim();if(!iki)return;
        ind.push({aspek:i.querySelector('.ji-aspek').value,iki,target:parseInt(i.querySelector('.ji-target').value,10)||0});});
      rows.push({nama:nm,kategori:r.querySelector('.jr-kat').value,indikator:ind});});
    f.append('rhkjson',JSON.stringify(rows));
    judulEkinFiles.forEach(x=>f.append('dok[]',x,x.name));
  }
  const save=async()=>{const o=btn.innerHTML;btn.disabled=true;btn.innerHTML='⏳ Menyimpan...';const act=judulEditId?'judul_update':'judul_add';
    try{await getJSON('api.php?action='+act,{method:'POST',body:f});$('#mJudul').classList.remove('show');$('#judulTipe').disabled=false;toast(judulEditId?'✓ SKP diperbarui':'✓ SKP dibuat');goHome();}
    catch(e){toast(e.message,true);}finally{btn.disabled=false;btn.innerHTML=o;}};
  if(judulEditId)askConfirm('Simpan perubahan?','SKP ini akan diperbarui.',save,{okText:'Simpan',danger:false});else save();};

/* ---------- Modal: RHK ---------- */
let rhkEditId=null, rhkEkinFiles=[];
const rhkEkinRender=makeDrop('rhkEkinDrop','rhkEkinFile','rhkEkinPending',()=>rhkEkinFiles);
function openAddRhk(){if(!curJudul){toast('Buka judul dulu',true);return;}rhkEditId=null;$('#rhkTitle').textContent='➕ RHK Baru';$('#rhkKategori').value='utama';$('#rhkPimpinan').value='';$('#rhkNama').value='';$('#rhkTarget').value='';rhkEkinFiles=[];rhkEkinRender();$('#rhkEkinWrap').hidden=false;$('#mRhk').classList.add('show');setTimeout(()=>$('#rhkNama').focus(),50);}
function openEditRhk(k){rhkEditId=k.id;$('#rhkTitle').textContent='✏️ Edit RHK';$('#rhkKategori').value=k.kategori||'utama';$('#rhkPimpinan').value=k.pimpinan||'';$('#rhkNama').value=k.nama||'';$('#rhkTarget').value=(k.target&&+k.target>0)?k.target:'';$('#rhkEkinWrap').hidden=true;$('#mRhk').classList.add('show');setTimeout(()=>$('#rhkNama').focus(),50);}
$('#rhkSave').onclick=()=>{const btn=$('#rhkSave');if(btn.disabled)return;
  const nama=$('#rhkNama').value.trim();if(!nama){toast('RHK wajib diisi',true);return;}
  const f=new FormData();f.append('csrf',CSRF);f.append('nama',nama);f.append('kategori',$('#rhkKategori').value);f.append('pimpinan',$('#rhkPimpinan').value.trim());f.append('target',parseInt($('#rhkTarget').value,10)||0);
  if(rhkEditId)f.append('id',rhkEditId); else f.append('judul_id',curJudul.id);
  if(!rhkEditId)rhkEkinFiles.forEach(x=>f.append('dok[]',x,x.name));
  const save=async()=>{const o=btn.innerHTML;btn.disabled=true;btn.innerHTML='⏳ Menyimpan...';const act=rhkEditId?'rhk_update':'rhk_add';
    try{await getJSON('api.php?action='+act,{method:'POST',body:f});$('#mRhk').classList.remove('show');toast(rhkEditId?'✓ RHK diperbarui':'✓ RHK dibuat');rhkEditId?openRhk(rhkEditId):openJudul(curJudul.id);}
    catch(e){toast(e.message,true);}finally{btn.disabled=false;btn.innerHTML=o;}};
  if(rhkEditId)askConfirm('Simpan perubahan?','RHK ini akan diperbarui.',save,{okText:'Simpan',danger:false});else save();};

/* ---------- Modal: Rencana Aksi ---------- */
let raksiEditId=null, raksiEkinFiles=[];
const raksiEkinRender=makeDrop('raksiEkinDrop','raksiEkinFile','raksiEkinPending',()=>raksiEkinFiles);
function fillBulanSelect(sel,def){let h='';for(let i=1;i<=12;i++)h+=`<option value="${i}"${i===def?' selected':''}>${BULAN[i-1]}</option>`;sel.innerHTML=h;}
function openAddRaksi(bulanPreset,reload){tblReload=reload||null;if(!curRhk){toast('Buka RHK dulu',true);return;}raksiEditId=null;$('#raksiTitle').textContent='➕ Rencana Aksi';
  fillBulanSelect($('#raksiBulan'),+wibParts().month);$('#raksiTarget').value='';$('#raksiNama').value='';
  const jb=bulanPreset?bulanPreset:((curJudul&&curJudul.tipe==='bulan')?(curJudul.bulan_ke||0):0);
  if(jb){$('#raksiBulan').value=String(jb);$('#raksiBulan').disabled=true;}else{$('#raksiBulan').disabled=false;}
  // salin dropdown dari rencana aksi yang sudah ada
  let opt='<option value="0">— tidak, buat baru —</option>';
  (curRhkRaksi||[]).forEach(s=>{opt+=`<option value="${s.id}">${BULAN[s.bulan_ke-1]} — ${esc((s.nama||'(tanpa judul)').slice(0,40))}</option>`;});
  $('#raksiSalin').innerHTML=opt; $('#raksiSalin').value='0'; $('#raksiSalinWrap').hidden=(curRhkRaksi||[]).length===0;
  raksiEkinFiles=[];raksiEkinRender();$('#raksiEkinWrap').hidden=false;
  $('#mRaksi').classList.add('show');setTimeout(()=>$('#raksiNama').focus(),50);}
$('#raksiSalin').onchange=()=>{const id=+$('#raksiSalin').value;if(!id)return;const s=(curRhkRaksi||[]).find(x=>x.id===id);if(s){$('#raksiNama').value=s.nama||'';}};
function openEditRaksi(s,reload){tblReload=reload||null;raksiEditId=s.id;$('#raksiTitle').textContent='✏️ Edit Rencana Aksi';
  fillBulanSelect($('#raksiBulan'),s.bulan_ke);$('#raksiBulan').disabled=true;$('#raksiTarget').value=(s.target&&+s.target>0)?s.target:'';$('#raksiNama').value=s.nama||'';
  $('#raksiSalinWrap').hidden=true;$('#raksiEkinWrap').hidden=true;
  $('#mRaksi').classList.add('show');setTimeout(()=>$('#raksiNama').focus(),50);}
$('#raksiSave').onclick=()=>{const btn=$('#raksiSave');if(btn.disabled)return;
  const nama=$('#raksiNama').value.trim();
  if(raksiEditId){
    const body=fd({id:raksiEditId,nama,target:parseInt($('#raksiTarget').value,10)||0});
    const save=async()=>{const o=btn.innerHTML;btn.disabled=true;btn.innerHTML='⏳...';
      try{await getJSON('api.php?action=raksi_update',{method:'POST',body});$('#mRaksi').classList.remove('show');$('#raksiBulan').disabled=false;toast('✓ Diperbarui');if(tblReload){tblReload();}else{openRhk(curRhk.id);}}
      catch(e){toast(e.message,true);}finally{btn.disabled=false;btn.innerHTML=o;}};
    askConfirm('Simpan perubahan?','Rencana aksi ini akan diperbarui.',save,{okText:'Simpan',danger:false});return;
  }
  const f=new FormData();f.append('csrf',CSRF);f.append('rhk_id',curRhk.id);f.append('bulan_ke',$('#raksiBulan').value);f.append('nama',nama);f.append('target',parseInt($('#raksiTarget').value,10)||0);f.append('salin_from',$('#raksiSalin').value||0);
  raksiEkinFiles.forEach(x=>f.append('dok[]',x,x.name));
  const save=async()=>{const o=btn.innerHTML;btn.disabled=true;btn.innerHTML='⏳ Menyimpan...';
    try{await getJSON('api.php?action=raksi_add',{method:'POST',body:f});$('#mRaksi').classList.remove('show');toast('✓ Rencana aksi dibuat');if(tblReload){tblReload();}else{openRhk(curRhk.id);}}
    catch(e){toast(e.message,true);}finally{btn.disabled=false;btn.innerHTML=o;}};
  save();};

/* ---------- Modal: Kelola RHK (form langsung dari tabel) ---------- */
let kelolaRhk=null, kelolaCtx=null, kelolaReload=null;
function kelolaIndRow(a){const d=document.createElement('div');d.innerHTML=
  `<div class="irow"><select class="ki-aspek"><option value="kuantitas">Kuantitas</option><option value="kualitas">Kualitas</option><option value="waktu">Waktu</option></select>`+
  `<textarea class="ki-iki" placeholder="Indikator Kinerja Individu (IKI)"></textarea>`+
  `<input type="text" inputmode="numeric" class="ki-target" placeholder="Target thn">`+
  `<button type="button" class="subrow-x ki-x" title="Hapus indikator">✕</button></div>`;
  const row=d.firstElementChild;
  if(a){row.querySelector('.ki-aspek').value=a.aspek||'kuantitas';row.querySelector('.ki-iki').value=a.iki||'';row.querySelector('.ki-target').value=(a.target&&+a.target>0)?a.target:'';row.dataset.indId=a.id||'';}
  row.querySelector('.ki-x').onclick=()=>row.remove();
  $('#kelolaIndList').appendChild(row);return row;}
$('#kelolaIndAdd').onclick=()=>{kelolaIndRow(null).querySelector('.ki-iki').focus();};
$('#kelolaCakupan').onchange=()=>{
  $('#kelolaCakupanHint').textContent = $('#kelolaCakupan').value==='bulan'
    ? 'Akan dibuatkan salinan RHK khusus bulan ini; bulan lain tetap memakai yang lama.'
    : 'RHK dipakai bersama tiap bulan, jadi perubahan akan terlihat di semua bulan.';};
function openKelolaRhk(x,j,reload){
  kelolaRhk=x; kelolaCtx=j; kelolaReload=reload||null;
  $('#kelolaTitle').textContent='⚙ Kelola RHK';
  $('#kelolaKat').value=x.kategori||'utama';
  $('#kelolaNama').value=x.nama||'';
  $('#kelolaIndList').innerHTML='';
  (x.indikator||[]).forEach(a=>kelolaIndRow(a));
  if(!(x.indikator||[]).length) kelolaIndRow(null);
  $('#kelolaCakupan').value='semua'; $('#kelolaCakupan').onchange();
  $('#mKelola').classList.add('show'); setTimeout(()=>$('#kelolaNama').focus(),50);
}
$('#kelolaSave').onclick=()=>{const btn=$('#kelolaSave'); if(btn.disabled)return;
  const nama=$('#kelolaNama').value.trim(); if(!nama){toast('Nama RHK wajib diisi',true);return;}
  const ind=[]; $('#kelolaIndList').querySelectorAll('.irow').forEach(r=>{const iki=r.querySelector('.ki-iki').value.trim(); if(!iki)return;
    ind.push({aspek:r.querySelector('.ki-aspek').value,iki,target:parseInt(r.querySelector('.ki-target').value,10)||0});});
  const cakupan=$('#kelolaCakupan').value, bk=kelolaCtx?kelolaCtx.bulan_ke:0;
  const pesan = cakupan==='bulan'
    ? `Perubahan hanya untuk <b>${kelolaCtx?kelolaCtx.bulan_nama:'bulan ini'}</b>. Bulan lain tetap memakai RHK yang lama. Lanjutkan?`
    : `Perubahan berlaku untuk <b>SEMUA bulan</b> (Januari–Desember). Lanjutkan?`;
  askConfirm('Simpan perubahan RHK?',pesan,async()=>{
    const o=btn.innerHTML; btn.disabled=true; btn.innerHTML='⏳ Menyimpan...';
    try{
      await getJSON('api.php?action=rhk_kelola',{method:'POST',body:fd({
        id:kelolaRhk.id, nama, kategori:$('#kelolaKat').value,
        cakupan, bulan_ke:bk, indjson:JSON.stringify(ind)})});
      $('#mKelola').classList.remove('show'); toast('✓ RHK diperbarui');
      if(kelolaReload)kelolaReload();
    }catch(e){toast(e.message,true);}finally{btn.disabled=false;btn.innerHTML=o;}
  },{okText:'Ya, simpan',danger:false});};
$('#kelolaHapus').onclick=()=>{
  askConfirm('Hapus RHK ini?',`"<b>${esc(kelolaRhk?kelolaRhk.nama:'')}</b>" beserta indikator, rencana aksi, realisasi & bukti dukungnya akan dihapus permanen dari SEMUA bulan. Yakin?`,async()=>{
    await getJSON('api.php?action=rhk_delete',{method:'POST',body:fd({id:kelolaRhk.id})});
    $('#mKelola').classList.remove('show'); toast('RHK dihapus'); if(kelolaReload)kelolaReload();
  },{okText:'Ya, hapus'});};
/* ---------- Modal: Indikator (aspek + IKI + target tahunan) ---------- */
let aspekEditId=null;
function openAddInd(){if(!curRhk){toast('Buka RHK dulu',true);return;}aspekEditId=null;$('#aspekTitle').textContent='➕ Indikator Kinerja Individu';$('#aspekSel').value='kuantitas';$('#aspekIki').value='';$('#aspekTarget').value='';$('#mAspek').classList.add('show');setTimeout(()=>$('#aspekIki').focus(),50);}
function openEditInd(a){if(!a)return;aspekEditId=a.id;$('#aspekTitle').textContent='✏️ Edit Indikator';$('#aspekSel').value=a.aspek||'kuantitas';$('#aspekIki').value=a.iki||'';$('#aspekTarget').value=(a.target&&+a.target>0)?a.target:'';$('#mAspek').classList.add('show');setTimeout(()=>$('#aspekIki').focus(),50);}
$('#aspekSave').onclick=()=>{const btn=$('#aspekSave');if(btn.disabled)return;
  const iki=$('#aspekIki').value.trim();if(!iki){toast('IKI wajib diisi',true);return;}
  const target=parseInt($('#aspekTarget').value,10)||0;
  const save=async()=>{const o=btn.innerHTML;btn.disabled=true;btn.innerHTML='⏳...';const act=aspekEditId?'indikator_update':'indikator_add';
    const body=aspekEditId?fd({id:aspekEditId,aspek:$('#aspekSel').value,iki,target}):fd({rhk_id:curRhk.id,aspek:$('#aspekSel').value,iki,target});
    try{await getJSON('api.php?action='+act,{method:'POST',body});$('#mAspek').classList.remove('show');toast(aspekEditId?'✓ Diperbarui':'✓ Indikator ditambahkan');openRhk(curRhk.id);}
    catch(e){toast(e.message,true);}finally{btn.disabled=false;btn.innerHTML=o;}};
  if(aspekEditId)askConfirm('Simpan perubahan?','Indikator ini akan diperbarui.',save,{okText:'Simpan',danger:false});else save();};

/* ---------- Modal: Edit Realisasi ---------- */
let harEditId=null;
function openEditHarian(h,reload){tblReload=reload||null;harEditId=h.id;$('#harUraian').value=h.uraian||'';$('#harJumlah').value=(h.jumlah&&+h.jumlah>0)?h.jumlah:'';$('#harTanggal').value=(h.created_at||'').slice(0,10);$('#mHarian').classList.add('show');setTimeout(()=>$('#harUraian').focus(),50);}
$('#harSave').onclick=()=>{const save=async()=>{try{await getJSON('api.php?action=realisasi_update',{method:'POST',body:fd({id:harEditId,uraian:$('#harUraian').value.trim(),jumlah:parseInt($('#harJumlah').value,10)||0,tanggal:$('#harTanggal').value||''})});
    $('#mHarian').classList.remove('show');toast('✓ Realisasi diperbarui');reloadRealisasi();}catch(e){toast(e.message,true);}};
  askConfirm('Simpan perubahan?','Realisasi ini akan diperbarui.',save,{okText:'Simpan',danger:false});};

$('#rhkNama').addEventListener('keydown',e=>{if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();$('#rhkSave').click();}});
$('#aspekIki').addEventListener('keydown',e=>{if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();$('#aspekSave').click();}});

bukaDariHash();
</script>
</body>
</html>
