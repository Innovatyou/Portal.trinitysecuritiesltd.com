<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Group sign in</title>
<style>
body{font-family:sans-serif;background:#f8fafc;color:#0f172a;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
.box{background:#fff;padding:32px;border-radius:8px;width:320px;box-shadow:0 1px 3px rgba(0,0,0,.1)}
h1{font-size:18px;margin:0 0 4px}
p.sub{color:#64748b;font-size:13px;margin:0 0 16px}
label{display:block;font-size:13px;margin:12px 0 4px;color:#475569}
input{width:100%;box-sizing:border-box;padding:8px;border-radius:4px;border:1px solid #cbd5e1;background:#fff;color:#0f172a}
button{margin-top:16px;width:100%;padding:10px;border:0;border-radius:4px;background:#2563eb;color:#fff;cursor:pointer}
.error{background:#fef2f2;color:#b91c1c;padding:8px;border-radius:4px;font-size:13px;margin-bottom:12px}
</style>
</head>
<body>
<div class="box">
<h1>Group sign in</h1>
<p class="sub">One login, every company you've been granted access to.</p>
<?php if (!empty($error)) : ?><div class="error"><?= esc($error) ?></div><?php endif; ?>
<form method="post" action="<?= site_url('group_auth/login') ?>">
<label>Email</label>
<input type="email" name="email" required autofocus>
<label>Password</label>
<input type="password" name="password" required>
<button type="submit">Sign in</button>
</form>
</div>
</body>
</html>
