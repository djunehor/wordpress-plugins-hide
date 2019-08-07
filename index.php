<?php
/*
Plugin Name: ZacWP Plugins Extended: Hide, Order & Sort
Description: Allows a website administrator hide plugins that shows in the plugins page, as well as add extra columns, filter and download
Version: 1.0
Author: Zacchaeus Bolaji
Author URI: https://github.com/makinde2013
*/

// Exit if accessed directly
defined( 'ABSPATH' ) or exit;

//prevent function not exist error
if(!function_exists('wp_get_current_user')) {
	include(ABSPATH . "wp-includes/pluggable.php");
}


class ZacWP_PO_Plugin_Order_Hide {

	private static $instance;

	//to hold array of all plugins
	public $plugins = [];

	// to hold an array of plugins activated/deactivated date
	public $pluginsLog = [];

	//to hold transient data of current plugin in loop
	public $temp_data = [];

	//allowed extra columns
	public $plugin_columns = ['id','slug','plugin','new_version','url','package','icons','banners',
		'banners_rtl','PluginURI', 'TextDomain','DomainPath','Network','Title', 'AuthorName',
		'Plugin_Size', 'Last_Modified', 'Plugin_Installed', 'Plugin_File', 'last_activated', 'last_deactivated'];

	/**Set hooks
	 * ZacWP_PO_Plugin_Order constructor.
	 */
	public function __construct() {
		$this->plugins = get_plugins();
		$this->pluginsLog = get_option( 'zacwp_po_plugins_log', array() );
		add_filter( 'pre_current_active_plugins', array( $this, 'zacwp_po_hide_plugins' ) );
		add_action( 'admin_menu', array( $this, 'zacwp_po_add_menu' ) );
		add_filter( 'manage_plugins_columns', 		array( $this, 'zacwp_po_plugins_columns' ) );
		add_action( 'manage_plugins_custom_column', array( $this, 'zacwp_plugins_extra_columns' ), 10, 3 );
		add_action( 'admin_footer', array( $this, 'zacwp_po_append_scripts') );
		if ( !$this->zacwp_po_plugin_activation_date_active() ) {
			add_action( 'activate_plugin', array( $this, 'zacwp_po_log_plugin_status' ) );
			add_action( 'deactivate_plugin', array( $this, 'zacwp_po_log_plugin_status' ) );
		}

	}

	public function zacwp_po_plugin_activation_date_active() {
	    return is_plugin_active( WP_PLUGIN_DIR.'/plugin-activation-date/plugin-activation-date.php' );
    }

	/**
	 *Add dataTables script to admin footer
	 *if current page is plugins list
	 */
	public  function zacwp_po_append_scripts() {

		if(basename($_SERVER['PHP_SELF']) !== 'plugins.php') return;
		?>
        <link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.19/css/jquery.dataTables.css" >
        <script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
        <script src="//cdn.datatables.net/buttons/1.5.6/js/dataTables.buttons.min.js"></script>
        <script src="//cdn.datatables.net/buttons/1.5.6/js/buttons.flash.min.js"></script>
        <script src="//cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
        <script src="//cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
        <script src="//cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
        <script src="//cdn.datatables.net/buttons/1.5.6/js/buttons.html5.min.js"></script>
        <script src="//cdn.datatables.net/buttons/1.5.6/js/buttons.print.min.js"></script>
        <script>
            var id = "plugin_table";
            var table = document.getElementsByClassName('wp-list-table')[0];
            table.setAttribute("id", id);
            jQuery(document).ready(function($){
                if($('.plugin-update-tr').html() != undefined ) {
                    var addHtml = "<div class='notice notice-warning'>";
                    addHtml += $('.plugin-update-tr').html();
                    addHtml += "</div>";

                    var parent = document.getElementsByClassName('subsubsub')[0];

                    parent.insertAdjacentHTML('beforeend', addHtml);
                }


                $('.plugin-update').remove();
                $('.plugin-update-tr').remove();
                $('#'+id).DataTable({
                    paging: false,
                    scrollY: 350,
                    searching: false,
                    dom: 'Bfrtip',
                    buttons: [
                        'copy', 'csv', 'excel', 'pdf', 'print'
                    ]
                });
            } );
        </script>
		<?php
	}

