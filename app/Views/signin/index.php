<!DOCTYPE html>
<html lang="en">

<head>
    <?php echo view('includes/head'); ?>
</head>

<body class="public-view signin-page">
    <style type="text/css">
        :root { --workflow-lime:#a8d600; --workflow-lime-dark:#76a900; --workflow-ink:#13210b; }
        html, body { min-height:100%; background:#f6fae9; }
        .signin-page .scrollable-page { min-height:100vh; display:flex; overflow:hidden; position:relative; }
        .workflow-brand-panel { width:58%; min-height:100vh; position:relative; overflow:hidden; display:flex; align-items:flex-end; padding:clamp(36px,6vw,92px); color:#fff; background-image:linear-gradient(145deg,rgba(7,18,11,.32),rgba(10,25,6,.82)),url('<?php echo base_url("assets/images/workflow-login-lagos.png"); ?>'); background-size:cover; background-position:center; }
        .workflow-brand-panel:before,.workflow-brand-panel:after { content:""; position:absolute; border-radius:34px; background:linear-gradient(145deg,rgba(168,214,0,.34),rgba(255,255,255,.06)); box-shadow:22px 26px 55px rgba(0,0,0,.28),inset 1px 1px 1px rgba(255,255,255,.45); transform:rotate(18deg); backdrop-filter:blur(4px); }
        .workflow-brand-panel:before { width:210px;height:210px;right:-55px;top:12%; }
        .workflow-brand-panel:after { width:110px;height:110px;left:9%;top:10%;transform:rotate(-16deg); }
        .workflow-brand-copy { position:relative;z-index:2;max-width:680px;text-shadow:0 4px 18px rgba(0,0,0,.4); }
        .workflow-kicker { display:inline-flex;align-items:center;gap:9px;padding:9px 14px;border:1px solid rgba(255,255,255,.32);border-radius:999px;background:rgba(11,22,8,.34);backdrop-filter:blur(12px);font-weight:700;letter-spacing:.08em;text-transform:uppercase;font-size:12px; }
        .workflow-kicker i { width:9px;height:9px;border-radius:50%;background:var(--workflow-lime);box-shadow:0 0 18px var(--workflow-lime); }
        .workflow-brand-copy h1 { margin:22px 0 14px;font-size:clamp(42px,5vw,76px);line-height:.98;font-weight:800;letter-spacing:-.04em; }
        .workflow-brand-copy p { margin:0;max-width:620px;font-size:18px;line-height:1.65;color:rgba(255,255,255,.88); }
        .signin-page .form-signin { width:42%;min-width:430px;min-height:100vh!important;height:auto!important;display:flex;flex-direction:column;justify-content:center;padding:54px clamp(32px,5vw,88px);background:radial-gradient(circle at 100% 0,rgba(168,214,0,.20),transparent 34%),#f8fbf2;position:relative; }
        .signin-page .form-signin:before { content:"";position:absolute;width:170px;height:170px;border-radius:38px;right:-70px;bottom:8%;transform:rotate(28deg);background:linear-gradient(145deg,#bce62c,#78a700);box-shadow:20px 24px 50px rgba(90,125,0,.22); }
        .signin-page .form-signin .card { position:relative;z-index:1;border:0!important;border-radius:30px!important;background:rgba(255,255,255,.86)!important;box-shadow:0 28px 80px rgba(29,52,9,.14),inset 0 1px 0 #fff;backdrop-filter:blur(18px);overflow:hidden; }
        .signin-page .card-header { border:0;background:transparent;padding:38px 38px 10px; }
        .signin-page .card-header img { max-height:72px;max-width:220px;object-fit:contain; }
        .signin-page .card-body { padding:22px 42px 42px!important; }
        .signin-page .form-control { height:54px;border:1px solid #dce5d0;border-radius:15px;background:#fbfdf7;box-shadow:inset 0 2px 5px rgba(25,45,8,.04);transition:.2s ease; }
        .signin-page .form-control:focus { border-color:var(--workflow-lime-dark);box-shadow:0 0 0 4px rgba(168,214,0,.18);background:#fff; }
        .signin-page .btn-primary { height:54px;border:0;border-radius:15px;background:linear-gradient(135deg,var(--workflow-lime),var(--workflow-lime-dark));color:var(--workflow-ink);font-weight:800;box-shadow:0 14px 28px rgba(118,169,0,.28);transition:transform .2s ease,box-shadow .2s ease; }
        .signin-page .btn-primary:hover { transform:translateY(-2px);box-shadow:0 18px 34px rgba(118,169,0,.36);color:var(--workflow-ink); }
        .signin-page a { color:#669600;font-weight:600; }
        .workflow-form-title { margin:8px 0 5px;color:#17210f;font-size:28px;font-weight:800;letter-spacing:-.02em; }
        .workflow-form-subtitle { color:#6c7664;margin:0; }
        .signin-page footer { display:none; }
        @media(max-width:991px){ .signin-page .scrollable-page{overflow-y:auto;height:auto}.workflow-brand-panel{display:none}.signin-page .form-signin{width:100%;min-width:0;padding:28px 18px;background-image:linear-gradient(rgba(10,22,7,.68),rgba(246,250,233,.94) 48%),url('<?php echo base_url("assets/images/workflow-login-lagos.png"); ?>');background-size:cover;background-position:center top}.signin-page .form-signin .card{max-width:520px;width:100%;margin:auto}.signin-page .card-body{padding:18px 28px 32px!important} }
    </style>

    <div class="scrollable-page">
        <section class="workflow-brand-panel" aria-label="WorkFlow operations team">
            <div class="workflow-brand-copy">
                <span class="workflow-kicker"><i></i> Operations and approvals</span>
                <h1>Move work forward.</h1>
                <p>Create requests, review supporting documents, approve decisions, and follow every action through a complete audit trail—from anywhere.</p>
            </div>
        </section>
        <div class="form-signin">
            <?php
            if (isset($form_type) && $form_type == "request_reset_password") {
                echo view("signin/reset_password_form");
            } else if (isset($form_type) && $form_type == "new_password") {
                echo view('signin/new_password_form');
            } else {
                echo view("signin/signin_form");
            }
            ?>
        </div>

        <?php echo view("includes/footer"); ?>
    </div>

    <script>
        $(document).ready(function() {
            $(".form-signin").css("height", "auto");
        });
    </script>

</body>

</html>
