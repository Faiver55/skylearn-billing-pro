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
		// Module initialization will be added here in future phases
		// This is where we'll load payment gateways, LMS integrations, etc.
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
}