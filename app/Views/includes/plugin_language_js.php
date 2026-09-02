<?php

$validation_err = "License error. " . app_lang('something_went_wrong');
if (isset($login_user->is_admin) && $login_user->is_admin) {
    $validation_err = "You don't have any active license for this site. Please check the Settings > Updates page for more details.";
}

?>

<script type="text/javascript">
    AppLanguage = {};
    AppLanguage.locale = "<?php echo app_lang('language_locale'); ?>";
    AppLanguage.localeLong = "<?php echo app_lang('language_locale_long'); ?>";

    AppLanguage.days = <?php echo json_encode(array(app_lang("sunday"), app_lang("monday"), app_lang("tuesday"), app_lang("wednesday"), app_lang("thursday"), app_lang("friday"), app_lang("saturday"))); ?>;
    AppLanguage.daysShort = <?php echo json_encode(array(app_lang("short_sunday"), app_lang("short_monday"), app_lang("short_tuesday"), app_lang("short_wednesday"), app_lang("short_thursday"), app_lang("short_friday"), app_lang("short_saturday"))); ?>;
    AppLanguage.daysMin = <?php echo json_encode(array(app_lang("min_sunday"), app_lang("min_monday"), app_lang("min_tuesday"), app_lang("min_wednesday"), app_lang("min_thursday"), app_lang("min_friday"), app_lang("min_saturday"))); ?>;

    AppLanguage.months = <?php echo json_encode(array(app_lang("january"), app_lang("february"), app_lang("march"), app_lang("april"), app_lang("may"), app_lang("june"), app_lang("july"), app_lang("august"), app_lang("september"), app_lang("october"), app_lang("november"), app_lang("december"))); ?>;
    AppLanguage.monthsShort = <?php echo json_encode(array(app_lang("short_january"), app_lang("short_february"), app_lang("short_march"), app_lang("short_april"), app_lang("short_may"), app_lang("short_june"), app_lang("short_july"), app_lang("short_august"), app_lang("short_september"), app_lang("short_october"), app_lang("short_november"), app_lang("short_december"))); ?>;

    AppLanguage.today = "<?php echo app_lang('today'); ?>";
    AppLanguage.yesterday = "<?php echo app_lang('yesterday'); ?>";
    AppLanguage.tomorrow = "<?php echo app_lang('tomorrow'); ?>";
    AppLanguage.last_7_days = "<?php echo app_lang('last_7_days'); ?>";
    AppLanguage.next_7_days = "<?php echo app_lang('next_7_days'); ?>";
    AppLanguage.last_30_days = "<?php echo app_lang('last_30_days'); ?>";
    AppLanguage.this_month = "<?php echo app_lang('this_month'); ?>";
    AppLanguage.last_month = "<?php echo app_lang('last_month'); ?>";
    AppLanguage.next_month = "<?php echo app_lang('next_month'); ?>";
    AppLanguage.this_year = "<?php echo app_lang('this_year'); ?>";
    AppLanguage.next_year = "<?php echo app_lang('next_year'); ?>";
    AppLanguage.last_year = "<?php echo app_lang('last_year'); ?>";

    AppLanguage.search = "<?php echo app_lang('search'); ?>";
    AppLanguage.noRecordFound = "<?php echo app_lang('no_record_found'); ?>";
    AppLanguage.print = "<?php echo app_lang('print'); ?>";
    AppLanguage.excel = "<?php echo app_lang('excel'); ?>";
    AppLanguage.printButtonTooltip = "<?php echo app_lang('print_button_help_text'); ?>";

    AppLanguage.fileUploadInstruction = "<?php echo app_lang('file_upload_instruction'); ?>";
    AppLanguage.fileNameTooLong = "<?php echo app_lang('file_name_too_long'); ?>";

    AppLanguage.custom = "<?php echo app_lang('custom'); ?>";
    AppLanguage.clear = "<?php echo app_lang('clear'); ?>";

    AppLanguage.total = "<?php echo app_lang('total'); ?>";
    AppLanguage.totalOfAllPages = "<?php echo app_lang('total_of_all_pages'); ?>";

    AppLanguage.all = "<?php echo app_lang('all'); ?>";

    AppLanguage.preview_next_key = "<?php echo app_lang('preview_next_key'); ?>";
    AppLanguage.preview_previous_key = "<?php echo app_lang('preview_previous_key'); ?>";

    AppLanguage.filters = "<?php echo app_lang('filters'); ?>";
    AppLanguage.manageFilters = "<?php echo app_lang('manage_filters'); ?>";
    AppLanguage.newFilter = "<?php echo app_lang('new_filter'); ?>";
    AppLanguage.addNewFilter = "<?php echo app_lang('add_new_filter'); ?>";
    AppLanguage.updateFilter = "<?php echo app_lang('update_filter'); ?>";

    AppLanguage.comment = "<?php echo app_lang('comment'); ?>";

    AppLanguage.undo = "<?php echo app_lang('undo'); ?>";

    AppLanguage.fileSizeTooLong = "<?php echo app_lang('file_size_too_large'); ?>";
    AppLanguage.somethingWentWrong = "<?php echo app_lang('something_went_wrong'); ?>";
    AppLanguage.selectRange = "<?php echo app_lang('select_range'); ?>";


    AppLanguage.monthly = "<?php echo app_lang('monthly'); ?>";
    AppLanguage.yearly = "<?php echo app_lang('yearly'); ?>";
    AppLanguage.custom = "<?php echo app_lang('custom'); ?>";
    AppLanguage.dynamic = "<?php echo app_lang('dynamic'); ?>";

    AppLanguage.batchUpdate = "<?php echo app_lang('batch_update'); ?>";
    AppLanguage.selectAll = "<?php echo app_lang('select_all'); ?>";
    AppLanguage.selectSpecific = "<?php echo app_lang('select_specific'); ?>";
    AppLanguage.clearSelection = "<?php echo app_lang('clear_selection'); ?>";
    AppLanguage.downloadSelectedItems = "<?php echo app_lang('download_selected_items'); ?>";
    AppLanguage.deleteSelectedItems = "<?php echo app_lang('delete_selected_items'); ?>";
    AppLanguage.enterMinimum2characters = "<?php echo app_lang('enter_minimum_2_characters'); ?>";
    AppLanguage.enterPromptHere = "<?php echo app_lang('enter_prompt_here'); ?>";
    AppLanguage.validationErr = "<?php echo $validation_err; ?>";
</script>