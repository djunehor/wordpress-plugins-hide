<link type='text/css' href='<?php echo plugins_url('../style/style-admin.css', __FILE__); ?>' rel='stylesheet' />
<div class='wrap'>
    <h2>ZacWP Plugin Manager - Settings</h2>

    <?php
    if(isset($status) && isset($message)) {
      echo "<div class='$status'><p>$message</p></div>";
}
?>

<form method='post' name='settings' action='<?php echo $url; ?>'>
    <input name="zacwp_po_setting_nonce" value="<?php echo $nonce; ?>" type="hidden">
    <table class='wp-list-table widefat fixed'>

        <tr><th style="width: 100%;">Hidden plugins</th><td>
                <select multiple name='zacwp_po_plugins[]'>
                    <?php
                foreach($all_plugins as $i => $plugin) {
                    echo '<option value="'.$i.'" '.(in_array($i, $plugins) ? 'selected' : '').'>'.$plugin['Name'].'</option>';
                    }
                    ?>
                </select></td></tr>
        <tr><th >Hide <b>ZacWP Plugin Hide</b> Menu
                <?php if(!$hide) { ?><br><div class='error'><small><i>Careful to copy this page link before hiding!</i></small></div> <?php } ?>
            </th><td>
                <select onchange="confirmHide(this.value)" required name='zacwp_po_hide_menu'>

                    <option value="1" <?php echo ($hide) ? 'selected' : ''; ?>>Yes</option>
                    <option value="0" <?php echo !$hide ? 'selected' : ''; ?>>No</option>

                </select></td></tr>
        <tr><th >Extra <b>Plugin</b> Menus</th><td>
                <select style="width: 100%;" multiple name='zacwp_po_plugin_columns[]'>
                    <?php
                foreach($plugin_columns as $plugin) {
                    echo '<option value="'.$plugin.'" '.(in_array($plugin, $selected_columns) ? 'selected' : '').'>'.ucwords( str_replace( "_", " ", $plugin)).'</option>';
                    }
                    ?>
                </select></td></tr>
        <tr style="display: none;" id="confirm_hide"><th >I have copied this <i><b><?php echo $url; ?></b></i> link so I can access this page later</th><td>
                <input type="checkbox" id="hide_confirmed" name="zacwp_po_hide_confirmed"></td></tr>
    </table>
    <div class="tablenav bottom">
        <input type='submit' name='zacwp_po_apply' value='Apply Changes' class='button button-primary' />&nbsp;
    </div>
</form>
</div>

</div>

<script>
    function confirmHide(val) {
        if(val == 1) {
            document.getElementById('confirm_hide').style.display = "block";
            document.getElementById('hide_confirmed').required = true;
            document.getElementById('hide_confirmed').value = 1;
        } else {
            document.getElementById('confirm_hide').style.display = "none";
            document.getElementById('hide_confirmed').required = false;
            document.getElementById('hide_confirmed').value = 0;
        }
    }
</script>