<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Switch company</title>
<style>
body{font-family:sans-serif;background:#f8fafc;color:#0f172a;margin:0;padding:32px;display:flex;justify-content:center}
.box{max-width:480px;width:100%}
h1{font-size:20px;display:flex;align-items:center;justify-content:space-between}
p.sub{color:#64748b;font-size:13px}
a.logout{color:#64748b;font-size:13px;text-decoration:none}
.company{display:flex;align-items:center;justify-content:space-between;background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:14px 16px;margin-top:10px}
.company .name{font-weight:600}
.company .domain{color:#64748b;font-size:13px}
a.switch{background:#2563eb;color:#fff;padding:8px 14px;border-radius:4px;text-decoration:none;font-size:13px}
.error{background:#fef2f2;color:#b91c1c;padding:10px;border-radius:4px;font-size:13px;margin-top:12px}
.empty{color:#64748b;font-size:14px;margin-top:20px}
</style>
</head>
<body>
<div class="box">
<h1>Your companies <a class="logout" href="<?= site_url('group_portal/logout') ?>">Sign out</a></h1>
<p class="sub">Signed in as <?= esc($group_admin->first_name . ' ' . $group_admin->last_name) ?>. Pick a company to switch into.</p>
<?php if (!empty($error)) : ?><div class="error"><?= esc($error) ?></div><?php endif; ?>
<?php foreach ($companies as $c) : ?>
<div class="company">
<div>
<div class="name"><?= esc($c->name) ?></div>
<div class="domain"><?= esc($c->domain) ?></div>
</div>
<a class="switch" href="<?= site_url('group_portal/switch_to/' . $c->id) ?>">Switch to</a>
</div>
<?php endforeach; ?>
<?php if (!count($companies)) : ?><p class="empty">You haven't been granted access to any company yet.</p><?php endif; ?>
</div>
</body>
</html>
