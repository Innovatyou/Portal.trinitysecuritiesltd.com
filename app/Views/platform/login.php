<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Platform admin - sign in</title>
<style>
body{font-family:sans-serif;background:#0f172a;color:#e2e8f0;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
.box{background:#1e293b;padding:32px;border-radius:8px;width:320px}
h1{font-size:18px;margin:0 0 16px}
label{display:block;font-size:13px;margin:12px 0 4px;color:#94a3b8}
input{width:100%;box-sizing:border-box;padding:8px;border-radius:4px;border:1px solid #334155;background:#0f172a;color:#e2e8f0}
button{margin-top:16px;width:100%;padding:10px;border:0;border-radius:4px;background:#2563eb;color:#fff;cursor:pointer}
.error{background:#7f1d1d;color:#fecaca;padding:8px;border-radius:4px;font-size:13px;margin-bottom:12px}
</style>
</head>
<body>
<div class="box">
<h1>Platform admin</h1>
<?php if (!empty($error)) : ?><div class="error"><?= esc($error) ?></div><?php endif; ?>
<form method="post" action="<?= site_url('platform_auth/login') ?>">
<label>Email</label>
<input type="email" name="email" required autofocus>
<label>Password</label>
<input type="password" name="password" required>
<button type="submit">Sign in</button>
</form>
</div>
</body>
</html>
