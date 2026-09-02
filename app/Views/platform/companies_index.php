<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Companies</title>
<style>
body{font-family:sans-serif;background:#0f172a;color:#e2e8f0;margin:0;padding:32px}
h1{font-size:20px;display:flex;align-items:center;justify-content:space-between}
a.btn{background:#2563eb;color:#fff;padding:8px 14px;border-radius:4px;text-decoration:none;font-size:14px}
a.logout{color:#94a3b8;font-size:13px;text-decoration:none}
table{width:100%;border-collapse:collapse;margin-top:20px}
th,td{text-align:left;padding:10px;border-bottom:1px solid #334155;font-size:14px}
th{color:#94a3b8;font-weight:normal}
.status{padding:2px 8px;border-radius:10px;font-size:12px}
.status-active{background:#14532d;color:#bbf7d0}
.status-suspended{background:#7f1d1d;color:#fecaca}
.status-provisioning{background:#78350f;color:#fde68a}
.flash{background:#14532d;color:#bbf7d0;padding:10px;border-radius:4px;margin-top:16px;font-size:14px}
.top{display:flex;justify-content:space-between;align-items:center}
</style>
</head>
<body>
<div class="top">
<h1 style="margin:0">Companies</h1>
<div><a class="btn" href="<?= site_url('platform_companies/create') ?>">+ Add company</a> &nbsp; <a class="logout" href="<?= site_url('platform_auth/logout') ?>">Sign out</a></div>
</div>
<?php if (session()->getFlashdata('success')) : ?><div class="flash"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>
<table>
<thead><tr><th>Name</th><th>Slug</th><th>Domain(s)</th><th>Status</th><th>Created</th></tr></thead>
<tbody>
<?php foreach ($companies as $c) : ?>
<tr>
<td><?= esc($c->name) ?></td>
<td><?= esc($c->slug) ?></td>
<td><?= esc($c->domains ?? '') ?></td>
<td><span class="status status-<?= esc($c->status) ?>"><?= esc($c->status) ?></span></td>
<td><?= esc($c->created_at) ?></td>
</tr>
<?php endforeach; ?>
<?php if (!count($companies)) : ?><tr><td colspan="5">No companies yet.</td></tr><?php endif; ?>
</tbody>
</table>
</body>
</html>
