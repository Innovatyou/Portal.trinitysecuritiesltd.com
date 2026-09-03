<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Add group admin</title>
<style>
body{font-family:sans-serif;background:#0f172a;color:#e2e8f0;margin:0;padding:32px}
.box{max-width:480px}
h1{font-size:20px}
label{display:block;font-size:13px;margin:14px 0 4px;color:#94a3b8}
input{width:100%;box-sizing:border-box;padding:8px;border-radius:4px;border:1px solid #334155;background:#1e293b;color:#e2e8f0}
button{margin-top:20px;padding:10px 18px;border:0;border-radius:4px;background:#2563eb;color:#fff;cursor:pointer}
.error{background:#7f1d1d;color:#fecaca;padding:8px;border-radius:4px;font-size:13px;margin-bottom:12px}
a{color:#94a3b8;font-size:13px}
</style>
</head>
<body>
<div class="box">
<p><a href="<?= site_url('platform_group_admins') ?>">&larr; Group admins</a></p>
<h1>Add group admin</h1>
<?php if (!empty($error)) : ?><div class="error"><?= esc($error) ?></div><?php endif; ?>
<form method="post" action="<?= site_url('platform_group_admins/store') ?>">
<label>First name</label>
<input name="first_name" value="<?= esc($old['first_name'] ?? '') ?>" required>
<label>Last name</label>
<input name="last_name" value="<?= esc($old['last_name'] ?? '') ?>" required>
<label>Email</label>
<input type="email" name="email" value="<?= esc($old['email'] ?? '') ?>" required>
<label>Password</label>
<input type="password" name="password" required>
<button type="submit">Create group admin</button>
</form>
</div>
</body>
</html>