	/**Add selected extra columns to plugins list
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function zacwp_po_plugins_columns( $columns ) {
		global $status;
		$selected_columns     = get_option('zacwp_po_plugin_columns', []);
		foreach ($this->plugin_columns as $column) {
			if(in_array($column, $selected_columns)) {
				$columns[ $column ] = __( ucwords( str_replace( "_", " ", $column ) ), 'zacpo' );
			}
		}

		if ( !$this->zacwp_po_plugin_activation_date_active() ) {
			if ( !in_array('last_activated_date', $columns)
			     && in_array('last_activated_date', $selected_columns)
            ) {
				$columns['last_activated'] = __( 'Last Activated', 'zacwp_po_dates' );
			} else if(in_array('last_deactivated_date', $selected_columns)
			          && !in_array('last_deactivated_date', $columns)
			) {
				$columns['last_deactivated'] = __( 'Last Deactivated', 'zacwp_po_dates' );
			}
		}
		return $columns;
	}

	/**Calculate extra plugin data and display for current row
	 * @param $column_name
	 * @param $plugin_file
	 * @param $plugin_data
	 */
	public function zacwp_plugins_extra_columns( $column_name, $plugin_file, $plugin_data ) {
		$current_plugin = $this->plugins[ $plugin_file ];

		if(file_exists(WP_PLUGIN_DIR.'/'.$plugin_file)
		   && !array_key_exists($plugin_file, $this->temp_data)
		) {
			$path          = pathinfo( WP_PLUGIN_DIR . '/' . $plugin_file );
			$this->temp_data[$plugin_file]    = $this->zacwp_po_get_plugin_date($path['dirname']);
		}

		switch ($column_name) {
			case 'Last_Modified':
				echo date('d M Y g:i a', $this->temp_data[$plugin_file]['last_modified_time'] );
				break;
			case 'Plugin_Size':
				echo $this->temp_data[$plugin_file]['directory_size'];
				break;
			case 'Plugin_File':
				echo $this->temp_data[$plugin_file]['files'];
				break;
			case 'Plugin_Installed':
				echo date('d M Y g:i a', $this->temp_data[$plugin_file]['create_time']);
				break;
			case 'last_activated':
				echo (array_key_exists($plugin_file, $this->pluginsLog)
				      && array_key_exists('last_activated_date', $this->pluginsLog[$plugin_file] )) ? date('d M Y g:i a', $this->pluginsLog[$plugin_file]['last_activated_date']) : '';
				break;
			case 'last_deactivated':
				echo (array_key_exists($plugin_file, $this->pluginsLog)
				      && array_key_exists('last_deactivated_date', $this->pluginsLog[$plugin_file] )) ? date('d M Y g:i a', $this->pluginsLog[$plugin_file]['last_deactivated_date']) : '';
				break;
			default;
				echo array_key_exists($column_name, $current_plugin) ? $current_plugin[$column_name] : '';
				break;
		}

	}

	/**Get plugin size, last modified, created, and number of files
	 * @param $path
	 *
	 * @return array
	 */
	public function zacwp_po_get_plugin_date($path){
		$bytestotal = 0;

		$last_modified_time = 0;
		$dirmtime = filemtime ($path);
		$dirctime = filectime ($path);
		$files = 0;

		$di = new RecursiveDirectoryIterator($path);
		foreach (new RecursiveIteratorIterator($di) as $filename => $object) {
			if(strlen($object->getFilename()) < 5) continue;
			if(strlen($object->getFilename()) < 5) continue;
			$file = $object->getPath().'/'.$object->getFilename();
			$files++;
			$filemtime = filemtime ($file);
			$bytestotal += $object->getSize();
			$last_modified_time = max($filemtime, $dirmtime, $last_modified_time);
		}

		return [
			'last_modified_time' => $last_modified_time,
			'create_time' => $dirctime,
			'directory_size' => $this->zacwp_po_format_file_size($bytestotal),
			'files' => $files,
		];
	}

	/**Convert byte to human readable format i.e KB, MB, GB etc
	 * @param $bytes
	 *
	 * @return string
	 */
	public function zacwp_po_format_file_size($bytes)
	{
		if ($bytes >= 1073741824)
		{
			$bytes = number_format($bytes / 1073741824, 2) . ' GB';
		}
        elseif ($bytes >= 1048576)
		{
			$bytes = number_format($bytes / 1048576, 2) . ' MB';
		}
        elseif ($bytes >= 1024)
		{
			$bytes = number_format($bytes / 1024, 2) . ' KB';
		}
        elseif ($bytes > 1)
		{
			$bytes = $bytes . ' bytes';
		}
        elseif ($bytes == 1)
		{
			$bytes = $bytes . ' byte';
		}
		else
		{
			$bytes = '0 bytes';
		}

		return $bytes;
	}

