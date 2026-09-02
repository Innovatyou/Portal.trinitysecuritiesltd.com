<div class="table-responsive">
    <table id="income-vs-expenses-summary-table" class="display" cellspacing="0" width="100%">
    </table>
</div>

<script type="text/javascript">
    var projectsDropdown = <?php echo $projects_dropdown; ?>;
    var currency_dropdown = <?php echo $currencies_dropdown; ?>;

    var filtrDropdown = [];
    if(projectsDropdown && projectsDropdown.length > 1) {
        filtrDropdown.push({name: "project_id", class: "w200", options: projectsDropdown});
    }

    if (currency_dropdown && currency_dropdown.length > 1) {
        filtrDropdown.push({name: "currency", class: "w150", options: currency_dropdown});
    }

    $("#income-vs-expenses-summary-table").appTable({
        source: '<?php echo_uri("expenses/income_vs_expenses_summary_list_data"); ?>',
        order: [
            [0, "desc"]
        ],
        dateRangeType: "yearly",
        filterDropdown: filtrDropdown,
        columns: [
            {visible: false, searchable: false}, //sorting purpose only
            {title: '<?php echo app_lang("month") ?>', "class": "w30p all", "iDataSort": 0},
            {title: '<?php echo app_lang("income") ?>', "class": "w20p text-right all"},
            {title: '<?php echo app_lang("expenses") ?>', "class": "w20p text-right all"},
            {title: '<?php echo app_lang("profit") ?>', "class": "w20p text-right all"}
        ],
        printColumns: [1, 2, 3, 4],
        xlsColumns: [1, 2, 3, 4],
        summation: [{
            column: 2,
            dataType: 'currency'
        }, {
            column: 3,
            dataType: 'currency'
        }, {
            column: 4,
            dataType: 'currency'
        }]
    });
</script>