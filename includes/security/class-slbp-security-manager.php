<?php
/**
 * Enhanced security features for the plugin.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/security
 */

/**
 * Enhanced security features for the plugin.
 *
 * Provides 2FA authentication, security audits, and enhanced
 * security measures for admin users and sensitive operations.
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/security
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Security_Manager {

	/**
	 * The audit logger instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SLBP_Audit_Logger    $audit_logger    The audit logger instance.
	 */
	private $audit_logger;

	/**
	 * Security settings.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $settings    Security settings.
	 */
	private $settings;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->audit_logger = new SLBP_Audit_Logger();
		$this->settings = get_option( 'slbp_security_settings', array() );
		$this->init_hooks();
	}

	/**
	 * Initialize security hooks.
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		// 2FA hooks
		add_action( 'wp_login', array( $this, 'check_2fa_requirement' ), 10, 2 );
		add_action( 'login_form', array( $this, 'add_2fa_login_field' ) );
		add_filter( 'wp_authenticate_user', array( $this, 'validate_2fa_login' ), 10, 2 );

		// Admin security hooks
		add_action( 'admin_init', array( $this, 'enforce_admin_security' ) );
		add_action( 'admin_notices', array( $this, 'show_security_notices' ) );

		// Login security
		add_action( 'wp_login_failed', array( $this, 'handle_failed_login' ) );
		add_filter( 'authenticate', array( $this, 'check_brute_force_protection' ), 30, 3 );

		// AJAX handlers
		add_action( 'wp_ajax_slbp_generate_2fa_secret', array( $this, 'ajax_generate_2fa_secret' ) );
		add_action( 'wp_ajax_slbp_verify_2fa_setup', array( $this, 'ajax_verify_2fa_setup' ) );
		add_action( 'wp_ajax_slbp_disable_2fa', array( $this, 'ajax_disable_2fa' ) );
		add_action( 'wp_ajax_slbp_run_security_audit', array( $this, 'ajax_run_security_audit' ) );

		// User profile 2FA settings
		add_action( 'show_user_profile', array( $this, 'show_2fa_profile_fields' ) );
		add_action( 'edit_user_profile', array( $this, 'show_2fa_profile_fields' ) );
		add_action( 'personal_options_update', array( $this, 'save_2fa_profile_fields' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_2fa_profile_fields' ) );

		// Security audit scheduler
		add_action( 'slbp_daily_security_audit', array( $this, 'run_daily_security_audit' ) );
	}

	/**
	 * Check if user requires 2FA and redirect if needed.
	 *
	 * @since    1.0.0
	 * @param    string    $user_login    Username.
	 * @param    WP_User   $user         User object.
	 */
	public function check_2fa_requirement( $user_login, $user ) {
		if ( ! $this->is_2fa_required_for_user( $user ) ) {
			return;
		}

		if ( ! $this->is_2fa_verified_for_session( $user->ID ) ) {
			// Destroy the session and redirect to 2FA verification
			wp_destroy_current_session();
			wp_redirect( add_query_arg( array(
				'action' => 'slbp_2fa_verify',
				'user_id' => $user->ID,
				'redirect_to' => admin_url(),
			), wp_login_url() ) );
			exit;
		}
	}

	/**
	 * Add 2FA field to login form.
	 *
	 * @since    1.0.0
	 */
	public function add_2fa_login_field() {
		if ( isset( $_GET['action'] ) && 'slbp_2fa_verify' === $_GET['action'] ) {
			?>
			<p>
				<label for="slbp_2fa_code"><?php esc_html_e( '2FA Verification Code', 'skylearn-billing-pro' ); ?></label>
				<input type="text" name="slbp_2fa_code" id="slbp_2fa_code" class="input" maxlength="6" autocomplete="off" />
			</p>
			<script>
			document.getElementById('slbp_2fa_code').focus();
			</script>
			<?php
		}
	}

	/**
	 * Validate 2FA code during login.
	 *
	 * @since    1.0.0
	 * @param    WP_User|WP_Error    $user      User object or error.
	 * @param    string              $password  Password.
	 * @return   WP_User|WP_Error              User object or error.
	 */
	public function validate_2fa_login( $user, $password ) {
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		if ( isset( $_GET['action'] ) && 'slbp_2fa_verify' === $_GET['action'] ) {
			$user_id = intval( $_GET['user_id'] ?? 0 );
			$provided_code = sanitize_text_field( $_POST['slbp_2fa_code'] ?? '' );

			if ( ! $this->verify_2fa_code( $user_id, $provided_code ) ) {
				$this->audit_logger->log_event(
					'security',
					'2fa_verification_failed',
					$user_id,
					array(
						'ip_address' => $this->get_user_ip(),
						'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
					),
					'warning'
				);

				return new WP_Error( '2fa_verification_failed', __( 'Invalid 2FA verification code.', 'skylearn-billing-pro' ) );
			}

			// Mark 2FA as verified for this session
			$this->mark_2fa_verified_for_session( $user_id );

			$this->audit_logger->log_event(
				'security',
				'2fa_verification_success',
				$user_id,
				array(),
				'info'
			);
		}

		return $user;
	}

	/**
	 * Generate 2FA secret for user.
	 *
	 * @since    1.0.0
	 * @param    int    $user_id    User ID.
	 * @return   string             Base32 encoded secret.
	 */
	public function generate_2fa_secret( $user_id = null ) {
		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		// Generate a random 20-byte secret and encode it in base32
		$secret = $this->base32_encode( random_bytes( 20 ) );
		
		// Store the secret (temporarily, until verified)
		update_user_meta( $user_id, 'slbp_2fa_secret_temp', $secret );

		$this->audit_logger->log_event(
			'security',
			'2fa_secret_generated',
			$user_id,
			array(),
			'info'
		);

		return $secret;
	}

	/**
	 * Verify 2FA setup with provided code.
	 *
	 * @since    1.0.0
	 * @param    int       $user_id    User ID.
	 * @param    string    $code      Verification code.
	 * @return   bool                 Whether verification was successful.
	 */
	public function verify_2fa_setup( $user_id, $code ) {
		$temp_secret = get_user_meta( $user_id, 'slbp_2fa_secret_temp', true );
		
		if ( empty( $temp_secret ) ) {
			return false;
		}

		if ( $this->verify_totp_code( $temp_secret, $code ) ) {
			// Move from temp to permanent
			update_user_meta( $user_id, 'slbp_2fa_secret', $temp_secret );
			update_user_meta( $user_id, 'slbp_2fa_enabled', true );
			delete_user_meta( $user_id, 'slbp_2fa_secret_temp' );

			// Generate backup codes
			$backup_codes = $this->generate_backup_codes( $user_id );

			$this->audit_logger->log_event(
				'security',
				'2fa_enabled',
				$user_id,
				array(),
				'info'
			);

			return true;
		}

		return false;
	}

	/**
	 * Verify 2FA code for login.
	 *
	 * @since    1.0.0
	 * @param    int       $user_id    User ID.
	 * @param    string    $code      Verification code.
	 * @return   bool                 Whether verification was successful.
	 */
	public function verify_2fa_code( $user_id, $code ) {
		$secret = get_user_meta( $user_id, 'slbp_2fa_secret', true );
		
		if ( empty( $secret ) ) {
			return false;
		}

		// Check TOTP code
		if ( $this->verify_totp_code( $secret, $code ) ) {
			return true;
		}

		// Check backup codes
		return $this->verify_backup_code( $user_id, $code );
	}

	/**
	 * Verify TOTP code against secret.
	 *
	 * @since    1.0.0
	 * @param    string    $secret    Base32 encoded secret.
	 * @param    string    $code     6-digit TOTP code.
	 * @return   bool               Whether code is valid.
	 */
	private function verify_totp_code( $secret, $code ) {
		$time_slice = floor( time() / 30 );
		
		// Check current time slice and previous/next for clock drift
		for ( $i = -1; $i <= 1; $i++ ) {
			$calculated_code = $this->generate_totp_code( $secret, $time_slice + $i );
			if ( hash_equals( $calculated_code, str_pad( $code, 6, '0', STR_PAD_LEFT ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Generate TOTP code for given secret and time slice.
	 *
	 * @since    1.0.0
	 * @param    string    $secret       Base32 encoded secret.
	 * @param    int       $time_slice   Time slice.
	 * @return   string                  6-digit TOTP code.
	 */
	private function generate_totp_code( $secret, $time_slice ) {
		$secret_key = $this->base32_decode( $secret );
		$time = pack( 'N*', 0 ) . pack( 'N*', $time_slice );
		$hash = hash_hmac( 'sha1', $time, $secret_key, true );
		$offset = ord( $hash[19] ) & 0xf;
		$code = (
			( ( ord( $hash[ $offset + 0 ] ) & 0x7f ) << 24 ) |
			( ( ord( $hash[ $offset + 1 ] ) & 0xff ) << 16 ) |
			( ( ord( $hash[ $offset + 2 ] ) & 0xff ) << 8 ) |
			( ord( $hash[ $offset + 3 ] ) & 0xff )
		) % pow( 10, 6 );

		return str_pad( $code, 6, '0', STR_PAD_LEFT );
	}

	/**
	 * Generate backup codes for user.
	 *
	 * @since    1.0.0
	 * @param    int    $user_id    User ID.
	 * @return   array             Array of backup codes.
	 */
	private function generate_backup_codes( $user_id ) {
		$codes = array();
		
		for ( $i = 0; $i < 10; $i++ ) {
			$codes[] = strtoupper( substr( md5( random_bytes( 16 ) ), 0, 8 ) );
		}

		update_user_meta( $user_id, 'slbp_2fa_backup_codes', $codes );

		return $codes;
	}

	/**
	 * Verify backup code.
	 *
	 * @since    1.0.0
	 * @param    int       $user_id    User ID.
	 * @param    string    $code      Backup code.
	 * @return   bool                 Whether code is valid.
	 */
	private function verify_backup_code( $user_id, $code ) {
		$backup_codes = get_user_meta( $user_id, 'slbp_2fa_backup_codes', true );
		
		if ( ! is_array( $backup_codes ) ) {
			return false;
		}

		$code = strtoupper( $code );
		$key = array_search( $code, $backup_codes, true );
		
		if ( false !== $key ) {
			// Remove used backup code
			unset( $backup_codes[ $key ] );
			update_user_meta( $user_id, 'slbp_2fa_backup_codes', $backup_codes );

			$this->audit_logger->log_event(
				'security',
				'backup_code_used',
				$user_id,
				array( 'code' => substr( $code, 0, 2 ) . '***' ),
				'info'
			);

			return true;
		}

		return false;
	}

	/**
	 * Run comprehensive security audit.
	 *
	 * @since    1.0.0
	 * @return   array    Security audit results.
	 */
	public function run_security_audit() {
		$audit_results = array();

		// Check WordPress version
		$audit_results['wordpress_version'] = $this->check_wordpress_version();

		// Check plugin versions
		$audit_results['plugin_updates'] = $this->check_plugin_updates();

		// Check SSL/TLS configuration
		$audit_results['ssl_config'] = $this->check_ssl_configuration();

		// Check file permissions
		$audit_results['file_permissions'] = $this->check_file_permissions();

		// Check user security
		$audit_results['user_security'] = $this->check_user_security();

		// Check database security
		$audit_results['database_security'] = $this->check_database_security();

		// Check failed login attempts
		$audit_results['login_security'] = $this->check_login_security();

		// Calculate overall security score
		$audit_results['security_score'] = $this->calculate_security_score( $audit_results );

		// Log the audit
		$this->audit_logger->log_event(
			'security',
			'security_audit_completed',
			get_current_user_id(),
			array( 'security_score' => $audit_results['security_score'] ),
			'info'
		);

		return $audit_results;
	}

	/**
	 * Handle failed login attempt.
	 *
	 * @since    1.0.0
	 * @param    string    $username    Username that failed.
	 */
	public function handle_failed_login( $username ) {
		$ip = $this->get_user_ip();
		$attempts_key = 'slbp_login_attempts_' . md5( $ip );
		$attempts = get_transient( $attempts_key );

		if ( false === $attempts ) {
			$attempts = array();
		}

		$attempts[] = array(
			'username' => $username,
			'timestamp' => time(),
		);

		// Keep only last 10 attempts
		$attempts = array_slice( $attempts, -10 );

		set_transient( $attempts_key, $attempts, HOUR_IN_SECONDS );

		// Log the failed attempt
		$this->audit_logger->log_event(
			'security',
			'login_failed',
			0,
			array(
				'username' => $username,
				'ip_address' => $ip,
				'attempt_count' => count( $attempts ),
			),
			'warning'
		);
	}

	/**
	 * Check for brute force attacks and block if necessary.
	 *
	 * @since    1.0.0
	 * @param    null|WP_User|WP_Error    $user      User object or null.
	 * @param    string                   $username  Username.
	 * @param    string                   $password  Password.
	 * @return   null|WP_User|WP_Error              User object, error, or null.
	 */
	public function check_brute_force_protection( $user, $username, $password ) {
		if ( empty( $username ) ) {
			return $user;
		}

		$ip = $this->get_user_ip();
		$attempts_key = 'slbp_login_attempts_' . md5( $ip );
		$attempts = get_transient( $attempts_key );

		if ( ! is_array( $attempts ) ) {
			return $user;
		}

		// Count recent failed attempts (last 15 minutes)
		$recent_attempts = 0;
		$cutoff_time = time() - ( 15 * MINUTE_IN_SECONDS );

		foreach ( $attempts as $attempt ) {
			if ( $attempt['timestamp'] > $cutoff_time ) {
				$recent_attempts++;
			}
		}

		// Block if too many recent attempts
		$max_attempts = $this->settings['max_login_attempts'] ?? 5;
		
		if ( $recent_attempts >= $max_attempts ) {
			$this->audit_logger->log_event(
				'security',
				'brute_force_blocked',
				0,
				array(
					'ip_address' => $ip,
					'attempt_count' => $recent_attempts,
					'username' => $username,
				),
				'error'
			);

			return new WP_Error( 'too_many_attempts', __( 'Too many failed login attempts. Please try again later.', 'skylearn-billing-pro' ) );
		}

		return $user;
	}

	/**
	 * Show 2FA profile fields.
	 *
	 * @since    1.0.0
	 * @param    WP_User    $user    User object.
	 */
	public function show_2fa_profile_fields( $user ) {
		if ( ! current_user_can( 'edit_user', $user->ID ) ) {
			return;
		}

		$is_2fa_enabled = get_user_meta( $user->ID, 'slbp_2fa_enabled', true );
		$backup_codes = get_user_meta( $user->ID, 'slbp_2fa_backup_codes', true );
		?>
		<h3><?php esc_html_e( 'Two-Factor Authentication', 'skylearn-billing-pro' ); ?></h3>
		<table class="form-table">
			<tr>
				<th><label for="slbp_2fa_status"><?php esc_html_e( '2FA Status', 'skylearn-billing-pro' ); ?></label></th>
				<td>
					<?php if ( $is_2fa_enabled ) : ?>
						<span class="dashicons dashicons-yes-alt" style="color: green;"></span>
						<?php esc_html_e( 'Enabled', 'skylearn-billing-pro' ); ?>
						<button type="button" id="slbp-disable-2fa" class="button"><?php esc_html_e( 'Disable 2FA', 'skylearn-billing-pro' ); ?></button>
					<?php else : ?>
						<span class="dashicons dashicons-warning" style="color: orange;"></span>
						<?php esc_html_e( 'Disabled', 'skylearn-billing-pro' ); ?>
						<button type="button" id="slbp-setup-2fa" class="button button-primary"><?php esc_html_e( 'Setup 2FA', 'skylearn-billing-pro' ); ?></button>
					<?php endif; ?>
				</td>
			</tr>
			<?php if ( $is_2fa_enabled && is_array( $backup_codes ) ) : ?>
			<tr>
				<th><label for="slbp_backup_codes"><?php esc_html_e( 'Backup Codes', 'skylearn-billing-pro' ); ?></label></th>
				<td>
					<p><?php printf( esc_html__( 'You have %d backup codes remaining.', 'skylearn-billing-pro' ), count( $backup_codes ) ); ?></p>
					<button type="button" id="slbp-show-backup-codes" class="button"><?php esc_html_e( 'Show Backup Codes', 'skylearn-billing-pro' ); ?></button>
				</td>
			</tr>
			<?php endif; ?>
		</table>

		<div id="slbp-2fa-setup-modal" style="display:none;">
			<div id="slbp-qr-code"></div>
			<p><?php esc_html_e( 'Scan this QR code with your authenticator app, then enter the 6-digit code below:', 'skylearn-billing-pro' ); ?></p>
			<input type="text" id="slbp-2fa-verification-code" placeholder="<?php esc_attr_e( '6-digit code', 'skylearn-billing-pro' ); ?>" maxlength="6" />
			<button type="button" id="slbp-verify-2fa-setup" class="button button-primary"><?php esc_html_e( 'Verify', 'skylearn-billing-pro' ); ?></button>
		</div>
		<?php
	}

	/**
	 * AJAX handler for generating 2FA secret.
	 *
	 * @since    1.0.0
	 */
	public function ajax_generate_2fa_secret() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'slbp_security_nonce' ) ) {
			wp_die( 'Security check failed.' );
		}

		$user_id = get_current_user_id();
		$secret = $this->generate_2fa_secret( $user_id );

		// Generate QR code URL
		$user = get_userdata( $user_id );
		$qr_url = $this->generate_qr_code_url( $user->user_email, $secret );

		wp_send_json_success( array(
			'secret' => $secret,
			'qr_url' => $qr_url,
		) );
	}

	/**
	 * AJAX handler for verifying 2FA setup.
	 *
	 * @since    1.0.0
	 */
	public function ajax_verify_2fa_setup() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'slbp_security_nonce' ) ) {
			wp_die( 'Security check failed.' );
		}

		$user_id = get_current_user_id();
		$code = sanitize_text_field( $_POST['code'] );

		if ( $this->verify_2fa_setup( $user_id, $code ) ) {
			wp_send_json_success( array( 'message' => __( '2FA has been successfully enabled.', 'skylearn-billing-pro' ) ) );
		} else {
			wp_send_json_error( __( 'Invalid verification code. Please try again.', 'skylearn-billing-pro' ) );
		}
	}

	/**
	 * AJAX handler for running security audit.
	 *
	 * @since    1.0.0
	 */
	public function ajax_run_security_audit() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'slbp_security_nonce' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Security check failed.' );
		}

		$audit_results = $this->run_security_audit();

		wp_send_json_success( $audit_results );
	}

	/**
	 * Generate QR code URL for 2FA setup.
	 *
	 * @since    1.0.0
	 * @param    string    $email     User email.
	 * @param    string    $secret    2FA secret.
	 * @return   string              QR code URL.
	 */
	private function generate_qr_code_url( $email, $secret ) {
		$site_name = get_bloginfo( 'name' );
		$otpauth_url = 'otpauth://totp/' . rawurlencode( $site_name . ':' . $email ) . '?secret=' . $secret . '&issuer=' . rawurlencode( $site_name );
		
		return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . rawurlencode( $otpauth_url );
	}

	/**
	 * Helper methods for security checks.
	 */
	
	private function check_wordpress_version() {
		global $wp_version;
		$latest_version = get_transient( 'slbp_latest_wp_version' );
		
		if ( false === $latest_version ) {
			$response = wp_remote_get( 'https://api.wordpress.org/core/version-check/1.7/' );
			if ( ! is_wp_error( $response ) ) {
				$body = wp_remote_retrieve_body( $response );
				$data = json_decode( $body, true );
				$latest_version = $data['offers'][0]['version'] ?? $wp_version;
				set_transient( 'slbp_latest_wp_version', $latest_version, DAY_IN_SECONDS );
			} else {
				$latest_version = $wp_version;
			}
		}

		return array(
			'current' => $wp_version,
			'latest' => $latest_version,
			'is_outdated' => version_compare( $wp_version, $latest_version, '<' ),
		);
	}

	private function check_plugin_updates() {
		$plugins = get_plugins();
		$updates = get_site_transient( 'update_plugins' );
		$outdated_plugins = array();

		foreach ( $plugins as $plugin_file => $plugin_data ) {
			if ( isset( $updates->response[ $plugin_file ] ) ) {
				$outdated_plugins[] = array(
					'name' => $plugin_data['Name'],
					'current' => $plugin_data['Version'],
					'new' => $updates->response[ $plugin_file ]->new_version,
				);
			}
		}

		return $outdated_plugins;
	}

	private function check_ssl_configuration() {
		return array(
			'is_ssl' => is_ssl(),
			'force_ssl_admin' => defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN,
		);
	}

	private function check_file_permissions() {
		$files_to_check = array(
			ABSPATH . 'wp-config.php',
			ABSPATH . '.htaccess',
		);

		$permission_issues = array();

		foreach ( $files_to_check as $file ) {
			if ( file_exists( $file ) ) {
				$perms = fileperms( $file );
				$octal_perms = substr( sprintf( '%o', $perms ), -4 );
				
				if ( '0644' !== $octal_perms && '0600' !== $octal_perms ) {
					$permission_issues[] = array(
						'file' => basename( $file ),
						'permissions' => $octal_perms,
						'recommended' => '0644',
					);
				}
			}
		}

		return $permission_issues;
	}

	private function check_user_security() {
		$users = get_users( array( 'role' => 'administrator' ) );
		$security_issues = array();

		foreach ( $users as $user ) {
			$issues = array();

			// Check for weak passwords (basic check)
			if ( strlen( $user->user_pass ) < 8 ) {
				$issues[] = 'weak_password';
			}

			// Check if 2FA is enabled
			if ( ! get_user_meta( $user->ID, 'slbp_2fa_enabled', true ) ) {
				$issues[] = 'no_2fa';
			}

			if ( ! empty( $issues ) ) {
				$security_issues[] = array(
					'user' => $user->user_login,
					'issues' => $issues,
				);
			}
		}

		return $security_issues;
	}

	private function check_database_security() {
		global $wpdb;

		$issues = array();

		// Check for default table prefix
		if ( 'wp_' === $wpdb->prefix ) {
			$issues[] = 'default_table_prefix';
		}

		// Check for database version
		$db_version = $wpdb->get_var( 'SELECT VERSION()' );
		$issues[] = array(
			'database_version' => $db_version,
		);

		return $issues;
	}

	private function check_login_security() {
		$recent_failures = $this->audit_logger->get_logs( array(
			'action' => 'login_failed',
			'start_date' => date( 'Y-m-d H:i:s', strtotime( '-24 hours' ) ),
			'limit' => 0,
		) );

		return array(
			'failed_attempts_24h' => $recent_failures['total'],
			'unique_ips' => count( array_unique( wp_list_pluck( $recent_failures['logs'], 'user_ip' ) ) ),
		);
	}

	private function calculate_security_score( $audit_results ) {
		$score = 100;

		// Deduct points for issues
		if ( $audit_results['wordpress_version']['is_outdated'] ) {
			$score -= 10;
		}

		if ( ! empty( $audit_results['plugin_updates'] ) ) {
			$score -= 5;
		}

		if ( ! $audit_results['ssl_config']['is_ssl'] ) {
			$score -= 15;
		}

		if ( ! empty( $audit_results['file_permissions'] ) ) {
			$score -= 10;
		}

		if ( ! empty( $audit_results['user_security'] ) ) {
			$score -= 20;
		}

		if ( $audit_results['login_security']['failed_attempts_24h'] > 50 ) {
			$score -= 5;
		}

		return max( 0, $score );
	}

	// Utility methods
	
	private function is_2fa_required_for_user( $user ) {
		if ( ! $user || is_wp_error( $user ) ) {
			return false;
		}

		// Check if 2FA is enabled for this user
		$user_2fa_enabled = get_user_meta( $user->ID, 'slbp_2fa_enabled', true );
		
		// Check global settings for mandatory 2FA
		$mandatory_for_admins = $this->settings['mandatory_2fa_for_admins'] ?? false;
		
		return $user_2fa_enabled || ( $mandatory_for_admins && user_can( $user, 'manage_options' ) );
	}

	private function is_2fa_verified_for_session( $user_id ) {
		$session_token = wp_get_session_token();
		$verified_sessions = get_user_meta( $user_id, 'slbp_2fa_verified_sessions', true );
		
		if ( ! is_array( $verified_sessions ) ) {
			$verified_sessions = array();
		}

		return in_array( $session_token, $verified_sessions, true );
	}

	private function mark_2fa_verified_for_session( $user_id ) {
		$session_token = wp_get_session_token();
		$verified_sessions = get_user_meta( $user_id, 'slbp_2fa_verified_sessions', true );
		
		if ( ! is_array( $verified_sessions ) ) {
			$verified_sessions = array();
		}

		$verified_sessions[] = $session_token;
		update_user_meta( $user_id, 'slbp_2fa_verified_sessions', $verified_sessions );
	}

	private function get_user_ip() {
		$ip_keys = array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );

		foreach ( $ip_keys as $key ) {
			if ( array_key_exists( $key, $_SERVER ) === true ) {
				$ip = $_SERVER[ $key ];
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = explode( ',', $ip )[0];
				}
				$ip = trim( $ip );
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) !== false ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}

	private function base32_encode( $data ) {
		$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
		$encoded = '';
		$bits = '';

		for ( $i = 0; $i < strlen( $data ); $i++ ) {
			$bits .= str_pad( decbin( ord( $data[ $i ] ) ), 8, '0', STR_PAD_LEFT );
		}

		for ( $i = 0; $i < strlen( $bits ); $i += 5 ) {
			$chunk = substr( $bits, $i, 5 );
			$chunk = str_pad( $chunk, 5, '0', STR_PAD_RIGHT );
			$encoded .= $alphabet[ bindec( $chunk ) ];
		}

		return $encoded;
	}

	private function base32_decode( $data ) {
		$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
		$bits = '';

		for ( $i = 0; $i < strlen( $data ); $i++ ) {
			$bits .= str_pad( decbin( strpos( $alphabet, $data[ $i ] ) ), 5, '0', STR_PAD_LEFT );
		}

		$decoded = '';
		for ( $i = 0; $i < strlen( $bits ); $i += 8 ) {
			$chunk = substr( $bits, $i, 8 );
			if ( strlen( $chunk ) === 8 ) {
				$decoded .= chr( bindec( $chunk ) );
			}
		}

		return $decoded;
	}

	/**
	 * Run daily security audit.
	 *
	 * @since    1.0.0
	 */
	public function run_daily_security_audit() {
		$audit_results = $this->run_security_audit();

		// Send email notification if security score is below threshold
		$threshold = $this->settings['security_score_threshold'] ?? 70;
		
		if ( $audit_results['security_score'] < $threshold ) {
			$this->send_security_alert_email( $audit_results );
		}
	}

	/**
	 * Send security alert email.
	 *
	 * @since    1.0.0
	 * @param    array    $audit_results    Security audit results.
	 */
	private function send_security_alert_email( $audit_results ) {
		$admin_email = get_option( 'admin_email' );
		$site_name = get_bloginfo( 'name' );

		$subject = sprintf( __( 'Security Alert: %s - Score %d/100', 'skylearn-billing-pro' ), $site_name, $audit_results['security_score'] );

		$message = sprintf( __( 'Security audit completed for %s with a score of %d/100.', 'skylearn-billing-pro' ), $site_name, $audit_results['security_score'] );
		$message .= "\n\n" . __( 'Please review the security recommendations in your WordPress admin dashboard.', 'skylearn-billing-pro' );

		wp_mail( $admin_email, $subject, $message );
	}
}