<?php
/*
Plugin Name: ZacWP Hide Plugin
Description: Allows a website administrator hide plugins that shows in the plugins page.
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


class ZacWP_PO_Plugin_Hide {

	private static $instance;
	public $pluginColumns = [];

	/**Set hooks
	 * ZacWP_PO_Plugin_Order constructor.
	 */
	public function __construct() {
		add_filter( 'pre_current_active_plugins', array( $this, 'zacwp_po_hide_plugins' ) );
		add_action( 'admin_menu', array( $this, 'zacwp_po_add_menu' ) );
	}

	/**Add settings submenu to wordpress settings menu
	 *
	 */
	public function zacwp_po_add_menu() {
		add_submenu_page( 'options-general.php', "ZacWP Plugins Settings", 'ZacWP Plugin Setting', 'administrator', 'zacwp_po_settings', array(
			$this,
			'zacwp_po_settings',
		) );
	}

	/**Ensure only one instance of the class
	 * @return ZacWP_PO_Plugin_Hide
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
			if ( isset( $_POST['apply'] )
			     && isset( $_POST['zacwp_po_plugins'] )
			     && isset( $_POST['zacwp_po_setting_nonce'] )
			     && wp_verify_nonce( sanitize_text_field( $_REQUEST['zacwp_po_setting_nonce'] ), 'zacwp_po_setting' )

			) {


				$fp      = file_put_contents( __DIR__ . '/inc/excluded_plugins.txt', implode( ",", $_POST['zacwp_po_plugins'] ) );
				$status  = "updated";
				$message = "Settings successfully changed";


			}

			$plugins     = file_get_contents( __DIR__ . '/inc/excluded_plugins.txt' );
			$plugins     = explode( ",", $plugins );
			$all_plugins = get_plugins();
			$base_url    = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'];
			$url         = $base_url . $_SERVER["REQUEST_URI"];

			$nonce = wp_create_nonce( 'zacwp_po_setting' );
			include( __DIR__ . '/views/settings.tpl' );
		}
	}

	/**Filter plugins list to only show unhidden plugins
	 *
	 */
	public function zacwp_po_hide_plugins() {

		global $wp_list_table;
		$hidearr   = explode( ",", file_get_contents( __DIR__ . '/inc/excluded_plugins.txt' ) );
		$myplugins = $wp_list_table->items;
		foreach ( $myplugins as $key => $val ) {
			if ( in_array( $key, $hidearr ) ) {
				unset( $wp_list_table->items[ $key ] );
			}
		}

	}
}


//initialize class instance
function ZacWP_Plugin_Order() {
	static $instance;

	// first call to instance() initializes the plugin
	if ( $instance === null || ! ( $instance instanceof ZacWP_PO_Plugin_Hide ) )
		$instance = ZacWP_PO_Plugin_Hide::instance();

	return $instance;
}

$zacwWp_plugin_order = ZacWP_Plugin_Order();