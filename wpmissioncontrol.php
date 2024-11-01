<?php
/*
Plugin Name: 	WPMissionControl
Plugin URI: 	https://wpmissioncontrol.com/plugins/wp-mission-control
Description: 	Remote maintenance and security system for Wordpress websites provided by WPMissionControl Center.
Author: 		WPMissionControl Team
License: 		GPLv2 or later
License URI: 	https://www.gnu.org/licenses/gpl-2.0.html
Version: 		1.0.5
Requires PHP: 	5.3.0
Text Domain: 	wpmissioncontrol
*/

if ( !class_exists( 'WPMC_Plugin' ) ) {

	if ( !defined( 'WPMC_PLUGIN_DIR_URL' ) ) {
		define( 'WPMC_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
	}
	if ( !defined( 'WPMC_PLUGIN_DIR_PATH' ) ) {
		define( 'WPMC_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
	}
	if ( !defined( 'WPMC_MAINTENANCE_EMAIL') ) {
		define( 'WPMC_MAINTENANCE_EMAIL', 'maintenance@wpmissioncontrol.com' );
	}
	if ( !defined( 'WPMC_STATIC_ASSETS_URL') ) {
		define( 'WPMC_STATIC_ASSETS_URL', plugin_dir_url( __FILE__ ) . '/assets' );
	}

	require_once dirname( __FILE__ ) . '/includes/WPMC_Plugin.php';
	require_once dirname( __FILE__ ) . '/includes/WPMC_Security.php';

	register_activation_hook(   __FILE__, array( 'WPMC_Plugin', 'activate_plugin' ) );
	register_deactivation_hook( __FILE__, array( 'WPMC_Plugin', 'deactivate_plugin' ) );
	register_uninstall_hook(    __FILE__, array( 'WPMC_Plugin', 'uninstall_plugin' ) );

	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( 'WPMC_Plugin', 'plugin_settings_link' ) );

	add_action( 'plugins_loaded', array( 'WPMC_Plugin', 'init' ) );
}