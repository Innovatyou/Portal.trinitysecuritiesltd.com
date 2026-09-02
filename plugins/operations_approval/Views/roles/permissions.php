<?php foreach (\operations_approval\Libraries\Operations_permissions::KEYS as $permission) { ?>
    <li>
        <span data-feather="key" class="icon-14 ml-20"></span>
        <h5><?php echo app_lang($permission); ?></h5>
        <div>
            <?php echo form_checkbox($permission, '1', get_array_value($permissions, $permission) === '1', "id='{$permission}' class='form-check-input'"); ?>
            <label for="<?php echo $permission; ?>"><?php echo app_lang('yes'); ?></label>
        </div>
    </li>
<?php } ?>
