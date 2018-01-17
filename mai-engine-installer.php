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

	private $config;

	private $file;

	function __construct() {
		$this->config = array(
			'name'     => 'Mai Theme Engine',
			'host'     => 'github',
			'slug'     => 'mai-theme-engine/mai-theme-engine.php',
			'uri'      => 'maithemewp/mai-theme-engine',
			'branch'   => 'master',
			'optional' => false,
			'token'    => null,
		);
		$this->file = get_stylesheet_directory() . '/includes/dependencies/wp-dependencies.json';
	}

	/**
	 * Main Mai_Engine_Installer Instance.
	 *
	 * Insures that only one instance of Mai_Engine_Installer exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since   1.0.0
	 * @static  var array $instance
	 * @uses    Mai_Engine_Installer::setup_constants() Setup the constants needed.
	 * @uses    Mai_Engine_Installer::includes() Include the required files.
	 * @uses    Mai_Engine_Installer::setup() Activate, deactivate, etc.
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
		define( 'MAI_THEME_ENGINE_INSTALLER_VERSION', '0.1.0' );
	}

	/**
	 * Load the necessary files.
	 * Setup the updater.
	 *
	 * @return void
	 */
	function includes() {
		require_once plugin_dir_path( __FILE__ ) . 'includes/wp-dependency-installer.php'; // v 1.3.2
		require_once plugin_dir_path( __FILE__ ) . 'includes/plugin-update-checker/plugin-update-checker.php'; // v 4.4
	}

	/**
	 * Run the hooks and function.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'plugins_loaded',    array( $this, 'update' ) );
		add_action( 'after_setup_theme', array( $this, 'install' ) );
		add_action( 'after_setup_theme', array( $this, 'write' ) );
		add_action( 'admin_init',        array( $this, 'deactivate' ) );
	}

	/**
	 * Setup the updater.
	 *
	 * @uses    https://github.com/YahnisElsts/plugin-update-checker/
	 *
	 * @return  void
	 */
	function update() {
		$updater = Puc_v4_Factory::buildUpdateChecker( 'https://github.com/maithemewp/mai-engine-installer/', __FILE__, 'mai-engine-installer' );
	}

	/**
	 * Force install of the correct plugin.
	 *
	 * @uses    https://github.com/afragen/wp-dependency-installer/
	 *
	 * @return  void
	 */
	function install() {
		WP_Dependency_Installer::instance()->register( array( $this->config ) );
	}

	/**
	 * Force install of the correct Mai Theme Engine plugin.
	 * Check the existing child theme json file and update as needed.
	 * Deactivate this plugin if everything is in place.
	 *
	 * @return void
	 */
	function write() {

		// Bail if current user cannot manage plugins.
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		// Bail if file doesn't exist.
		if ( ! file_exists( $this->file ) ) {
			return;
		}

		$update_file = false;

		// Load the dependencies file.
		$contents = file_get_contents( $this->file );

		// Decode the JSON data into a PHP array.
		$decoded = json_decode( $contents, true );

		// Loop through the dependencies.
		foreach ( $decoded as $key => $value ) {

			// Skip if not the one we want.
			if ( ! isset( $value['uri'] ) || 'maiprowp/mai-pro-engine' !== $value['uri'] ) {
				continue;
			}

			// Build the new engine location array.
			$decoded[ $key ] = $this->config;

			$update_file = true;

		}

		if ( ! $update_file ) {
			return;
		}

		// Encode the array back into a JSON string.
		$json = json_encode( $decoded );

		// Save the file.
		file_put_contents( $this->file, $json );

	}

	function deactivate() {

		$file_correct = false;

		// Load the dependencies file.
		$contents = file_get_contents( $this->file );

		// Decode the JSON data into a PHP array.
		$decoded = json_decode( $contents, true );

		// Loop through the dependencies.
		foreach ( $decoded as $key => $value ) {

			// If depency is set correctly, deactivate this plugin.
			if ( isset( $value['uri'] ) && 'maithemewp/mai-theme-engine' === $value['uri'] ) {
				$file_correct = true;
			}

		}

		// Create array of plugins to deactivate, with this one being the only one for now.
		$plugins_to_deactivate = array( plugin_basename( __FILE__ ) );

		// If the old engine is active, add it to array of plugins to deactivate.
		if ( is_plugin_active( 'mai-pro-engine/mai-pro-engine.php' ) ) {
			$plugins_to_deactivate[] = 'mai-pro-engine/mai-pro-engine.php';
		}
		// Old engine is not active, new engine is active, and the theme file has been updated.
		elseif ( class_exists( 'Mai_Theme_Engine' ) && $file_correct ) {
			// Deactivate plugins.
			deactivate_plugins( $plugins_to_deactivate );
		}

	}

}

/**
 * The main function for that returns Mai_Engine_Installer
 *
 * @return object|Mai_Engine_Installer The one true Mai_Engine_Installer Instance.
 */
function Mai_Engine_Installer() {
	return Mai_Engine_Installer::instance();
}

// Get Mai_Engine_Installer Running.
Mai_Engine_Installer();
