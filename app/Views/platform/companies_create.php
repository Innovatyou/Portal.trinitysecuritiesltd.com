<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Add company</title>
<style>
body{font-family:sans-serif;background:#0f172a;color:#e2e8f0;margin:0;padding:32px}
.box{max-width:480px}
h1{font-size:20px}
label{display:block;font-size:13px;margin:14px 0 4px;color:#94a3b8}
input{width:100%;box-sizing:border-box;padding:8px;border-radius:4px;border:1px solid #334155;background:#1e293b;color:#e2e8f0}
button{margin-top:20px;padding:10px 18px;border:0;border-radius:4px;background:#2563eb;color:#fff;cursor:pointer}
.error{background:#7f1d1d;color:#fecaca;padding:8px;border-radius:4px;font-size:13px;margin-bottom:12px}
a{color:#94a3b8;font-size:13px}
small{color:#64748b}
</style>
</head>
<body>
<div class="box">
<p><a href="<?= site_url('platform_companies') ?>">&larr; Companies</a></p>
<h1>Add company</h1>
<?php if (!empty($error)) : ?><div class="error"><?= esc($error) ?></div><?php endif; ?>
<form method="post" action="<?= site_url('platform_companies/store') ?>">
<label>Company name</label>
<input name="name" value="<?= esc($old['name'] ?? '') ?>" required>
<label>Slug <small>(lowercase, used for the database name and file storage - can't be changed later)</small></label>
<input name="slug" pattern="[a-z][a-z0-9_]{2,40}" value="<?= esc($old['slug'] ?? '') ?>" required>
<label>Domain <small>(must already point its DNS at this server)</small></label>
<input name="domain" value="<?= esc($old['domain'] ?? '') ?>" required>
<label>Admin first name</label>
<input name="admin_first" value="<?= esc($old['admin_first'] ?? '') ?>" required>
<label>Admin last name</label>
<input name="admin_last" value="<?= esc($old['admin_last'] ?? '') ?>" required>
<label>Admin email</label>
<input type="email" name="admin_email" value="<?= esc($old['admin_email'] ?? '') ?>" required>
<label>Admin password</label>
<input type="password" name="admin_password" required>
<button type="submit">Provision company</button>
</form>
</div>
</body>
</html>
