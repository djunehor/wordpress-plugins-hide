<link type='text/css' href='<?php echo __DIR__.'/inc/style-admin.css'; ?>' rel='stylesheet' />
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

        <tr><th class='simple-table-manager'>Hidden plugins</th><td>
                <select multiple name='zacwp_po_plugins[]'>
                    <?php
                foreach($all_plugins as $i => $plugin) {
                    echo '<option value="'.$i.'" '.(in_array($i, $plugins) ? 'selected' : '').'>'.$plugin['Name'].'</option>';
                    }
                    ?>
                </select></td></tr>
    </table>
    <div class="tablenav bottom">
        <input type='submit' name='apply' value='Apply Changes' class='button button-primary' />&nbsp;
    </div>
</form>
</div>

</div>