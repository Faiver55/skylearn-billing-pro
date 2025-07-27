<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/core
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/core
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Plugin {

	/**
	 * The single instance of the class.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SLBP_Plugin    $instance    The single instance of the class.
	 */
	private static $instance = null;

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      SLBP_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Dependency injection container.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $container    Container for registered dependencies.
	 */
	protected $container = array();

	/**
	 * Admin instance.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      SLBP_Admin    $admin    The admin instance.
	 */
	protected $admin;

	/**
	 * Enrollment admin instance.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      SLBP_Enrollment_Admin    $enrollment_admin    The enrollment admin instance.
	 */
	protected $enrollment_admin;

	/**
	 * Main Plugin Instance.
	 *
	 * Ensures only one instance of the plugin is loaded or can be loaded.
	 *
	 * @since  1.0.0
	 * @static
	 * @return SLBP_Plugin - Main instance.
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cloning is forbidden.', 'skylearn-billing-pro' ), '1.0.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Unserializing instances is forbidden.', 'skylearn-billing-pro' ), '1.0.0' );
	}

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	private function __construct() {
		if ( defined( 'SLBP_VERSION' ) ) {
			$this->version = SLBP_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'skylearn-billing-pro';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->init_modules();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - SLBP_Loader. Orchestrates the hooks of the plugin.
	 * - SLBP_i18n. Defines internationalization functionality.
	 * - SLBP_Admin. Defines all hooks for the admin area.
	 * - SLBP_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once SLBP_PLUGIN_PATH . 'includes/core/class-slbp-loader.php';

		$this->loader = new SLBP_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the SLBP_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$this->loader->add_action( 'plugins_loaded', $this, 'load_plugin_textdomain' );
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'skylearn-billing-pro',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		// Only load admin functionality in admin area
		if ( ! is_admin() ) {
			return;
		}

		// Initialize admin class
		$this->admin = new SLBP_Admin( $this->plugin_name, $this->version );

		// Initialize enrollment admin
		$this->enrollment_admin = new SLBP_Enrollment_Admin( $this );

		// Register admin menu
		$this->loader->add_action( 'admin_menu', $this->admin->get_menu(), 'register_admin_menu' );

		// Register settings
		$this->loader->add_action( 'admin_init', $this->admin->get_settings(), 'register_settings' );

		// Enqueue admin assets
		$this->loader->add_action( 'admin_enqueue_scripts', $this->admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $this->admin, 'enqueue_scripts' );

		// Admin notices
		$this->loader->add_action( 'admin_notices', $this->admin, 'display_admin_notices' );

		// AJAX handlers
		$this->loader->add_action( 'wp_ajax_slbp_test_connection', $this->admin, 'handle_ajax_test_connection' );
		$this->loader->add_action( 'wp_ajax_slbp_dismiss_notice', 'SLBP_Admin_Notices', 'handle_dismiss_notice' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		// Public hooks will be added here in future phases
	}

	/**
	 * Initialize plugin modules.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function init_modules() {
		// Initialize payment gateways
		$this->init_payment_gateways();
		
		// Initialize LMS integrations
		$this->init_lms_integrations();
	}

	/**
	 * Initialize payment gateways.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function init_payment_gateways() {
		// Load webhook handler (this will register REST endpoints)
		require_once SLBP_PLUGIN_PATH . 'includes/payment-gateways/lemon-squeezy-webhook.php';
		
		// Register available gateways
		$this->register_payment_gateway( 'lemon_squeezy', 'SLBP_Lemon_Squeezy' );
	}

	/**
	 * Initialize LMS integrations.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function init_lms_integrations() {
		// Register available LMS integrations
		$this->register_lms_integration( 'learndash', 'SLBP_LearnDash' );
	}

	/**
	 * Register a payment gateway.
	 *
	 * @since    1.0.0
	 * @param    string    $gateway_id      Gateway identifier.
	 * @param    string    $gateway_class   Gateway class name.
	 */
	public function register_payment_gateway( $gateway_id, $gateway_class ) {
		$this->container[ 'gateway_' . $gateway_id ] = $gateway_class;
	}

	/**
	 * Register an LMS integration.
	 *
	 * @since    1.0.0
	 * @param    string    $lms_id       LMS identifier.
	 * @param    string    $lms_class    LMS class name.
	 */
	public function register_lms_integration( $lms_id, $lms_class ) {
		$this->container[ 'lms_' . $lms_id ] = $lms_class;
	}

	/**
	 * Get payment gateway instance.
	 *
	 * @since    1.0.0
	 * @param    string    $gateway_id    Gateway identifier.
	 * @return   SLBP_Abstract_Payment_Gateway|null    Gateway instance or null if not found.
	 */
	public function get_payment_gateway( $gateway_id ) {
		$gateway_class = $this->container[ 'gateway_' . $gateway_id ] ?? null;
		
		if ( ! $gateway_class || ! class_exists( $gateway_class ) ) {
			return null;
		}

		// Get gateway configuration from settings
		$config = $this->get_gateway_config( $gateway_id );
		
		return new $gateway_class( $config );
	}

	/**
	 * Get LMS integration instance.
	 *
	 * @since    1.0.0
	 * @param    string    $lms_id    LMS identifier.
	 * @return   SLBP_Abstract_LMS_Integration|null    LMS instance or null if not found.
	 */
	public function get_lms_integration( $lms_id ) {
		$lms_class = $this->container[ 'lms_' . $lms_id ] ?? null;
		
		if ( ! $lms_class || ! class_exists( $lms_class ) ) {
			return null;
		}

		// Get LMS configuration from settings
		$config = $this->get_lms_config( $lms_id );
		
		return new $lms_class( $config );
	}

	/**
	 * Get gateway configuration from settings.
	 *
	 * @since    1.0.0
	 * @param    string    $gateway_id    Gateway identifier.
	 * @return   array                   Gateway configuration.
	 */
	private function get_gateway_config( $gateway_id ) {
		$payment_settings = get_option( 'slbp_payment_settings', array() );
		
		switch ( $gateway_id ) {
			case 'lemon_squeezy':
				return array(
					'api_key'        => $payment_settings['lemon_squeezy_api_key'] ?? '',
					'store_id'       => $payment_settings['lemon_squeezy_store_id'] ?? '',
					'test_mode'      => $payment_settings['lemon_squeezy_test_mode'] ?? false,
					'webhook_secret' => $payment_settings['webhook_secret'] ?? '',
				);
			
			default:
				return array();
		}
	}

	/**
	 * Get LMS configuration from settings.
	 *
	 * @since    1.0.0
	 * @param    string    $lms_id    LMS identifier.
	 * @return   array              LMS configuration.
	 */
	private function get_lms_config( $lms_id ) {
		$lms_settings = get_option( 'slbp_lms_settings', array() );
		
		switch ( $lms_id ) {
			case 'learndash':
				return array(
					'enabled'         => $lms_settings['learndash_enabled'] ?? true,
					'auto_enroll'     => $lms_settings['learndash_auto_enroll'] ?? true,
					'access_duration' => $lms_settings['learndash_access_duration'] ?? 0,
				);
			
			default:
				return array();
		}
	}

	/**
	 * Register a dependency in the container.
	 *
	 * @since    1.0.0
	 * @param    string    $key        The dependency key.
	 * @param    mixed     $dependency The dependency to register.
	 */
	public function register( $key, $dependency ) {
		$this->container[ $key ] = $dependency;
	}

	/**
	 * Resolve a dependency from the container.
	 *
	 * @since    1.0.0
	 * @param    string    $key    The dependency key.
	 * @return   mixed             The resolved dependency or null if not found.
	 */
	public function resolve( $key ) {
		return isset( $this->container[ $key ] ) ? $this->container[ $key ] : null;
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    SLBP_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Get the admin instance.
	 *
	 * @since     1.0.0
	 * @return    SLBP_Admin|null    The admin instance.
	 */
	public function get_admin() {
		return $this->admin;
	}

	/**
	 * Get the enrollment admin instance.
	 *
	 * @since     1.0.0
	 * @return    SLBP_Enrollment_Admin|null    The enrollment admin instance.
	 */
	public function get_enrollment_admin() {
		return $this->enrollment_admin;
	}
}