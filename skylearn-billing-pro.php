<?php
/**
 * SkyLearn Billing Pro
 *
 * Professional LearnDash billing management with multiple payment gateway support
 *
 * @package           SkyLearnBillingPro
 * @author            Skyian LLC
 * @copyright         2024 Skyian LLC
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       SkyLearn Billing Pro
 * Plugin URI:        https://skyianllc.com/skylearn-billing-pro
 * Description:       Professional LearnDash billing management with multiple payment gateway support including Lemon Squeezy integration.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            Skyian LLC
 * Author URI:        https://skyianllc.com
 * Text Domain:       skylearn-billing-pro
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Network:           false
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access is forbidden.' );
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 */
define( 'SLBP_VERSION', '1.0.0' );

/**
 * Plugin constants
 */
define( 'SLBP_PLUGIN_FILE', __FILE__ );
define( 'SLBP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'SLBP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SLBP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'SLBP_TEXT_DOMAIN', 'skylearn-billing-pro' );

/**
 * Autoloader for plugin classes
 *
 * @param string $class_name The name of the class to load.
 */
function slbp_autoload_classes( $class_name ) {
	// Check if the class belongs to our plugin
	if ( 0 !== strpos( $class_name, 'SLBP_' ) ) {
		return;
	}

	// Convert class name to file name
	$class_file = strtolower( str_replace( '_', '-', $class_name ) );
	$class_file = 'class-' . $class_file . '.php';

	// Define possible paths
	$paths = array(
		SLBP_PLUGIN_PATH . 'includes/core/',
		SLBP_PLUGIN_PATH . 'includes/admin/',
		SLBP_PLUGIN_PATH . 'includes/payment-gateways/',
		SLBP_PLUGIN_PATH . 'includes/lms-integrations/',
		SLBP_PLUGIN_PATH . 'includes/utilities/',
		SLBP_PLUGIN_PATH . 'includes/analytics/',
		SLBP_PLUGIN_PATH . 'includes/notifications/',
		SLBP_PLUGIN_PATH . 'includes/integrations/',
		SLBP_PLUGIN_PATH . 'includes/dashboard/',
		SLBP_PLUGIN_PATH . 'includes/onboarding/',
		SLBP_PLUGIN_PATH . 'includes/reporting/',
		SLBP_PLUGIN_PATH . 'includes/external-integrations/',
		SLBP_PLUGIN_PATH . 'includes/internationalization/',
		SLBP_PLUGIN_PATH . 'includes/training/',
	);

	// Try to load the class file
	foreach ( $paths as $path ) {
		$file_path = $path . $class_file;
		if ( file_exists( $file_path ) ) {
			require_once $file_path;
			break;
		}
	}
}

// Register the autoloader
spl_autoload_register( 'slbp_autoload_classes' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/core/class-slbp-activator.php
 */
function slbp_activate_plugin() {
	require_once SLBP_PLUGIN_PATH . 'includes/core/class-slbp-activator.php';
	SLBP_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/core/class-slbp-deactivator.php
 */
function slbp_deactivate_plugin() {
	require_once SLBP_PLUGIN_PATH . 'includes/core/class-slbp-deactivator.php';
	SLBP_Deactivator::deactivate();
}

// Register activation and deactivation hooks
register_activation_hook( __FILE__, 'slbp_activate_plugin' );
register_deactivation_hook( __FILE__, 'slbp_deactivate_plugin' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once SLBP_PLUGIN_PATH . 'includes/core/class-slbp-plugin.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function slbp_run_plugin() {
	$plugin = SLBP_Plugin::get_instance();
	$plugin->run();
}

// Check if WordPress is loaded before running the plugin
if ( defined( 'ABSPATH' ) ) {
	slbp_run_plugin();
}