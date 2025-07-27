<?php
/**
 * Admin notice handler for SkyLearn Billing Pro
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/admin
 */

/**
 * Handle admin notices for the plugin.
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/admin
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Admin_Notices {

	/**
	 * Initialize the admin notices.
	 *
	 * @since    1.0.0
	 */
	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'display_notices' ) );
	}

	/**
	 * Display admin notices.
	 *
	 * @since    1.0.0
	 */
	public static function display_notices() {
		// Check if we're on a SLBP admin page
		$screen = get_current_screen();
		if ( ! $screen || false === strpos( $screen->id, 'slbp' ) && false === strpos( $screen->id, 'skylearn-billing' ) ) {
			return;
		}

		// Check for LearnDash dependency
		if ( ! class_exists( 'SFWD_LMS' ) ) {
			self::display_notice(
				'warning',
				sprintf(
					/* translators: %s: LearnDash plugin name */
					esc_html__( 'SkyLearn Billing Pro requires %s to be installed and activated for full functionality.', 'skylearn-billing-pro' ),
					'<strong>LearnDash LMS</strong>'
				),
				'learndash-missing'
			);
		}

		// Check for initial configuration
		$payment_settings = get_option( 'slbp_payment_settings', array() );
		if ( empty( $payment_settings['lemon_squeezy_api_key'] ) ) {
			self::display_notice(
				'info',
				sprintf(
					/* translators: %s: settings page URL */
					esc_html__( 'Welcome to SkyLearn Billing Pro! Please %s to get started.', 'skylearn-billing-pro' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=slbp-settings&tab=payment' ) ) . '">' . esc_html__( 'configure your payment gateway', 'skylearn-billing-pro' ) . '</a>'
				),
				'initial-setup'
			);
		}

		// Check for SSL in production
		if ( ! is_ssl() && ! self::is_local_development() ) {
			self::display_notice(
				'warning',
				esc_html__( 'SkyLearn Billing Pro requires SSL/HTTPS for secure payment processing. Please enable SSL on your site.', 'skylearn-billing-pro' ),
				'ssl-required'
			);
		}
	}

	/**
	 * Display a single notice.
	 *
	 * @since    1.0.0
	 * @param    string    $type        The notice type (success, error, warning, info).
	 * @param    string    $message     The notice message.
	 * @param    string    $key         Unique key for the notice (for dismissal).
	 * @param    bool      $dismissible Whether the notice is dismissible.
	 */
	public static function display_notice( $type, $message, $key = '', $dismissible = true ) {
		// Check if notice has been dismissed
		if ( $dismissible && $key && get_user_meta( get_current_user_id(), 'slbp_dismissed_notice_' . $key, true ) ) {
			return;
		}

		$classes = array( 'notice', 'notice-' . $type );
		if ( $dismissible ) {
			$classes[] = 'is-dismissible';
		}

		printf(
			'<div class="%s" data-notice-key="%s"><p>%s</p></div>',
			esc_attr( implode( ' ', $classes ) ),
			esc_attr( $key ),
			wp_kses_post( $message )
		);
	}

	/**
	 * Check if this is a local development environment.
	 *
	 * @since    1.0.0
	 * @return   bool    True if local development, false otherwise.
	 */
	private static function is_local_development() {
		$local_hosts = array( 'localhost', '127.0.0.1', '::1' );
		$host = isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : '';
		
		// Remove port number if present
		$host = explode( ':', $host )[0];
		
		return in_array( $host, $local_hosts, true ) || 
			   false !== strpos( $host, '.local' ) ||
			   false !== strpos( $host, '.test' ) ||
			   false !== strpos( $host, '.dev' );
	}

	/**
	 * Handle AJAX notice dismissal.
	 *
	 * @since    1.0.0
	 */
	public static function handle_dismiss_notice() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'slbp_admin_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'skylearn-billing-pro' ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'skylearn-billing-pro' ) );
		}

		$notice_key = sanitize_text_field( $_POST['notice_key'] ?? '' );
		
		if ( $notice_key ) {
			update_user_meta( get_current_user_id(), 'slbp_dismissed_notice_' . $notice_key, 1 );
			wp_send_json_success();
		}

		wp_send_json_error();
	}
}