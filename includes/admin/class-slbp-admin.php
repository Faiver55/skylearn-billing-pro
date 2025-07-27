<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and all hooks for the admin area.
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/admin
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Admin menu instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SLBP_Admin_Menu    $menu    The admin menu instance.
	 */
	private $menu;

	/**
	 * Settings instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SLBP_Settings    $settings    The settings instance.
	 */
	private $settings;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name       The name of this plugin.
	 * @param    string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		$this->load_dependencies();
		$this->init_components();
	}

	/**
	 * Load the required dependencies for the admin area.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		// Dependencies will be loaded via autoloader
	}

	/**
	 * Initialize admin components.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function init_components() {
		$this->menu     = new SLBP_Admin_Menu( $this->plugin_name, $this->version );
		$this->settings = new SLBP_Settings( $this->plugin_name, $this->version );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 * @param    string    $hook_suffix    The hook suffix for the current admin page.
	 */
	public function enqueue_styles( $hook_suffix ) {
		// Only load on our plugin pages
		if ( ! $this->is_slbp_admin_page( $hook_suffix ) ) {
			return;
		}

		wp_enqueue_style(
			$this->plugin_name . '-admin',
			SLBP_PLUGIN_URL . 'admin/css/admin-style.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 * @param    string    $hook_suffix    The hook suffix for the current admin page.
	 */
	public function enqueue_scripts( $hook_suffix ) {
		// Only load on our plugin pages
		if ( ! $this->is_slbp_admin_page( $hook_suffix ) ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name . '-admin',
			SLBP_PLUGIN_URL . 'admin/js/admin-script.js',
			array( 'jquery' ),
			$this->version,
			false
		);

		// Localize script for AJAX
		wp_localize_script(
			$this->plugin_name . '-admin',
			'slbp_admin',
			array(
				'ajax_url'    => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'slbp_admin_nonce' ),
				'plugin_name' => $this->plugin_name,
				'strings'     => array(
					'saving'           => esc_html__( 'Saving...', 'skylearn-billing-pro' ),
					'saved'            => esc_html__( 'Settings saved!', 'skylearn-billing-pro' ),
					'error'            => esc_html__( 'An error occurred. Please try again.', 'skylearn-billing-pro' ),
					'test_connection'  => esc_html__( 'Testing connection...', 'skylearn-billing-pro' ),
					'connection_valid' => esc_html__( 'Connection successful!', 'skylearn-billing-pro' ),
					'connection_error' => esc_html__( 'Connection failed. Please check your settings.', 'skylearn-billing-pro' ),
				),
			)
		);
	}

	/**
	 * Check if current page is a SLBP admin page.
	 *
	 * @since    1.0.0
	 * @param    string    $hook_suffix    The hook suffix for the current admin page.
	 * @return   bool                      True if SLBP admin page, false otherwise.
	 */
	private function is_slbp_admin_page( $hook_suffix ) {
		$slbp_pages = array(
			'toplevel_page_skylearn-billing-pro',
			'skylearn-billing_page_slbp-settings',
			'skylearn-billing_page_slbp-analytics',
			'skylearn-billing_page_slbp-license',
			'skylearn-billing_page_slbp-help',
		);

		return in_array( $hook_suffix, $slbp_pages, true );
	}

	/**
	 * Display admin notices.
	 *
	 * @since    1.0.0
	 */
	public function display_admin_notices() {
		// Check for settings update messages
		if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true' ) {
			add_settings_error(
				'slbp_messages',
				'slbp_message',
				esc_html__( 'Settings saved successfully!', 'skylearn-billing-pro' ),
				'updated'
			);
		}

		// Check for any stored notices
		$notices = get_transient( 'slbp_admin_notices' );
		if ( $notices && is_array( $notices ) ) {
			foreach ( $notices as $notice ) {
				printf(
					'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
					esc_attr( $notice['type'] ),
					wp_kses_post( $notice['message'] )
				);
			}
			delete_transient( 'slbp_admin_notices' );
		}

		settings_errors( 'slbp_messages' );
	}

	/**
	 * Add admin notice.
	 *
	 * @since    1.0.0
	 * @param    string    $message    The notice message.
	 * @param    string    $type       The notice type (success, error, warning, info).
	 */
	public function add_admin_notice( $message, $type = 'info' ) {
		$notices   = get_transient( 'slbp_admin_notices' ) ?: array();
		$notices[] = array(
			'message' => $message,
			'type'    => $type,
		);
		set_transient( 'slbp_admin_notices', $notices, 60 );
	}

	/**
	 * Handle AJAX requests for admin actions.
	 *
	 * @since    1.0.0
	 */
	public function handle_ajax_test_connection() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'slbp_admin_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'skylearn-billing-pro' ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'skylearn-billing-pro' ) );
		}

		$gateway = sanitize_text_field( $_POST['gateway'] ?? '' );
		$api_key = sanitize_text_field( $_POST['api_key'] ?? '' );
		$store_id = sanitize_text_field( $_POST['store_id'] ?? '' );

		// Test connection based on gateway
		$result = $this->test_gateway_connection( $gateway, $api_key, $store_id );

		wp_send_json( $result );
	}

	/**
	 * Test gateway connection.
	 *
	 * @since    1.0.0
	 * @param    string    $gateway     The gateway to test.
	 * @param    string    $api_key     The API key.
	 * @param    string    $store_id    The store ID.
	 * @return   array                  Test result.
	 */
	private function test_gateway_connection( $gateway, $api_key, $store_id ) {
		// Placeholder for gateway testing logic
		// This will be implemented in Phase 3 with actual gateway integration
		
		if ( empty( $api_key ) ) {
			return array(
				'success' => false,
				'message' => esc_html__( 'API key is required.', 'skylearn-billing-pro' ),
			);
		}

		if ( $gateway === 'lemon_squeezy' && empty( $store_id ) ) {
			return array(
				'success' => false,
				'message' => esc_html__( 'Store ID is required for Lemon Squeezy.', 'skylearn-billing-pro' ),
			);
		}

		// Simulate successful connection for now
		return array(
			'success' => true,
			'message' => esc_html__( 'Connection test successful! (Placeholder)', 'skylearn-billing-pro' ),
		);
	}

	/**
	 * Get the admin menu instance.
	 *
	 * @since    1.0.0
	 * @return   SLBP_Admin_Menu    The admin menu instance.
	 */
	public function get_menu() {
		return $this->menu;
	}

	/**
	 * Get the settings instance.
	 *
	 * @since    1.0.0
	 * @return   SLBP_Settings    The settings instance.
	 */
	public function get_settings() {
		return $this->settings;
	}
}