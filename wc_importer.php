<?php

/*
Plugin Name: WC Importer
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: WC - Product Importer.
Version: 1.0
Author: buddyboss
Author URI: http://URI_Of_The_Plugin_Author
License: A "Slug" license name e.g. GPL2
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_Importer{
	var $plugin_url;
	var $plugin_dir;
	var $plugin_prefix;
	var $plugin_version;
	var $domain;
	var $c; //contain the classes

	function __construct() {
		$this->plugin_version = '1.0';
		$this->plugin_dir     = plugin_dir_path( __FILE__ );
		$this->plugin_url     = plugin_dir_url( __FILE__ );
		$this->plugin_prefix  = 'wc_importer';
		$this->domain         = 'wc_importer';
		$this->c              = new stdClass();

		//register all hooks.
		$this->load_classes();
	}
 
	function load_classes() {
		if ( class_exists( 'WP_CLI' ) ) {
			error_log('WP_CLI class exists, attempting to include wc_product_importer.php');
			include $this->plugin_dir . 'includes/wc_product_importer.php';
			if (class_exists('WC_Product_CLI_Importer')) {
				error_log('WC_Product_CLI_Importer class exists, adding command');
				WP_CLI::add_command( 'wc-product', 'WC_Product_CLI_Importer' );
			} else {
				error_log('WC_Product_CLI_Importer class does not exist');
			}
		} else {
			error_log('WP_CLI class does not exist');
		}
	}
}

/*
 * Easy to call function.
 **/
function wc_importer() {
	global $wc_importer;
	return $wc_importer;
}

function wc_importer_init(){
	global $wc_importer;
	//load the main class
	$wc_importer = new WC_Importer();
}
add_action( 'plugins_loaded', 'wc_importer_init' );