	/**Add settings submenu to wordpress settings menu
	 *if user hasn't disabled
	 */
	public function zacwp_po_add_menu() {
		$hide_menu = get_option('zacwp_po_hide_menu', false);

		//If user hasn't selected hide menu, attach menu
		add_submenu_page( $hide_menu ? null : 'options-general.php', "ZacWP Plugins Settings", 'ZacWP Plugin Setting', 'administrator', 'zacwp_po_settings', array(
			$this,
			'zacwp_po_settings',
		) );

	}

	/**Ensure only one instance of the class
	 * @return ZacWP_PO_Plugin_Order_Hide
	 */
	public static function instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**Handle setting of hidden plugins
	 * Accessible to only website administrator
	 *
	 */
	public function zacwp_po_settings() {

		if ( current_user_can( 'administrator' ) ) {

			// update ini file
			if ( isset( $_POST['zacwp_po_apply'] )
			     && isset( $_POST['zacwp_po_setting_nonce'] )
			     && wp_verify_nonce( sanitize_text_field( $_REQUEST['zacwp_po_setting_nonce'] ), 'zacwp_po_setting' )

			) {
				$zacwp_po_plugins = isset( $_POST['zacwp_po_plugins'] ) ? (array) $_POST['zacwp_po_plugins'] : array();
				$zacwp_po_plugin_columns = isset( $_POST['zacwp_po_plugin_columns'] ) ? (array) $_POST['zacwp_po_plugin_columns'] : array();

				// Any of the WordPress data sanitization functions can be used here
				$excluded_plugins = array_map('sanitize_text_field', $zacwp_po_plugins);
				$extra_columns = array_map('sanitize_text_field', $zacwp_po_plugin_columns);

				$hide_menu = sanitize_text_field(isset($_POST['zacwp_po_hide_menu']) ? $_POST['zacwp_po_hide_menu'] : false);
				update_option('zacwp_po_excluded_plugins', $excluded_plugins, false);
				update_option('zacwp_po_hide_menu', $hide_menu, false);
				update_option('zacwp_po_plugin_columns', $extra_columns, false);

				$status  = "updated";
				$message = "Settings successfully changed. Some changes will reflect after next page load.";


			}

			$plugins     = get_option('zacwp_po_excluded_plugins', []);
			$hide     = get_option('zacwp_po_hide_menu', false);
			$selected_columns     = get_option('zacwp_po_plugin_columns', []);
			$all_plugins = get_plugins();
			$base_url    = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'];
			$url         = $base_url . $_SERVER["REQUEST_URI"];
			$plugin_columns = $this->plugin_columns;

			$nonce = wp_create_nonce( 'zacwp_po_setting' );
			include( __DIR__ . '/views/settings.tpl' );
		}
	}

	/**Filter plugins list to only show unhidden plugins
	 *
	 */
	public function zacwp_po_hide_plugins() {

		global $wp_list_table;
		$hidearr   = get_option('zacwp_po_excluded_plugins', []);
		$myplugins = $wp_list_table->items;

		foreach ( $myplugins as $key => $val ) {
			if ( in_array( $key, $hidearr ) ) {
				unset( $wp_list_table->items[ $key ] );
			}
		}

	}

	/**This only works if plugin-activation-date plugin is inactive
	 * It saves the deactivation/activation date of plugin on action
	 * @param $plugin
	 */
	public function zacwp_po_log_plugin_status( $plugin ) {
		$this->pluginsLog[ $plugin ] = array(
			'status' 	=> current_filter() == 'activate_plugin' ? 'activated' : 'deactivated',
			current_filter() == 'activate_plugin' ? 'last_activated_date' : 'last_deactivated_date' => current_time( 'timestamp' )
		);
		update_option( 'zacwp_po_plugins_log', $this->pluginsLog );
	}

}


/**Ensure only one instance of class per time
 * @return ZacWP_PO_Plugin_Order_Hide
 */
function ZacWP_Plugin_Order_Hide() {
	static $instance;

	// first call to instance() initializes the plugin
	if ( $instance === null || ! ( $instance instanceof ZacWP_PO_Plugin_Order_Hide ) )
		$instance = ZacWP_PO_Plugin_Order_Hide::instance();

	return $instance;
}

function zacwp_po_run_at_uninstall() {
	delete_option('zacwp_po_hide_menu');
	delete_option('zacwp_po_excluded_plugins');
	delete_option('zacwp_po_plugin_columns');
	delete_option('zacwp_po_plugins_log');
}

$zacWp_plugin_order = ZacWP_Plugin_Order_Hide();
register_uninstall_hook( __FILE__, 'zacwp_po_run_at_uninstall' );