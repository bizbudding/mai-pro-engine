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

final class Mai_Engine_Installer {

	/**
	 * @var Mai_Engine_Installer The one true Mai_Engine_Installer
	 * @since 1.0.0
	 */
	private static $instance;

	private $engine_running;
	private $file;
	private $config;

	function __construct() {
		$this->engine_running = false;
		$this->file           = get_stylesheet_directory() . '/includes/dependencies/wp-dependencies.json';
		$this->config         = array(
			'name'     => 'Mai Theme Engine',
			'host'     => 'github',
			'slug'     => 'mai-theme-engine/mai-theme-engine.php',
			'uri'      => 'maithemewp/mai-theme-engine',
			'branch'   => 'master',
			'optional' => false,
			'token'    => null,
		);
	}

	/**
	 * Main Mai_Engine_Installer Instance.
	 *
	 * Insures that only one instance of Mai_Engine_Installer exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since   1.0.0
	 * @static  var array $instance
	 * @see     Mai_Engine_Installer()
	 * @return  object | Mai_Engine_Installer The one true Mai_Engine_Installer
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup
			self::$instance = new Mai_Engine_Installer;
			// Methods
			self::$instance->setup_constants();
			self::$instance->includes();
			self::$instance->hooks();
		}
		return self::$instance;
	}

	/**
	 * Setup plugin constants.
	 *
	 * @access  private
	 * @return  void
	 */
	private function setup_constants() {
		// A full version ahead of Mai Theme Engine so it always shows as an update.
		define( 'MAI_THEME_ENGINE_INSTALLER_VERSION', '0.1.0' );
	}

	/**
	 * Load the necessary files.
	 * Setup the updater.
	 *
	 * @uses    https://github.com/YahnisElsts/plugin-update-checker/
	 * @uses    https://github.com/afragen/wp-dependency-installer/
	 *
	 * @return  void
	 */
	function includes() {
		require_once plugin_dir_path( __FILE__ ) . 'includes/wp-dependency-installer.php'; // v 1.3.2
		require_once plugin_dir_path( __FILE__ ) . 'includes/plugin-update-checker/plugin-update-checker.php'; // v 4.4
		$updater = Puc_v4_Factory::buildUpdateChecker( 'https://github.com/maiprowp/mai-pro-engine/', __FILE__, 'mai-pro-engine' );
		WP_Dependency_Installer::instance()->register( array( $this->config ) );
		$this->engine_running = true;
	}

	/**
	 * Run the hooks and function.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'plugins_loaded', array( $this, 'write' ) );
		// add_action( 'admin_init',    array( $this, 'deactivate' ) );
		add_action( 'admin_notices',  array( $this, 'admin_notices' ) );
	}

	/**
	 * Force install of the correct Mai Theme Engine plugin.
	 * Check the existing child theme json file and update as needed.
	 *
	 * @return void
	 */
	function write() {

		// Bail if file doesn't exist.
		if ( ! file_exists( $this->file ) ) {
			return;
		}

		$update_file = false;

		// Load the dependencies file.
		$contents = file_get_contents( $this->file );

		// Decode the JSON data into a PHP array.
		$decoded = json_decode( $contents, true );

		if ( empty( $decoded ) ) {
			$update_file = true;
			// Build the new engine location array.
			$decoded[] = $this->config;
		} else {
			// Loop through the dependencies.
			foreach ( (array) $decoded as $key => $value ) {

				// Skip if not the ones we want.
				if ( ! isset( $value['uri'] ) || ! in_array( $value['uri'], array( 'maiprowp/mai-pro-engine', 'bizbudding/mai-pro-engine' ) ) ) {
					continue;
				}

				$update_file = true;

				// Build the new engine location array.
				$decoded[ $key ] = $this->config;
			}
		}

		if ( ! $update_file ) {
			return;
		}

		// Encode the array back into a JSON string.
		$json = json_encode( $decoded, JSON_UNESCAPED_SLASHES );

		// Save the file.
		file_put_contents( $this->file, $json );

	}

	/**
	 * Check that the JSON file updated accordingly,
	 * and deactivate the plugin.
	 */
	function deactivate() {

		// Bail if file doesn't exist.
		if ( ! file_exists( $this->file ) ) {
			return;
		}

		$file_correct = false;

		// Load the dependencies file.
		$contents = file_get_contents( $this->file );

		// Decode the JSON data into a PHP array.
		$decoded = json_decode( $contents, true );

		// Loop through the dependencies.
		foreach ( (array) $decoded as $key => $value ) {
			// If depency is set correctly, deactivate this plugin.
			if ( isset( $value['uri'] ) && ( 'maithemewp/mai-theme-engine' == $value['uri'] ) ) {
				$file_correct = true;
			}
		}

		// Old engine is not active, new engine is active, and the theme file has been updated.
		if ( $this->engine_running && $file_correct ) {
			// Deactivate plugins. Best on 'admin_init'.
			deactivate_plugins( plugin_basename( __FILE__ ) );
		}

	}

	/**
	 * Show an admin notice with a link to refresh the page,
	 * this should trigger the engine install, if it didn't trigger the first time
	 * during installation of this plugin.
	 */
	function admin_notices() {
		/**
		 * Check if this plugin is active,
		 * cause it was deactivating before showing notice and would show it on multiple refreshes.
		 */
		if ( ! is_plugin_active( plugin_basename( __FILE__ ) ) ) {
			return;
		}
		$notice = sprintf( '<strong>' . __( 'Please %s to complete the Mai Theme Engine installation. If Mai Theme Engine is activated, please deactivate and delete Mai Theme Engine Installer.', 'mai-pro-engine' ) . '</strong>', '<a href="' . get_permalink() . '">click here</a>' );
		printf( '<div class="notice notice-error is-dismissible"><p>%s</p></div>', $notice );
		// Remove "Plugin activated" notice.
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}

}

/**
 * The main function for that returns Mai_Engine_Installer
 *
 * @return object|Mai_Engine_Installer The one true Mai_Engine_Installer Instance.
 */
function Mai_Engine_Installer() {
	if ( ! is_admin() ) {
		return;
	}
	return Mai_Engine_Installer::instance();
}

// Get Mai_Engine_Installer Running.
Mai_Engine_Installer();
