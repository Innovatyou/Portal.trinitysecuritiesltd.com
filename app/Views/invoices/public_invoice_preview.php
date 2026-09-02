<!DOCTYPE html>
<html lang="en">

<head>
    <?php echo view('includes/head'); ?>
</head>

<body>
    <div id="page-content" class="page-wrapper clearfix public-invoice-preview">
        <?php
        load_css(array(
            "assets/css/invoice.css",
        ));
        ?>

        <div class="invoice-preview">
            <?php if ($invoice_total_summary->balance_due >= 1 && count($payment_methods) && !$client_info->disable_online_payment) { ?>
                <div class="card d-block p15 no-border clearfix invoice-payment-button pb0">

                    <?php echo view("invoices/payments/online_payments", array(
                        "invoice_total_summary" => $invoice_total_summary,
                        "invoice_info" => $invoice_info,
                        "invoice_id" => $invoice_id,
                        "payment_methods" => $payment_methods,
                        "contact_user_id" => $contact_id,
                        "verification_code" => $verification_code
                    )); ?>

                </div>
            <?php } ?>

            <div id="invoice-preview" class="invoice-preview-container bg-white mt15">
                <div class="row">
                    <div class="col-md-12 position-relative">
                        <div class="ribbon"><?php echo $invoice_status_label; ?></div>
                    </div>
                </div>

                <?php
                echo $invoice_preview;
                ?>
            </div>

        </div>
    </div>

    <script type="text/javascript">
        $(document).ready(function() {
            $("#payment-amount").change(function() {
                var value = $(this).val();
                $(".payment-amount-field").each(function() {
                    $(this).val(value);
                });
            });
        });

        $("html, body").css({
            "overflow-y": "auto"
        });
    </script>
</body>

</html>