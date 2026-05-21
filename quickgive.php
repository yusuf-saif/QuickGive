<?php
/**
 * Plugin Name:       QuickGive
 * Plugin URI:        https://wordpress.org/plugins/quickgive/
 * Description:       Collect one-time donations via a Paystack popup modal. Drop the [paystack_donation_popup] shortcode anywhere.
 * Version:           1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            saif2002
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       quickgive
 *
 * @package QuickGive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'QUICKGIVE_VERSION', '1.0' );
define( 'QUICKGIVE_FILE', __FILE__ );
define( 'QUICKGIVE_DIR', plugin_dir_path( __FILE__ ) );
define( 'QUICKGIVE_URL', plugin_dir_url( __FILE__ ) );
define( 'QUICKGIVE_SLUG', 'quickgive' );

/**
 * Main plugin bootstrap class.
 */
final class QuickGive {

	/**
	 * Singleton instance.
	 *
	 * @var QuickGive|null
	 */
	private static $instance = null;

	/**
	 * Get or create the singleton instance.
	 *
	 * @return QuickGive
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor — load all dependencies and hook in.
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Include all class files.
	 */
	private function load_dependencies() {
		require_once QUICKGIVE_DIR . 'includes/class-quickgive-logger.php';
		require_once QUICKGIVE_DIR . 'includes/class-quickgive-email.php';
		require_once QUICKGIVE_DIR . 'includes/class-quickgive-admin.php';
		require_once QUICKGIVE_DIR . 'includes/class-quickgive-ajax.php';
		require_once QUICKGIVE_DIR . 'includes/class-quickgive-shortcode.php';
	}

	/**
	 * Register WordPress hooks.
	 */
	private function init_hooks() {
		register_activation_hook( QUICKGIVE_FILE, array( 'QuickGive_Logger', 'create_table' ) );

		// Non-destructive DB upgrade for existing installs.
		QuickGive_Logger::maybe_upgrade_table();

		new QuickGive_Admin();
		new QuickGive_Ajax();
		new QuickGive_Shortcode();
	}
}

// Boot the plugin.
add_action( 'plugins_loaded', array( 'QuickGive', 'instance' ) );
