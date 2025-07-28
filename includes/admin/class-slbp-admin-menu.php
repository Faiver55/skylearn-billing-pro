<?php
/**
 * The admin menu functionality of the plugin.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/admin
 */

/**
 * The admin menu functionality of the plugin.
 *
 * Defines the admin menu structure, capabilities, and page routing.
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/admin
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Admin_Menu {

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
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name       The name of this plugin.
	 * @param    string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the admin menu.
	 *
	 * @since    1.0.0
	 */
	public function register_admin_menu() {
		// Main menu page
		add_menu_page(
			esc_html__( 'SkyLearn Billing Pro', 'skylearn-billing-pro' ),
			esc_html__( 'SkyLearn Billing', 'skylearn-billing-pro' ),
			'manage_options',
			'skylearn-billing-pro',
			array( $this, 'display_dashboard_page' ),
			$this->get_menu_icon(),
			58 // Position between Comments (25) and Appearance (60)
		);

		// Dashboard (same as main page)
		add_submenu_page(
			'skylearn-billing-pro',
			esc_html__( 'Dashboard', 'skylearn-billing-pro' ),
			esc_html__( 'Dashboard', 'skylearn-billing-pro' ),
			'manage_options',
			'skylearn-billing-pro',
			array( $this, 'display_dashboard_page' )
		);

		// Settings
		add_submenu_page(
			'skylearn-billing-pro',
			esc_html__( 'Settings', 'skylearn-billing-pro' ),
			esc_html__( 'Settings', 'skylearn-billing-pro' ),
			'manage_options',
			'slbp-settings',
			array( $this, 'display_settings_page' )
		);

		// Analytics
		add_submenu_page(
			'skylearn-billing-pro',
			esc_html__( 'Analytics', 'skylearn-billing-pro' ),
			esc_html__( 'Analytics', 'skylearn-billing-pro' ),
			'manage_options',
			'slbp-analytics',
			array( $this, 'display_analytics_page' )
		);

		// Notifications
		add_submenu_page(
			'skylearn-billing-pro',
			esc_html__( 'Notifications', 'skylearn-billing-pro' ),
			esc_html__( 'Notifications', 'skylearn-billing-pro' ),
			'manage_options',
			'slbp-notifications',
			array( $this, 'display_notifications_page' )
		);

		// Integrations
		add_submenu_page(
			'skylearn-billing-pro',
			esc_html__( 'Integrations', 'skylearn-billing-pro' ),
			esc_html__( 'Integrations', 'skylearn-billing-pro' ),
			'manage_options',
			'slbp-integrations',
			array( $this, 'display_integrations_page' )
		);

		// Enrollment Logs
		add_submenu_page(
			'skylearn-billing-pro',
			esc_html__( 'Enrollment Logs', 'skylearn-billing-pro' ),
			esc_html__( 'Enrollment Logs', 'skylearn-billing-pro' ),
			'manage_options',
			'slbp-enrollment-logs',
			array( $this, 'display_enrollment_logs_page' )
		);

		// Developer API
		add_submenu_page(
			'skylearn-billing-pro',
			esc_html__( 'Developer API', 'skylearn-billing-pro' ),
			esc_html__( 'Developer API', 'skylearn-billing-pro' ),
			'manage_options',
			'slbp-developer-api',
			array( $this, 'display_developer_api_page' )
		);

		// License Management
		add_submenu_page(
			'skylearn-billing-pro',
			esc_html__( 'License', 'skylearn-billing-pro' ),
			esc_html__( 'License', 'skylearn-billing-pro' ),
			'manage_options',
			'slbp-license',
			array( $this, 'display_license_page' )
		);

		// Documentation/Help
		add_submenu_page(
			'skylearn-billing-pro',
			esc_html__( 'Help & Documentation', 'skylearn-billing-pro' ),
			esc_html__( 'Help', 'skylearn-billing-pro' ),
			'manage_options',
			'slbp-help',
			array( $this, 'display_help_page' )
		);
	}

	/**
	 * Get the menu icon.
	 *
	 * @since    1.0.0
	 * @return   string    The menu icon SVG or dashicon.
	 */
	private function get_menu_icon() {
		// Custom SVG icon for SkyLearn Billing
		return 'data:image/svg+xml;base64,' . base64_encode(
			'<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M10 2C8.89543 2 8 2.89543 8 4V6H6C4.89543 6 4 6.89543 4 8V16C4 17.1046 4.89543 18 6 18H14C15.1046 18 16 17.1046 16 16V8C16 6.89543 15.1046 6 14 6H12V4C12 2.89543 11.1046 2 10 2ZM10 4V6H10V4ZM6 8H14V16H6V8ZM8 10V12H12V10H8Z" fill="currentColor"/>
			</svg>'
		);
	}

	/**
	 * Display the dashboard page.
	 *
	 * @since    1.0.0
	 */
	public function display_dashboard_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'skylearn-billing-pro' ) );
		}

		include_once SLBP_PLUGIN_PATH . 'admin/partials/dashboard.php';
	}

	/**
	 * Display the settings page.
	 *
	 * @since    1.0.0
	 */
	public function display_settings_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'skylearn-billing-pro' ) );
		}

		include_once SLBP_PLUGIN_PATH . 'admin/partials/settings.php';
	}

	/**
	 * Display the analytics page.
	 *
	 * @since    1.0.0
	 */
	public function display_analytics_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'skylearn-billing-pro' ) );
		}

		include_once SLBP_PLUGIN_PATH . 'admin/partials/analytics.php';
	}

	/**
	 * Display the license page.
	 *
	 * @since    1.0.0
	 */
	public function display_license_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'skylearn-billing-pro' ) );
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'License Management', 'skylearn-billing-pro' ) . '</h1>';
		echo '<div class="slbp-card">';
		echo '<p>' . esc_html__( 'License management functionality will be available in a future release.', 'skylearn-billing-pro' ) . '</p>';
		echo '<p>' . esc_html__( 'This section will handle license activation, validation, and renewal processes.', 'skylearn-billing-pro' ) . '</p>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Display the help page.
	 *
	 * @since    1.0.0
	 */
	public function display_help_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'skylearn-billing-pro' ) );
		}

		include_once SLBP_PLUGIN_PATH . 'admin/partials/help.php';
	}

	/**
	 * Display the enrollment logs page.
	 *
	 * @since    1.0.0
	 */
	public function display_enrollment_logs_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'skylearn-billing-pro' ) );
		}

		include_once SLBP_PLUGIN_PATH . 'admin/partials/enrollment-logs.php';
	}

	/**
	 * Display the developer API page.
	 *
	 * @since    1.0.0
	 */
	public function display_developer_api_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'skylearn-billing-pro' ) );
		}

		include_once SLBP_PLUGIN_PATH . 'admin/partials/developer-api.php';
	}

	/**
	 * Display the notifications page.
	 *
	 * @since    1.0.0
	 */
	public function display_notifications_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'skylearn-billing-pro' ) );
		}

		// Get notification manager instance
		$plugin = SLBP_Plugin::get_instance();
		$notification_manager = $plugin->resolve( 'notification_manager' );
		
		if ( $notification_manager ) {
			$admin_notifications = new SLBP_Admin_Notifications( $notification_manager );
			$admin_notifications->render_notifications_page();
		} else {
			echo '<div class="wrap">';
			echo '<h1>' . esc_html__( 'Notifications', 'skylearn-billing-pro' ) . '</h1>';
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Notification system not initialized.', 'skylearn-billing-pro' ) . '</p></div>';
			echo '</div>';
		}
	}

	/**
	 * Display the integrations page.
	 *
	 * @since    1.0.0
	 */
	public function display_integrations_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'skylearn-billing-pro' ) );
		}

		include_once SLBP_PLUGIN_PATH . 'admin/partials/integrations.php';
	}

	/**
	 * Add badge notifications to menu items.
	 *
	 * @since    1.0.0
	 * @param    string    $menu_slug    The menu slug to add badge to.
	 * @param    string    $badge_text   The badge text.
	 * @param    string    $badge_type   The badge type (update, error, warning).
	 */
	public function add_menu_badge( $menu_slug, $badge_text, $badge_type = 'update' ) {
		global $submenu;
		
		if ( isset( $submenu['skylearn-billing-pro'] ) ) {
			foreach ( $submenu['skylearn-billing-pro'] as $key => $menu_item ) {
				if ( $menu_item[2] === $menu_slug ) {
					$submenu['skylearn-billing-pro'][ $key ][0] .= sprintf(
						' <span class="slbp-badge slbp-badge-%s">%s</span>',
						esc_attr( $badge_type ),
						esc_html( $badge_text )
					);
					break;
				}
			}
		}
	}

	/**
	 * Check if current page is a SLBP admin page.
	 *
	 * @since    1.0.0
	 * @return   bool    True if on SLBP admin page, false otherwise.
	 */
	public function is_slbp_admin_page() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return false;
		}

		$slbp_pages = array(
			'toplevel_page_skylearn-billing-pro',
			'skylearn-billing_page_slbp-settings',
			'skylearn-billing_page_slbp-analytics',
			'skylearn-billing_page_slbp-notifications',
			'skylearn-billing_page_slbp-integrations',
			'skylearn-billing_page_slbp-enrollment-logs',
			'skylearn-billing_page_slbp-developer-api',
			'skylearn-billing_page_slbp-license',
			'skylearn-billing_page_slbp-help',
		);

		return in_array( $screen->id, $slbp_pages, true );
	}

	/**
	 * Get the current admin page slug.
	 *
	 * @since    1.0.0
	 * @return   string|null    The current page slug or null.
	 */
	public function get_current_page_slug() {
		return isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : null;
	}
}