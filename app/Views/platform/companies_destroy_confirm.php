<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Delete company</title>
<style>
body{font-family:sans-serif;background:#0f172a;color:#e2e8f0;margin:0;padding:32px}
.box{max-width:520px}
h1{font-size:20px;color:#f87171}
label{display:block;font-size:13px;margin:14px 0 4px;color:#94a3b8}
input{width:100%;box-sizing:border-box;padding:8px;border-radius:4px;border:1px solid #334155;background:#1e293b;color:#e2e8f0}
button.danger{margin-top:20px;padding:10px 18px;border:0;border-radius:4px;background:#dc2626;color:#fff;cursor:pointer;font-weight:bold}
a{color:#94a3b8;font-size:13px}
.warning{background:#450a0a;border:1px solid #7f1d1d;color:#fecaca;padding:14px;border-radius:6px;font-size:14px;margin-top:16px}
.warning ul{margin:8px 0 0;padding-left:20px}
</style>
</head>
<body>
<div class="box">
<p><a href="<?= site_url('platform_companies') ?>">&larr; Companies</a></p>
<h1>Delete "<?= esc($company->name) ?>"?</h1>
<div class="warning">
This permanently deletes, with no undo:
<ul>
<li>Their entire database (<code><?= esc($company->db_database) ?></code>) - every project, invoice, ticket, approval request, everything</li>
<li>Their uploaded files</li>
<li>Their domain registration(s)</li>
</ul>
Their admin login and every staff/client account they created stop working immediately.
</div>
<form method="post" action="<?= site_url('platform_companies/destroy') ?>">
<input type="hidden" name="id" value="<?= (int) $company->id ?>">
<label>Type <strong><?= esc($company->slug) ?></strong> to confirm</label>
<input name="confirm_slug" autocomplete="off" autofocus required>
<button class="danger" type="submit">Permanently delete this company</button>
</form>
</div>
</body>
</html>
