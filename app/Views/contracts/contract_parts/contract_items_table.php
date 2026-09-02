<?php
$color = get_setting("contract_color");
if (!$color) {
    $color = get_setting("invoice_color") ? get_setting("invoice_color") : "#2AA384";
}

$item_column_width = get_setting("show_item_image_in_contract_item_list") ? "35%" : "50%";
$colspan = get_setting("show_item_image_in_contract_item_list") ? 4 : 3;

$discount_row = '<tr>
                        <td colspan="<?php echo $colspan; ?>" style="text-align: right;">' . app_lang("discount") . '</td>
                        <td style="text-align: right; width: 20%; border: 1px solid #fff; background-color: #f4f4f4;">' . to_currency($contract_total_summary->discount_total, $contract_total_summary->currency_symbol) . '</td>
                    </tr>';

$total_after_discount_row = '<tr>
                                    <td colspan="<?php echo $colspan; ?>" style="text-align: right;">' . app_lang("total_after_discount") . '</td>
                                    <td style="text-align: right; width: 20%; border: 1px solid #fff; background-color: #f4f4f4;">' . to_currency($contract_total_summary->contract_subtotal - $contract_total_summary->discount_total, $contract_total_summary->currency_symbol) . '</td>
                                </tr>';
?>

<table class="table-responsive" style="width: 100%;">            
    <tr style="font-weight: bold; background-color: <?php echo $color; ?>; color: #fff;  ">
        <?php if(get_setting("show_item_image_in_contract_item_list")) { ?>
        <th style="width: 15%; border-right: 1px solid #eee;"> <?php echo app_lang("image"); ?> </th>
        <?php } ?>
        <th style="width: <?php echo $item_column_width; ?>; border-right: 1px solid #eee;"> <?php echo app_lang("item"); ?> </th>
        <th style="text-align: center;  width: 13%; border-right: 1px solid #eee;"> <?php echo app_lang("quantity"); ?></th>
        <th style="text-align: right;  width: 17%; border-right: 1px solid #eee;"> <?php echo app_lang("rate"); ?></th>
        <th style="text-align: right;  width: 20%; "> <?php echo app_lang("total"); ?></th>
    </tr>
    <?php
    foreach ($contract_items as $item) {
        ?>
        <tr style="background-color: #f4f4f4; ">
            <?php if(get_setting("show_item_image_in_contract_item_list")) { ?>
            <td style="width: 15%; border: 1px solid #fff; padding: 10px; hyphens: auto;">
                <img src="<?php echo get_store_item_image($item->files); ?>" style="max-width: 100%;" />
            </td>
            <?php } ?>
            <td style="width: <?php echo $item_column_width; ?>; border: 1px solid #fff; padding: 10px; hyphens: auto;"><?php echo $item->title; ?>
                <br />
                <span style="color: #888; font-size: 90%; line-height: 16px;"><?php echo custom_nl2br($item->description ? process_images_from_content($item->description) : ""); ?></span>
            </td>
            <td style="text-align: center; width: 13%; border: 1px solid #fff;"> <?php echo $item->quantity . " " . $item->unit_type; ?></td>
            <td style="text-align: right; width: 17%; border: 1px solid #fff;"> <?php echo to_currency($item->rate, $item->currency_symbol); ?></td>
            <td style="text-align: right; width: 20%; border: 1px solid #fff;"> <?php echo to_currency($item->total, $item->currency_symbol); ?></td>
        </tr>
    <?php } ?>
    <tr>
        <td colspan="<?php echo $colspan; ?>" style="text-align: right;"><?php echo app_lang("sub_total"); ?></td>
        <td style="text-align: right; width: 20%; border: 1px solid #fff; background-color: #f4f4f4;">
            <?php echo to_currency($contract_total_summary->contract_subtotal, $contract_total_summary->currency_symbol); ?>
        </td>
    </tr>
    <?php
    if ($contract_total_summary->discount_total && $contract_total_summary->discount_type == "before_tax") {
        echo $discount_row . $total_after_discount_row;
    }
    ?>
    <?php if ($contract_total_summary->tax) { ?>
        <tr>
            <td colspan="<?php echo $colspan; ?>" style="text-align: right;"><?php echo $contract_total_summary->tax_name; ?></td>
            <td style="text-align: right; width: 20%; border: 1px solid #fff; background-color: #f4f4f4;">
                <?php echo to_currency($contract_total_summary->tax, $contract_total_summary->currency_symbol); ?>
            </td>
        </tr>
    <?php } ?>
    <?php if ($contract_total_summary->tax2) { ?>
        <tr>
            <td colspan="<?php echo $colspan; ?>" style="text-align: right;"><?php echo $contract_total_summary->tax_name2; ?></td>
            <td style="text-align: right; width: 20%; border: 1px solid #fff; background-color: #f4f4f4;">
                <?php echo to_currency($contract_total_summary->tax2, $contract_total_summary->currency_symbol); ?>
            </td>
        </tr>
    <?php } ?>
    <?php
    if ($contract_total_summary->discount_total && $contract_total_summary->discount_type == "after_tax") {
        echo $discount_row;
    }
    ?> 
    <tr>
        <td colspan="<?php echo $colspan; ?>" style="text-align: right;"><?php echo app_lang("total"); ?></td>
        <td style="text-align: right; width: 20%; background-color: <?php echo $color; ?>; color: #fff;">
            <?php echo to_currency($contract_total_summary->contract_total, $contract_total_summary->currency_symbol); ?>
        </td>
    </tr>
</table>