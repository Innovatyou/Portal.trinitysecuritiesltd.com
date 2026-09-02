<div id="page-content" class="page-wrapper clearfix">
    <?php
    load_css(array(
        "assets/css/invoice.css",
    ));
    ?>

    <div class="invoice-preview">
        <?php if ($login_user->user_type === "client" && $invoice_total_summary->balance_due >= 1 && count($payment_methods) && !$client_info->disable_online_payment && $invoice_info->status !== "credited" && $invoice_info->status !== "cancelled") { ?>
            <div class="card d-block p15 no-border clearfix invoice-payment-button pb-0">

                <?php echo view("invoices/payments/online_payments", array(
                    "invoice_total_summary" => $invoice_total_summary,
                    "invoice_info" => $invoice_info,
                    "invoice_id" => $invoice_id,
                    "payment_methods" => $payment_methods,
                )); ?>

                <div class="float-end">
                    <?php
                    echo "<div class='text-center'>" . anchor("invoices/download_pdf/" . $invoice_info->id, app_lang("download_pdf"), array("class" => "btn btn-default round")) . "</div>"
                    ?>
                </div>

            </div>
        <?php
        } else if ($login_user->user_type === "client") {
            echo "<div class='text-center'>" . anchor("invoices/download_pdf/" . $invoice_info->id, app_lang("download_pdf"), array("class" => "btn btn-default round")) . "</div>";
        }


        if ($show_close_preview) {
            echo "<div class='text-center'>" . anchor("invoices/view/" . $invoice_info->id, app_lang("close_preview"), array("class" => "btn btn-default round")) . "</div>";
        }
        ?>

        <div id="invoice-preview" class="invoice-preview-container bg-white mt15">
            <?php if ($invoice_info->type == "invoice") { ?>
                <div class="row">
                    <div class="col-md-12 position-relative">
                        <div class="ribbon"><?php echo $invoice_status_label; ?></div>
                    </div>
                </div>
            <?php
            }
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
</script>