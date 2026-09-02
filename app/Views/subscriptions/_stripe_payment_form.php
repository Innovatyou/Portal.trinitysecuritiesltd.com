<?php echo form_open(get_uri("pay_invoice/init_online_payment"), array("id" => "stripe-checkout-form", "class" => "float-start", "role" => "form")); ?>
<input type="hidden" name="subscription_id" value="<?php echo $subscription_id; ?>" />
<input type="hidden" name="payment_amount" value="<?php echo to_decimal_format($balance_due); ?>" id="stripe-payment-amount-field" />
<input type="hidden" name="verification_code" value="<?php echo isset($verification_code) ? $verification_code : ""; ?>" id="verification_code" />
<input type="hidden" name="contact_user_id" value="<?php echo isset($contact_user_id) ? $contact_user_id : ""; ?>" id="contact_user_id" />

<input type="hidden" name="currency" value="<?php echo $currency; ?>" />
<input type="hidden" name="balance_due" value="<?php echo $balance_due; ?>" />
<input type="hidden" name="client_id" value="<?php echo $subscription_info->client_id; ?>" />
<input type="hidden" name="payment_method_id" value="<?php echo get_array_value($payment_method, "id"); ?>" />
<input type="hidden" name="description" value="<?php echo app_lang("pay_subscription"); ?>: (<?php echo to_currency($balance_due, $currency . " "); ?>)" id="description" />

<button type="button" id="stripe-payment-button" class="btn btn-primary mr15 spinning-btn"><?php echo app_lang("subscribe"); ?></button>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function() {
        var currency = "<?php echo $currency . ' '; ?>",
            paySubscriptionText = "<?php echo app_lang("pay_subscription"); ?>";
        var $button = $("#stripe-payment-button");

        $("#stripe-checkout-form").appForm({
            isModal: false,
            onSuccess: function(response) {
                if (response.success && response.checkout_url) {
                    window.location.href = response.checkout_url;
                }
            }
        });

        $button.on('click', function(event) {

            $button.addClass("spinning");

            var minimumPaymentAmount = "<?php echo get_array_value($payment_method, 'minimum_payment_amount'); ?>" * 1;
            if (!minimumPaymentAmount || isNaN(minimumPaymentAmount)) {
                minimumPaymentAmount = 1;
            }

            if (unformatCurrency($("#stripe-payment-amount-field").val()) < minimumPaymentAmount) {
                appAlert.error("<?php echo app_lang("invoice_over_payment_error_message"); ?>");
                return false;
            }

            $("#stripe-checkout-form").trigger("submit");
        });

    });
</script>