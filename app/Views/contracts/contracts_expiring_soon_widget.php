<div class="card bg-white">
    <div class="card-header">
        <i data-feather="clock" class="icon-16"></i>&nbsp; <?php echo app_lang('contracts_expiring_soon'); ?>
    </div>

    <div class="table-responsive" id="contracts-expiring-soon-widget-table">
        <table id="contracts-expiring-soon-table" class="display" cellspacing="0" width="100%">
        </table>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        if (!isMobile()) {
            initScrollbar('#contracts-expiring-soon-widget-table', {
                setHeight: 330
            });
        }

        $("#contracts-expiring-soon-table").appTable({
            source: '<?php echo_uri("contracts/list_data/0/1") ?>',
            order: [[5, "desc"]],
            displayLength: 30,
            columns: [
                {title: "<?php echo app_lang('contract') ?>", "class": "w15p"},
                {title: "<?php echo app_lang('title') ?>", "class": "all"},
                {title: "<?php echo app_lang('client') ?>"},
                {visible: false, searchable: false},
                {visible: false, searchable: false},
                {title: "<?php echo app_lang('contract_date') ?>", "iDataSort": 4, "class": "w125"},
                {visible: false, searchable: false},
                {title: "<?php echo app_lang('valid_until') ?>", "iDataSort": 6, "class": "w125"},
                {title: "<?php echo app_lang('amount') ?>", "class": "w80"},
                {title: "<?php echo app_lang('status') ?>", "class": "w80"},
                {visible: false, searchable: false}
            ]
        });
    });
</script>