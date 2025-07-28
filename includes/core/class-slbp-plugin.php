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
	 * Internationalization manager instance.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      SLBP_I18n    $i18n    The internationalization manager instance.
	 */
	protected $i18n;

	/**
	 * Language switcher instance.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      SLBP_Language_Switcher    $language_switcher    The language switcher instance.
	 */
	protected $language_switcher;

	/**
	 * Tax calculator instance.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      SLBP_Tax_Calculator    $tax_calculator    The tax calculator instance.
	 */
	protected $tax_calculator;

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
		// Initialize internationalization manager
		$this->i18n = new SLBP_I18n();

		// Initialize language switcher
		$this->language_switcher = new SLBP_Language_Switcher( $this->i18n );

		// Initialize tax calculator
		$this->tax_calculator = new SLBP_Tax_Calculator();

		// Initialize shortcodes
		$this->container['i18n_shortcodes'] = new SLBP_I18n_Shortcodes( $this->language_switcher );

		// Initialize language manager admin (only in admin)
		if ( is_admin() ) {
			$this->container['language_manager_admin'] = new SLBP_Language_Manager_Admin( $this->i18n );
		}

		// Initialize email template manager
		$this->container['email_template_manager'] = new SLBP_Email_Template_Manager( $this->i18n );

		// Store in container for dependency injection
		$this->container['i18n'] = $this->i18n;
		$this->container['language_switcher'] = $this->language_switcher;
		$this->container['tax_calculator'] = $this->tax_calculator;

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
		// Initialize mobile/PWA support
		$this->init_mobile_support();
		
		// Enqueue public assets
		$this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_public_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_public_scripts' );
		
		// Add PWA meta tags and manifest
		$this->loader->add_action( 'wp_head', $this, 'add_pwa_meta_tags' );
		$this->loader->add_action( 'wp_footer', $this, 'add_pwa_scripts' );
		
		// Add mobile viewport meta tag
		$this->loader->add_action( 'wp_head', $this, 'add_mobile_viewport_meta' );
		
		// Register manifest endpoint
		$this->loader->add_action( 'init', $this, 'register_manifest_endpoint' );
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

		// Initialize notification system
		$this->init_notification_system();

		// Initialize 3rd-party integrations
		$this->init_integrations();

		// Initialize user dashboard
		$this->init_user_dashboard();

		// Initialize setup wizard
		$this->init_setup_wizard();

		// Initialize REST API
		$this->init_rest_api();

		// Initialize Phase 8 features
		$this->init_compliance_features();
		$this->init_advanced_reporting();
		$this->init_external_analytics();
		$this->init_security_features();

		// Initialize Phase 11 features (Scalability, Performance, and Reliability)
		$this->init_performance_optimization();
		$this->init_scalability_features();
		$this->init_monitoring_and_alerting();
		$this->init_backup_and_recovery();

		// Initialize Phase 12 features (Security, Privacy, and Compliance)
		$this->init_enhanced_security();
		$this->init_privacy_management();
		$this->init_pci_compliance();
		$this->init_security_dashboard();
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
	 * Initialize notification system.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function init_notification_system() {
		// Initialize notification manager
		$this->container['notification_manager'] = new SLBP_Notification_Manager();

		// Initialize admin notifications if in admin area
		if ( is_admin() ) {
			$this->container['admin_notifications'] = new SLBP_Admin_Notifications( 
				$this->container['notification_manager'] 
			);
		}
	}

	/**
	 * Initialize 3rd-party integrations.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function init_integrations() {
		// Initialize integrations manager
		$this->container['integrations_manager'] = new SLBP_Integrations_Manager();
	}

	/**
	 * Initialize user dashboard.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function init_user_dashboard() {
		// Initialize user dashboard
		$this->container['user_dashboard'] = new SLBP_User_Dashboard();
	}

	/**
	 * Initialize setup wizard.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function init_setup_wizard() {
		// Initialize setup wizard
		$this->container['setup_wizard'] = new SLBP_Setup_Wizard();
	}

	/**
	 * Initialize REST API.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function init_rest_api() {
		// Initialize REST API
		$this->container['rest_api'] = new SLBP_REST_API();
		
		// Initialize API key manager
		$this->container['api_key_manager'] = new SLBP_API_Key_Manager();
		
		// Initialize webhook manager
		$this->container['webhook_manager'] = new SLBP_Webhook_Manager();
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

	/**
	 * Get an instance from the dependency injection container.
	 *
	 * @since     1.0.0
	 * @param     string    $key    The container key.
	 * @return    mixed     The instance from container or null if not found.
	 */
	public function get_from_container( $key ) {
		return isset( $this->container[ $key ] ) ? $this->container[ $key ] : null;
	}

	/**
	 * Get the REST API instance.
	 *
	 * @since     1.0.0
	 * @return    SLBP_REST_API|null    The REST API instance.
	 */
	public function get_rest_api() {
		return $this->resolve( 'rest_api' );
	}

	/**
	 * Get the API key manager instance.
	 *
	 * @since     1.0.0
	 * @return    SLBP_API_Key_Manager|null    The API key manager instance.
	 */
	public function get_api_key_manager() {
		return $this->resolve( 'api_key_manager' );
	}

	/**
	 * Get the webhook manager instance.
	 *
	 * @since     1.0.0
	 * @return    SLBP_Webhook_Manager|null    The webhook manager instance.
	 */
	public function get_webhook_manager() {
		return $this->resolve( 'webhook_manager' );
	}

	/**
	 * Initialize compliance features (Phase 8).
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function init_compliance_features() {
		// Initialize audit logger
		$this->container['audit_logger'] = new SLBP_Audit_Logger();

		// Initialize compliance manager
		$this->container['compliance_manager'] = new SLBP_Compliance_Manager();
	}

	/**
	 * Initialize advanced reporting features (Phase 8).
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function init_advanced_reporting() {
		// Initialize advanced reports
		$this->container['advanced_reports'] = new SLBP_Advanced_Reports();
	}

	/**
	 * Initialize external analytics integrations (Phase 8).
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function init_external_analytics() {
		// Initialize external analytics
		$this->container['external_analytics'] = new SLBP_External_Analytics();
	}

	/**
	 * Initialize security features (Phase 8).
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function init_security_features() {
		// Initialize security manager
		$this->container['security_manager'] = new SLBP_Security_Manager();
	}

	/**
	 * Initialize performance optimization features (Phase 11).
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function init_performance_optimization() {
		// Initialize performance optimizer
		$this->container['performance_optimizer'] = new SLBP_Performance_Optimizer();

		// Initialize background processor
		$this->container['background_processor'] = new SLBP_Background_Processor();

		// Initialize rate limiter
		$this->container['rate_limiter'] = new SLBP_Rate_Limiter();
	}

	/**
	 * Initialize scalability features (Phase 11).
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function init_scalability_features() {
		// Initialize scalability manager
		$this->container['scalability_manager'] = new SLBP_Scalability_Manager();
	}

	/**
	 * Initialize monitoring and alerting (Phase 11).
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function init_monitoring_and_alerting() {
		// Initialize monitoring manager
		$this->container['monitoring_manager'] = new SLBP_Monitoring_Manager();
	}

	/**
	 * Initialize backup and recovery (Phase 11).
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function init_backup_and_recovery() {
		// Initialize backup manager
		$this->container['backup_manager'] = new SLBP_Backup_Manager();
	}

	/**
	 * Get the audit logger instance.
	 *
	 * @since     1.0.0
	 * @return    SLBP_Audit_Logger|null    The audit logger instance.
	 */
	public function get_audit_logger() {
		return $this->resolve( 'audit_logger' );
	}

	/**
	 * Get the compliance manager instance.
	 *
	 * @since     1.0.0
	 * @return    SLBP_Compliance_Manager|null    The compliance manager instance.
	 */
	public function get_compliance_manager() {
		return $this->resolve( 'compliance_manager' );
	}

	/**
	 * Get the advanced reports instance.
	 *
	 * @since     1.0.0
	 * @return    SLBP_Advanced_Reports|null    The advanced reports instance.
	 */
	public function get_advanced_reports() {
		return $this->resolve( 'advanced_reports' );
	}

	/**
	 * Get the external analytics instance.
	 *
	 * @since     1.0.0
	 * @return    SLBP_External_Analytics|null    The external analytics instance.
	 */
	public function get_external_analytics() {
		return $this->resolve( 'external_analytics' );
	}

	/**
	 * Get the security manager instance.
	 *
	 * @since     1.0.0
	 * @return    SLBP_Security_Manager|null    The security manager instance.
	 */
	public function get_security_manager() {
		return $this->resolve( 'security_manager' );
	}

	/**
	 * Get the performance optimizer instance.
	 *
	 * @since     1.0.0
	 * @return    SLBP_Performance_Optimizer|null    The performance optimizer instance.
	 */
	public function get_performance_optimizer() {
		return $this->resolve( 'performance_optimizer' );
	}

	/**
	 * Get the background processor instance.
	 *
	 * @since     1.0.0
	 * @return    SLBP_Background_Processor|null    The background processor instance.
	 */
	public function get_background_processor() {
		return $this->resolve( 'background_processor' );
	}

	/**
	 * Get the rate limiter instance.
	 *
	 * @since     1.0.0
	 * @return    SLBP_Rate_Limiter|null    The rate limiter instance.
	 */
	public function get_rate_limiter() {
		return $this->resolve( 'rate_limiter' );
	}

	/**
	 * Get the scalability manager instance.
	 *
	 * @since     1.0.0
	 * @return    SLBP_Scalability_Manager|null    The scalability manager instance.
	 */
	public function get_scalability_manager() {
		return $this->resolve( 'scalability_manager' );
	}

	/**
	 * Get the monitoring manager instance.
	 *
	 * @since     1.0.0
	 * @return    SLBP_Monitoring_Manager|null    The monitoring manager instance.
	 */
	public function get_monitoring_manager() {
		return $this->resolve( 'monitoring_manager' );
	}

	/**
	 * Get the backup manager instance.
	 *
	 * @since     1.0.0
	 * @return    SLBP_Backup_Manager|null    The backup manager instance.
	 */
	public function get_backup_manager() {
		return $this->resolve( 'backup_manager' );
	}

	/**
	 * Initialize mobile support and PWA features.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function init_mobile_support() {
		// This will be extended with mobile-specific classes in the future
		// For now, we handle mobile features through hooks
	}

	/**
	 * Enqueue public styles including mobile-responsive CSS.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_public_styles() {
		// Only enqueue on pages that need SkyLearn Billing Pro styles
		if ( $this->should_enqueue_public_assets() ) {
			wp_enqueue_style(
				'slbp-user-dashboard',
				SLBP_PLUGIN_URL . 'public/css/user-dashboard.css',
				array(),
				$this->version,
				'all'
			);
		}
	}

	/**
	 * Enqueue public scripts including mobile enhancements.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_public_scripts() {
		// Only enqueue on pages that need SkyLearn Billing Pro scripts
		if ( $this->should_enqueue_public_assets() ) {
			wp_enqueue_script(
				'slbp-user-dashboard',
				SLBP_PLUGIN_URL . 'public/js/user-dashboard.js',
				array( 'jquery' ),
				$this->version,
				true
			);

			// Add mobile/PWA configuration
			wp_localize_script( 'slbp-user-dashboard', 'slbp_mobile_config', array(
				'service_worker_url' => SLBP_PLUGIN_URL . 'public/sw.js',
				'manifest_url' => SLBP_PLUGIN_URL . 'public/manifest.json',
				'api_url' => rest_url( 'skylearn-billing-pro/v1/' ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
				'is_mobile' => wp_is_mobile(),
			) );
		}
	}

	/**
	 * Add PWA meta tags to head.
	 *
	 * @since    1.0.0
	 */
	public function add_pwa_meta_tags() {
		if ( ! $this->should_enqueue_public_assets() ) {
			return;
		}

		echo '<link rel="manifest" href="' . esc_url( SLBP_PLUGIN_URL . 'public/manifest.json' ) . '">' . "\n";
		echo '<meta name="theme-color" content="#6366f1">' . "\n";
		echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
		echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";
		echo '<meta name="apple-mobile-web-app-title" content="SkyLearn Pro">' . "\n";
		echo '<link rel="apple-touch-icon" href="' . esc_url( SLBP_PLUGIN_URL . 'assets/images/icon-apple-touch.png' ) . '">' . "\n";
		echo '<meta name="msapplication-TileColor" content="#6366f1">' . "\n";
		echo '<meta name="msapplication-config" content="' . esc_url( SLBP_PLUGIN_URL . 'public/browserconfig.xml' ) . '">' . "\n";
	}

	/**
	 * Add PWA scripts to footer.
	 *
	 * @since    1.0.0
	 */
	public function add_pwa_scripts() {
		if ( ! $this->should_enqueue_public_assets() ) {
			return;
		}

		?>
		<script>
		// Register service worker for PWA functionality
		if ('serviceWorker' in navigator) {
			window.addEventListener('load', function() {
				navigator.serviceWorker.register('<?php echo esc_url( SLBP_PLUGIN_URL . 'public/sw.js' ); ?>')
					.then(function(registration) {
						console.log('SkyLearn Billing Pro Service Worker registered');
					})
					.catch(function(error) {
						console.log('Service Worker registration failed:', error);
					});
			});
		}

		// Install prompt handling
		let deferredPrompt;
		window.addEventListener('beforeinstallprompt', function(e) {
			e.preventDefault();
			deferredPrompt = e;
			// Show install button if desired
			const installButton = document.querySelector('.slbp-install-app');
			if (installButton) {
				installButton.style.display = 'block';
				installButton.addEventListener('click', function() {
					deferredPrompt.prompt();
					deferredPrompt.userChoice.then(function(choiceResult) {
						deferredPrompt = null;
						installButton.style.display = 'none';
					});
				});
			}
		});
		</script>
		<?php
	}

	/**
	 * Add mobile viewport meta tag.
	 *
	 * @since    1.0.0
	 */
	public function add_mobile_viewport_meta() {
		// Check if viewport meta tag already exists
		if ( ! has_action( 'wp_head', array( $this, 'add_mobile_viewport_meta' ) ) ) {
			return;
		}

		echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">' . "\n";
	}

	/**
	 * Register manifest endpoint for PWA.
	 *
	 * @since    1.0.0
	 */
	public function register_manifest_endpoint() {
		add_rewrite_rule( '^slbp-manifest\.json$', 'index.php?slbp_manifest=1', 'top' );
		add_filter( 'query_vars', array( $this, 'add_manifest_query_var' ) );
		add_action( 'template_redirect', array( $this, 'serve_manifest' ) );
	}

	/**
	 * Add manifest query variable.
	 *
	 * @since    1.0.0
	 * @param    array    $query_vars    The query variables.
	 * @return   array    The modified query variables.
	 */
	public function add_manifest_query_var( $query_vars ) {
		$query_vars[] = 'slbp_manifest';
		return $query_vars;
	}

	/**
	 * Serve manifest file.
	 *
	 * @since    1.0.0
	 */
	public function serve_manifest() {
		if ( get_query_var( 'slbp_manifest' ) ) {
			header( 'Content-Type: application/json' );
			readfile( SLBP_PLUGIN_PATH . 'public/manifest.json' );
			exit;
		}
	}

	/**
	 * Check if we should enqueue public assets.
	 *
	 * @since    1.0.0
	 * @return   bool    Whether to enqueue public assets.
	 */
	private function should_enqueue_public_assets() {
		// Only enqueue on admin pages or pages with SkyLearn shortcodes/blocks
		return is_admin() || 
			   has_shortcode( get_post()->post_content ?? '', 'skylearn_dashboard' ) ||
			   has_block( 'skylearn/dashboard' ) ||
			   is_page( 'billing' ) ||
			   is_page( 'dashboard' ) ||
			   apply_filters( 'slbp_should_enqueue_public_assets', false );
	}

	/**
	 * Initialize enhanced security features for Phase 12.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function init_enhanced_security() {
		// Enhanced security manager with API security and advanced features
		$this->container['enhanced_security_manager'] = new SLBP_Security_Manager();
	}

	/**
	 * Initialize privacy management features for Phase 12.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function init_privacy_management() {
		// Privacy manager for GDPR/CCPA compliance
		$this->container['privacy_manager'] = new SLBP_Privacy_Manager();
	}

	/**
	 * Initialize PCI compliance features for Phase 12.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function init_pci_compliance() {
		// PCI DSS compliance manager
		$this->container['pci_compliance_manager'] = new SLBP_PCI_Compliance_Manager();
	}

	/**
	 * Initialize security dashboard for Phase 12.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function init_security_dashboard() {
		// Security dashboard for admin interface
		if ( is_admin() ) {
			$this->container['security_dashboard'] = new SLBP_Security_Dashboard();
		}
	}

	/**
	 * Get the privacy manager instance.
	 *
	 * @since     1.0.0
	 * @return    SLBP_Privacy_Manager|null    The privacy manager instance.
	 */
	public function get_privacy_manager() {
		return $this->resolve( 'privacy_manager' );
	}

	/**
	 * Get the PCI compliance manager instance.
	 *
	 * @since     1.0.0
	 * @return    SLBP_PCI_Compliance_Manager|null    The PCI compliance manager instance.
	 */
	public function get_pci_compliance_manager() {
		return $this->resolve( 'pci_compliance_manager' );
	}

	/**
	 * Get the security dashboard instance.
	 *
	 * @since     1.0.0
	 * @return    SLBP_Security_Dashboard|null    The security dashboard instance.
	 */
	public function get_security_dashboard() {
		return $this->resolve( 'security_dashboard' );
	}
}