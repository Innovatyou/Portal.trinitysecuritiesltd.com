<!-- Important: Don't change the id: payment-amount -->

<?php if ($invoice_id) { ?>
    <div class="inline-block strong float-start pt5 pr15">
        <?php echo app_lang("pay_invoice"); ?>:
    </div>
<?php } else { ?>
    <div class="mb15">
        <?php echo app_lang("process_your_payment_to_place_the_order"); ?>
    </div>
<?php } ?>

<div class="mr15 strong float-start general-form" style="width: 145px;">
    <?php if (get_setting("allow_partial_invoice_payment_from_clients") && $invoice_id) { ?>
        <span class="invoice-payment-amount-section" style="background-color: #f6f8f9; display: inline-block; padding: 8px 2px 7px 10px;"><?php echo $invoice_total_summary->currency; ?></span><input type="text" id="payment-amount" value="<?php echo to_decimal_format($invoice_total_summary->balance_due); ?>" class="form-control inline-block fw-bold" style="padding-left: 3px; width: 100px" />
    <?php } else { ?>
        <span class="pt5 inline-block">
            <?php echo to_currency($invoice_total_summary->balance_due, $invoice_total_summary->currency . " "); ?>
        </span>
    <?php } ?>
</div>

<?php
$verification_code = isset($verification_code) ? $verification_code : "";
$contact_user_id = isset($contact_user_id) ? $contact_user_id : "";
echo form_open(get_uri("pay_invoice/init_online_payment"), array("id" => "online-payment-form", "class" => "float-start", "role" => "form"));
?>

<input type="hidden" name="invoice_id" value="<?php echo $invoice_id; ?>" />
<input type="hidden" name="payment_amount" value="<?php echo to_decimal_format($invoice_total_summary->balance_due); ?>" id="online-payment-amount-field" />
<input type="hidden" name="verification_code" value="<?php echo $verification_code; ?>" id="online-payment-verification-code" />
<input type="hidden" name="contact_user_id" value="<?php echo $contact_user_id; ?>" id="online-payment-contact-user-id" />

<input type="hidden" name="currency" value="<?php echo $invoice_total_summary->currency; ?>" />
<input type="hidden" name="balance_due" value="<?php echo $invoice_total_summary->balance_due; ?>" />
<input type="hidden" name="client_id" value="<?php echo $invoice_info->client_id; ?>" />
<input type="hidden" name="payment_method_id" value="" />
<input type="hidden" name="description" value="<?php echo $invoice_id ? app_lang("pay_invoice") : app_lang("process_payment"); ?>: (<?php echo to_currency($invoice_total_summary->balance_due, $invoice_total_summary->currency . " "); ?>)" id="online-payment-description" />

<?php echo form_close(); ?>

<?php
foreach ($payment_methods as $payment_method) {

    $method_type = get_array_value($payment_method, "type");

    $pass_variables = array(
        "payment_method" => $payment_method,
        "balance_due" => $invoice_total_summary->balance_due,
        "currency" => $invoice_total_summary->currency,
        "invoice_info" => $invoice_info,
        "invoice_id" => $invoice_id,
        "contact_user_id" => $contact_user_id,
        "verification_code" => $verification_code
    );

    if ($invoice_total_summary->balance_due >= get_array_value($payment_method, "minimum_payment_amount")) {
        if ($method_type == "stripe" || $method_type == "paypal_payments_standard") {
            echo "<button type='button' data-id='" . get_array_value($payment_method, "id") . "' data-minimum-payment-amount='" . get_array_value($payment_method, "minimum_payment_amount") . "' class='btn btn-primary mr15 spinning-btn online-payment-button'>" . get_array_value($payment_method, "pay_button_text") . "</button>";
        } else if ($method_type == "paytm") {
            echo view("invoices/_paytm_payment_form", $pass_variables);
        }

        app_hooks()->do_action('app_hook_invoice_payment_extension', array_merge(array("method_type" => $method_type), $pass_variables));
    }
}
?>

<script type="text/javascript">
    $(document).ready(function() {
        var currency = "<?php echo $invoice_total_summary->currency . ' '; ?>",
            payInvoiceText = "<?php echo app_lang("pay_invoice"); ?>";
        var $button = $(".online-payment-button");

        $("#online-payment-form").appForm({
            isModal: false,
            onSuccess: function(response) {
                if (response.success && response.checkout_url) {
                    window.location.href = response.checkout_url;
                }
            }
        });

        $button.on('click', function(event) {

            //show an error message if user attempt to pay more than the invoice due and exit
            <?php if (get_setting("allow_partial_invoice_payment_from_clients") && $invoice_id) { ?>
                if (unformatCurrency($("#payment-amount").val()) > "<?php echo $invoice_total_summary->balance_due; ?>") {
                    appAlert.error("<?php echo app_lang("invoice_over_payment_error_message"); ?>");
                    return false;
                }
            <?php } ?>

            var payment_method_id = $(this).data("id");
            $("#online-payment-form").find("input[name='payment_method_id']").val(payment_method_id);

            $(this).addClass("spinning");

            // init payment process
            // this will return the checkout url
            $("#online-payment-form").trigger("submit");
        });

        $("#payment-amount").change(function() {
            //changed the amount. update the description on the payment form
            var value = $(this).val();
            $("#online-payment-description").val(payInvoiceText + " (" + toCurrency(unformatCurrency(value), currency) + ")");

            //change payment amount field value as inputed/ don't use unformatCurrency we'll do it in controller
            $("#online-payment-amount-field").val(value);

            //check minimum payment amount and show/hide payment button
            $(".online-payment-button").each(function() {
                var minimumPaymentAmount = $(this).data("minimum-payment-amount") * 1;
                if (!minimumPaymentAmount || isNaN(minimumPaymentAmount)) {
                    minimumPaymentAmount = 1;
                }

                if (value < minimumPaymentAmount) {
                    $(this).hide();
                } else {
                    $(this).show();
                }
            });
        });

    });
</script>