<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Group admins</title>
<style>
body{font-family:sans-serif;background:#0f172a;color:#e2e8f0;margin:0;padding:32px}
h1{font-size:20px;display:flex;align-items:center;justify-content:space-between}
a.btn{background:#2563eb;color:#fff;padding:8px 14px;border-radius:4px;text-decoration:none;font-size:14px}
a.logout{color:#94a3b8;font-size:13px;text-decoration:none}
table{width:100%;border-collapse:collapse;margin-top:20px}
th,td{text-align:left;padding:10px;border-bottom:1px solid #334155;font-size:14px;vertical-align:top}
th{color:#94a3b8;font-weight:normal}
.flash{padding:10px;border-radius:4px;margin-top:16px;font-size:14px}
.flash-success{background:#14532d;color:#bbf7d0}
.flash-error{background:#7f1d1d;color:#fecaca}
.top{display:flex;justify-content:space-between;align-items:center}
.nav{margin-bottom:12px}
.nav a{color:#94a3b8;font-size:13px;text-decoration:none;margin-right:16px}
a.view-link{color:#93c5fd;font-size:13px;text-decoration:none}
</style>
</head>
<body>
<div class="nav"><a href="<?= site_url('platform_companies') ?>">Companies</a><a href="<?= site_url('platform_group_admins') ?>" style="color:#e2e8f0">Group admins</a></div>
<div class="top">
<h1 style="margin:0">Group admins</h1>
<div><a class="btn" href="<?= site_url('platform_group_admins/create') ?>">+ Add group admin</a> &nbsp; <a class="logout" href="<?= site_url('platform_auth/logout') ?>">Sign out</a></div>
</div>
<p style="color:#94a3b8;font-size:13px;max-width:640px">A group admin signs in once and can be granted full admin access into several companies - useful for a group MD who oversees more than one company under this SaaS.</p>
<?php if (session()->getFlashdata('success')) : ?><div class="flash flash-success"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>
<?php if (session()->getFlashdata('error')) : ?><div class="flash flash-error"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>
<table>
<thead><tr><th>Name</th><th>Email</th><th>Companies</th><th>Last sign-in</th><th></th></tr></thead>
<tbody>
<?php foreach ($group_admins as $g) : ?>
<tr>
<td><?= esc($g->first_name . ' ' . $g->last_name) ?></td>
<td><?= esc($g->email) ?></td>
<td><?= (int) $g->company_count ?></td>
<td><?= esc($g->last_login_at ?? 'Never') ?></td>
<td><a class="view-link" href="<?= site_url('platform_group_admins/show/' . $g->id) ?>">Manage access</a></td>
</tr>
<?php endforeach; ?>
<?php if (!count($group_admins)) : ?><tr><td colspan="5">No group admins yet.</td></tr><?php endif; ?>
</tbody>
</table>
</body>
</html>
