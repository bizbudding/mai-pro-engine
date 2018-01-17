<?php

/**
 * Plugin Name:     Mai Theme Engine Installer
 * Plugin URI:      https://maitheme.com/
 * Description:     This plugin only exists when older versions of Mai Theme or Mai Pro point to the older engine repository. Once Mai Theme Engine is installed and activated, this plugin can safely be deactivated and deleted.
 *
 * Version:         0.1.0
 *
 * Author:          MaiTheme.com
 * Author URI:      https://maitheme.com
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'PLUGIN_NAME_VERSION', '0.1.0' );

add_action( 'plugins_loaded', 'mai_engine_installer_setup' );
function mai_engine_installer_setup() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/wp-dependency-installer.php'; // v 1.3.2
	require_once plugin_dir_path( __FILE__ ) . 'includes/plugin-update-checker/plugin-update-checker.php'; // v 4.4
	$updater = Puc_v4_Factory::buildUpdateChecker( 'https://github.com/maithemewp/mai-engine-installer/', __FILE__, 'mai-engine-installer' );
}

add_action( 'plugins_loaded', 'mai_engine_installer_run' );
function mai_engine_installer_run() {

}
