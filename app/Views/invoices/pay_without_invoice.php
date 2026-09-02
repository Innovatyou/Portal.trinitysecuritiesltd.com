<div id="page-content" class="page-wrapper clearfix">
    <?php
    load_css(array(
        "assets/css/invoice.css",
    ));
    ?>

    <div class="invoice-preview">
        <?php if ($login_user->user_type === "client" && $invoice_total_summary->balance_due >= 1 && count($payment_methods) && !$client_info->disable_online_payment) { ?>
            <div class="card d-block p15 no-border clearfix invoice-payment-button pb-0">

                <?php echo view("invoices/payments/online_payments", array(
                    "invoice_total_summary" => $invoice_total_summary,
                    "invoice_info" => $invoice_info,
                    "invoice_id" => $invoice_id,
                    "payment_methods" => $payment_methods,
                    "contact_user_id" => $contact_user_id,
                    "verification_code" => $verification_code
                )); ?>

            </div>
        <?php } ?>

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
</script>