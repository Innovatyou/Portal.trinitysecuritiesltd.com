<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Manage access</title>
<style>
body{font-family:sans-serif;background:#0f172a;color:#e2e8f0;margin:0;padding:32px}
.box{max-width:640px}
h1{font-size:20px}
table{width:100%;border-collapse:collapse;margin-top:16px}
th,td{text-align:left;padding:10px;border-bottom:1px solid #334155;font-size:14px;vertical-align:top}
th{color:#94a3b8;font-weight:normal}
.flash{padding:10px;border-radius:4px;margin:16px 0;font-size:14px}
.flash-success{background:#14532d;color:#bbf7d0}
.flash-error{background:#7f1d1d;color:#fecaca}
a{color:#94a3b8;font-size:13px;text-decoration:none}
button.revoke{background:none;border:1px solid #7f1d1d;color:#f87171;border-radius:4px;padding:4px 10px;font-size:12px;cursor:pointer}
.grant-form{margin-top:24px;display:flex;gap:8px;align-items:center}
select{padding:8px;border-radius:4px;border:1px solid #334155;background:#1e293b;color:#e2e8f0}
button.grant{padding:8px 14px;border:0;border-radius:4px;background:#2563eb;color:#fff;cursor:pointer}
.badge{font-size:11px;padding:2px 6px;border-radius:8px;background:#334155;color:#94a3b8;margin-left:6px}
</style>
</head>
<body>
<div class="box">
<p><a href="<?= site_url('platform_group_admins') ?>">&larr; Group admins</a></p>
<h1><?= esc($group_admin->first_name . ' ' . $group_admin->last_name) ?></h1>
<p style="color:#94a3b8;font-size:13px"><?= esc($group_admin->email) ?></p>
<?php if (session()->getFlashdata('success')) : ?><div class="flash flash-success"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>
<?php if (session()->getFlashdata('error')) : ?><div class="flash flash-error"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>

<table>
<thead><tr><th>Company</th><th>Granted</th><th></th></tr></thead>
<tbody>
<?php foreach ($access as $a) : ?>
<tr>
<td><?= esc($a->name) ?><?php if (!$a->created_by_grant) : ?><span class="badge">linked existing account</span><?php endif; ?></td>
<td><?= esc($a->granted_at) ?></td>
<td>
<form method="post" action="<?= site_url('platform_group_admins/revoke') ?>" onsubmit="return confirm('Revoke access to <?= esc($a->name, 'js') ?>?');">
<input type="hidden" name="access_id" value="<?= (int) $a->id ?>">
<input type="hidden" name="group_admin_id" value="<?= (int) $group_admin->id ?>">
<button class="revoke" type="submit">Revoke</button>
</form>
</td>
</tr>
<?php endforeach; ?>
<?php if (!count($access)) : ?><tr><td colspan="3">No company access granted yet.</td></tr><?php endif; ?>
</tbody>
</table>

<?php if (count($available)) : ?>
<form class="grant-form" method="post" action="<?= site_url('platform_group_admins/grant') ?>">
<input type="hidden" name="group_admin_id" value="<?= (int) $group_admin->id ?>">
<select name="tenant_id" required>
<?php foreach ($available as $t) : ?>
<option value="<?= (int) $t->id ?>"><?= esc($t->name) ?></option>
<?php endforeach; ?>
</select>
<button class="grant" type="submit">Grant access</button>
</form>
<?php endif; ?>
</div>
</body>
</html>
