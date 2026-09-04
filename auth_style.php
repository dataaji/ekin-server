<?php /* CSS bersama untuk halaman login & setup */ ?>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
    background:linear-gradient(135deg,#0f766e,#0d9488);min-height:100vh;
    display:grid;place-items:center;padding:20px;color:#0f172a}
  .box{background:#fff;border-radius:18px;padding:30px 26px;width:100%;max-width:390px;
    box-shadow:0 20px 60px rgba(0,0,0,.25)}
  .logo{width:54px;height:54px;border-radius:14px;background:#ccfbf1;display:grid;place-items:center;
    font-size:28px;margin:0 auto 14px}
  h1{font-size:21px;text-align:center;font-weight:800}
  .sub{text-align:center;color:#64748b;font-size:13px;margin:4px 0 22px}
  label{display:block;font-size:13px;font-weight:600;margin:0 0 6px;color:#334155}
  .field{margin-bottom:14px}
  input{width:100%;border:1.5px solid #e2e8f0;border-radius:10px;padding:12px 13px;font:inherit;color:#0f172a}
  input:focus{outline:none;border-color:#0d9488;box-shadow:0 0 0 3px #ccfbf1}
  button{width:100%;background:#0d9488;color:#fff;border:none;border-radius:10px;padding:13px;
    font:inherit;font-weight:700;font-size:15px;cursor:pointer;margin-top:4px}
  button:hover{filter:brightness(1.06)}
  .err{background:#fee2e2;color:#b91c1c;border-radius:10px;padding:10px 12px;font-size:13px;margin-bottom:16px}
  .hint{font-size:12px;color:#94a3b8;margin-top:16px;text-align:center;line-height:1.5}
</style>
