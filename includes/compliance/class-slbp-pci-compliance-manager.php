<?php
/**
 * The PCI-DSS compliance functionality of the plugin.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/compliance
 */

/**
 * The PCI-DSS compliance functionality of the plugin.
 *
 * Provides PCI-DSS compliance features for payment processing,
 * secure data handling, and payment gateway security.
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/compliance
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_PCI_Compliance_Manager {

	/**
	 * The audit logger instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SLBP_Audit_Logger    $audit_logger    The audit logger instance.
	 */
	private $audit_logger;

	/**
	 * PCI compliance settings.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $settings    PCI compliance settings.
	 */
	private $settings;

	/**
	 * Encryption key for sensitive data.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $encryption_key    Encryption key.
	 */
	private $encryption_key;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->audit_logger = new SLBP_Audit_Logger();
		$this->settings = get_option( 'slbp_pci_settings', $this->get_default_settings() );
		$this->encryption_key = $this->get_encryption_key();
		$this->init_hooks();
	}

	/**
	 * Initialize PCI compliance hooks.
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		// Payment processing hooks
		add_filter( 'slbp_process_payment_data', array( $this, 'secure_payment_data' ), 10, 2 );
		add_action( 'slbp_payment_completed', array( $this, 'log_payment_compliance' ), 10, 2 );
		add_action( 'slbp_payment_failed', array( $this, 'log_payment_compliance' ), 10, 2 );

		// Data security hooks
		add_filter( 'slbp_store_sensitive_data', array( $this, 'encrypt_sensitive_data' ), 10, 1 );
		add_filter( 'slbp_retrieve_sensitive_data', array( $this, 'decrypt_sensitive_data' ), 10, 1 );

		// Admin hooks
		add_action( 'admin_menu', array( $this, 'add_pci_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_pci_settings' ) );

		// AJAX handlers
		add_action( 'wp_ajax_slbp_run_pci_assessment', array( $this, 'ajax_run_pci_assessment' ) );
		add_action( 'wp_ajax_slbp_generate_compliance_report', array( $this, 'ajax_generate_compliance_report' ) );

		// Security headers and configuration
		add_action( 'init', array( $this, 'apply_security_headers' ) );
		add_action( 'wp_loaded', array( $this, 'enforce_ssl_requirements' ) );

		// Scheduled compliance checks
		add_action( 'slbp_daily_pci_check', array( $this, 'run_daily_compliance_check' ) );
	}

	/**
	 * Get default PCI compliance settings.
	 *
	 * @since    1.0.0
	 * @return   array    Default PCI settings.
	 */
	private function get_default_settings() {
		return array(
			'pci_compliance_level' => 'level_4', // Most common for small merchants
			'encrypt_card_data' => true,
			'mask_sensitive_data' => true,
			'secure_payment_forms' => true,
			'audit_payment_access' => true,
			'require_ssl_payments' => true,
			'tokenize_card_data' => true,
			'vulnerability_scanning' => false,
			'penetration_testing' => false,
			'compliance_monitoring' => true,
			'data_retention_policy' => array(
				'payment_data' => 365, // days
				'card_data' => 0, // immediate deletion after processing
				'transaction_logs' => 2555, // 7 years
			),
			'access_controls' => array(
				'require_2fa_for_payments' => true,
				'limit_payment_admin_access' => true,
				'log_all_payment_access' => true,
			),
		);
	}

	/**
	 * Apply security headers for PCI compliance.
	 *
	 * @since    1.0.0
	 */
	public function apply_security_headers() {
		// Only apply on payment pages or admin
		if ( ! $this->is_payment_context() && ! is_admin() ) {
			return;
		}

		// Security headers for PCI compliance
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Frame-Options: DENY' );
		header( 'X-XSS-Protection: 1; mode=block' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		
		// Content Security Policy for payment forms
		if ( $this->is_payment_context() ) {
			$csp = "default-src 'self'; script-src 'self' 'unsafe-inline' https://js.lemonsqueezy.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; connect-src 'self' https://api.lemonsqueezy.com;";
			header( 'Content-Security-Policy: ' . $csp );
		}

		// HSTS header for SSL enforcement
		if ( is_ssl() ) {
			header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains; preload' );
		}
	}

	/**
	 * Enforce SSL requirements for payment processing.
	 *
	 * @since    1.0.0
	 */
	public function enforce_ssl_requirements() {
		if ( ! $this->settings['require_ssl_payments'] ) {
			return;
		}

		// Redirect to HTTPS for payment pages
		if ( $this->is_payment_context() && ! is_ssl() ) {
			$redirect_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			wp_redirect( $redirect_url, 301 );
			exit;
		}
	}

	/**
	 * Secure payment data processing.
	 *
	 * @since    1.0.0
	 * @param    array    $payment_data    Payment data.
	 * @param    string   $context        Processing context.
	 * @return   array                    Secured payment data.
	 */
	public function secure_payment_data( $payment_data, $context = 'processing' ) {
		// Validate that we're in a secure context
		if ( ! is_ssl() && $this->settings['require_ssl_payments'] ) {
			throw new Exception( __( 'SSL is required for payment processing', 'skylearn-billing-pro' ) );
		}

		// Mask sensitive data for logging
		if ( 'logging' === $context ) {
			$payment_data = $this->mask_payment_data( $payment_data );
		}

		// Encrypt sensitive data for storage
		if ( 'storage' === $context ) {
			$payment_data = $this->encrypt_payment_data( $payment_data );
		}

		// Log the data access
		$this->audit_logger->log_event(
			'pci',
			'payment_data_accessed',
			get_current_user_id(),
			array(
				'context' => $context,
				'has_sensitive_data' => $this->has_sensitive_payment_data( $payment_data ),
			),
			'info'
		);

		return $payment_data;
	}

	/**
	 * Encrypt sensitive data.
	 *
	 * @since    1.0.0
	 * @param    mixed    $data    Data to encrypt.
	 * @return   string           Encrypted data.
	 */
	public function encrypt_sensitive_data( $data ) {
		if ( ! $this->settings['encrypt_card_data'] ) {
			return $data;
		}

		$serialized_data = serialize( $data );
		$iv = openssl_random_pseudo_bytes( 16 );
		$encrypted = openssl_encrypt( $serialized_data, 'AES-256-CBC', $this->encryption_key, 0, $iv );
		
		return base64_encode( $iv . $encrypted );
	}

	/**
	 * Decrypt sensitive data.
	 *
	 * @since    1.0.0
	 * @param    string    $encrypted_data    Encrypted data.
	 * @return   mixed                       Decrypted data.
	 */
	public function decrypt_sensitive_data( $encrypted_data ) {
		if ( ! $this->settings['encrypt_card_data'] ) {
			return $encrypted_data;
		}

		$data = base64_decode( $encrypted_data );
		$iv = substr( $data, 0, 16 );
		$encrypted = substr( $data, 16 );
		
		$decrypted = openssl_decrypt( $encrypted, 'AES-256-CBC', $this->encryption_key, 0, $iv );
		
		return unserialize( $decrypted );
	}

	/**
	 * Mask payment data for secure logging.
	 *
	 * @since    1.0.0
	 * @param    array    $payment_data    Payment data.
	 * @return   array                    Masked payment data.
	 */
	private function mask_payment_data( $payment_data ) {
		$masked_data = $payment_data;

		// Mask credit card numbers
		if ( isset( $masked_data['card_number'] ) ) {
			$masked_data['card_number'] = $this->mask_card_number( $masked_data['card_number'] );
		}

		// Mask CVV
		if ( isset( $masked_data['cvv'] ) ) {
			$masked_data['cvv'] = '***';
		}

		// Mask banking information
		if ( isset( $masked_data['account_number'] ) ) {
			$masked_data['account_number'] = $this->mask_account_number( $masked_data['account_number'] );
		}

		// Mask SSN or similar
		if ( isset( $masked_data['ssn'] ) ) {
			$masked_data['ssn'] = 'XXX-XX-' . substr( $masked_data['ssn'], -4 );
		}

		return $masked_data;
	}

	/**
	 * Mask credit card number for display.
	 *
	 * @since    1.0.0
	 * @param    string    $card_number    Credit card number.
	 * @return   string                   Masked card number.
	 */
	private function mask_card_number( $card_number ) {
		$card_number = preg_replace( '/\D/', '', $card_number );
		$length = strlen( $card_number );
		
		if ( $length < 4 ) {
			return str_repeat( '*', $length );
		}

		return str_repeat( '*', $length - 4 ) . substr( $card_number, -4 );
	}

	/**
	 * Mask account number for display.
	 *
	 * @since    1.0.0
	 * @param    string    $account_number    Account number.
	 * @return   string                      Masked account number.
	 */
	private function mask_account_number( $account_number ) {
		$account_number = preg_replace( '/\D/', '', $account_number );
		$length = strlen( $account_number );
		
		if ( $length < 4 ) {
			return str_repeat( '*', $length );
		}

		return str_repeat( '*', $length - 4 ) . substr( $account_number, -4 );
	}

	/**
	 * Check if payment data contains sensitive information.
	 *
	 * @since    1.0.0
	 * @param    array    $payment_data    Payment data.
	 * @return   bool                     Whether data contains sensitive info.
	 */
	private function has_sensitive_payment_data( $payment_data ) {
		$sensitive_fields = array( 'card_number', 'cvv', 'account_number', 'ssn', 'routing_number' );
		
		foreach ( $sensitive_fields as $field ) {
			if ( ! empty( $payment_data[$field] ) ) {
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Encrypt payment data for storage.
	 *
	 * @since    1.0.0
	 * @param    array    $payment_data    Payment data.
	 * @return   array                    Encrypted payment data.
	 */
	private function encrypt_payment_data( $payment_data ) {
		$sensitive_fields = array( 'card_number', 'cvv', 'account_number', 'routing_number' );
		
		foreach ( $sensitive_fields as $field ) {
			if ( ! empty( $payment_data[$field] ) ) {
				$payment_data[$field] = $this->encrypt_sensitive_data( $payment_data[$field] );
			}
		}
		
		return $payment_data;
	}

	/**
	 * Run PCI compliance assessment.
	 *
	 * @since    1.0.0
	 * @return   array    Assessment results.
	 */
	public function run_pci_assessment() {
		$assessment = array(
			'score' => 0,
			'requirements' => array(),
			'recommendations' => array(),
		);

		// Requirement 1: Install and maintain a firewall configuration
		$assessment['requirements']['firewall'] = array(
			'title' => __( 'Firewall Configuration', 'skylearn-billing-pro' ),
			'status' => $this->check_firewall_configuration(),
			'description' => __( 'Firewall and network security configuration', 'skylearn-billing-pro' ),
		);

		// Requirement 2: Do not use vendor-supplied defaults
		$assessment['requirements']['defaults'] = array(
			'title' => __( 'Default Passwords and Settings', 'skylearn-billing-pro' ),
			'status' => $this->check_default_settings(),
			'description' => __( 'Check for default passwords and configurations', 'skylearn-billing-pro' ),
		);

		// Requirement 3: Protect stored cardholder data
		$assessment['requirements']['data_protection'] = array(
			'title' => __( 'Cardholder Data Protection', 'skylearn-billing-pro' ),
			'status' => $this->check_data_protection(),
			'description' => __( 'Encryption and protection of stored payment data', 'skylearn-billing-pro' ),
		);

		// Requirement 4: Encrypt transmission of cardholder data
		$assessment['requirements']['transmission_encryption'] = array(
			'title' => __( 'Data Transmission Encryption', 'skylearn-billing-pro' ),
			'status' => $this->check_transmission_encryption(),
			'description' => __( 'SSL/TLS encryption for data transmission', 'skylearn-billing-pro' ),
		);

		// Requirement 6: Develop and maintain secure systems
		$assessment['requirements']['secure_systems'] = array(
			'title' => __( 'Secure Systems and Applications', 'skylearn-billing-pro' ),
			'status' => $this->check_secure_systems(),
			'description' => __( 'Security patches and secure development practices', 'skylearn-billing-pro' ),
		);

		// Requirement 7: Restrict access by business need-to-know
		$assessment['requirements']['access_control'] = array(
			'title' => __( 'Access Control', 'skylearn-billing-pro' ),
			'status' => $this->check_access_control(),
			'description' => __( 'Role-based access control implementation', 'skylearn-billing-pro' ),
		);

		// Requirement 8: Identify and authenticate access
		$assessment['requirements']['authentication'] = array(
			'title' => __( 'User Authentication', 'skylearn-billing-pro' ),
			'status' => $this->check_authentication(),
			'description' => __( 'Strong authentication and user identification', 'skylearn-billing-pro' ),
		);

		// Requirement 9: Restrict physical access
		$assessment['requirements']['physical_access'] = array(
			'title' => __( 'Physical Access Restrictions', 'skylearn-billing-pro' ),
			'status' => 'manual_review',
			'description' => __( 'Physical access controls (requires manual review)', 'skylearn-billing-pro' ),
		);

		// Requirement 10: Track and monitor network access
		$assessment['requirements']['monitoring'] = array(
			'title' => __( 'Network Access Monitoring', 'skylearn-billing-pro' ),
			'status' => $this->check_monitoring(),
			'description' => __( 'Audit logging and monitoring systems', 'skylearn-billing-pro' ),
		);

		// Requirement 11: Regularly test security systems
		$assessment['requirements']['security_testing'] = array(
			'title' => __( 'Security Testing', 'skylearn-billing-pro' ),
			'status' => $this->check_security_testing(),
			'description' => __( 'Vulnerability scans and penetration testing', 'skylearn-billing-pro' ),
		);

		// Requirement 12: Maintain a policy that addresses information security
		$assessment['requirements']['security_policy'] = array(
			'title' => __( 'Security Policy', 'skylearn-billing-pro' ),
			'status' => $this->check_security_policy(),
			'description' => __( 'Information security policy and procedures', 'skylearn-billing-pro' ),
		);

		// Calculate overall score
		$total_requirements = count( $assessment['requirements'] );
		$passed_requirements = 0;
		
		foreach ( $assessment['requirements'] as $requirement ) {
			if ( 'pass' === $requirement['status'] ) {
				$passed_requirements++;
			}
		}
		
		$assessment['score'] = round( ( $passed_requirements / $total_requirements ) * 100 );

		// Generate recommendations based on failed requirements
		$assessment['recommendations'] = $this->generate_compliance_recommendations( $assessment['requirements'] );

		// Log the assessment
		$this->audit_logger->log_event(
			'pci',
			'compliance_assessment_completed',
			get_current_user_id(),
			array(
				'score' => $assessment['score'],
				'requirements_checked' => $total_requirements,
				'requirements_passed' => $passed_requirements,
			),
			'info'
		);

		return $assessment;
	}

	/**
	 * Check firewall configuration.
	 *
	 * @since    1.0.0
	 * @return   string    Status (pass/fail/manual_review).
	 */
	private function check_firewall_configuration() {
		// This would typically require external tools or server configuration checks
		// For now, we'll check basic WordPress security measures
		
		$checks = array();
		
		// Check if XML-RPC is disabled
		$checks['xmlrpc'] = ! apply_filters( 'xmlrpc_enabled', true );
		
		// Check if file editing is disabled
		$checks['file_editing'] = defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT;
		
		// Check if debug is disabled in production
		$checks['debug'] = ! ( defined( 'WP_DEBUG' ) && WP_DEBUG );
		
		$passed_checks = array_filter( $checks );
		
		return count( $passed_checks ) >= 2 ? 'pass' : 'fail';
	}

	/**
	 * Check for default settings and passwords.
	 *
	 * @since    1.0.0
	 * @return   string    Status (pass/fail/manual_review).
	 */
	private function check_default_settings() {
		$checks = array();
		
		// Check database table prefix
		global $wpdb;
		$checks['table_prefix'] = 'wp_' !== $wpdb->prefix;
		
		// Check for admin username
		$admin_user = get_user_by( 'login', 'admin' );
		$checks['admin_username'] = ! $admin_user;
		
		// Check security keys
		$checks['security_keys'] = defined( 'AUTH_KEY' ) && 'put your unique phrase here' !== AUTH_KEY;
		
		$passed_checks = array_filter( $checks );
		
		return count( $passed_checks ) >= 2 ? 'pass' : 'fail';
	}

	/**
	 * Check data protection measures.
	 *
	 * @since    1.0.0
	 * @return   string    Status (pass/fail/manual_review).
	 */
	private function check_data_protection() {
		$checks = array();
		
		// Check encryption settings
		$checks['encryption_enabled'] = $this->settings['encrypt_card_data'];
		
		// Check data masking
		$checks['data_masking'] = $this->settings['mask_sensitive_data'];
		
		// Check tokenization
		$checks['tokenization'] = $this->settings['tokenize_card_data'];
		
		$passed_checks = array_filter( $checks );
		
		return count( $passed_checks ) >= 2 ? 'pass' : 'fail';
	}

	/**
	 * Check transmission encryption.
	 *
	 * @since    1.0.0
	 * @return   string    Status (pass/fail/manual_review).
	 */
	private function check_transmission_encryption() {
		$checks = array();
		
		// Check SSL requirement
		$checks['ssl_required'] = $this->settings['require_ssl_payments'];
		
		// Check if site is actually using SSL
		$checks['ssl_active'] = is_ssl();
		
		// Check force SSL admin
		$checks['force_ssl_admin'] = defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN;
		
		$passed_checks = array_filter( $checks );
		
		return count( $passed_checks ) >= 2 ? 'pass' : 'fail';
	}

	/**
	 * Check secure systems and applications.
	 *
	 * @since    1.0.0
	 * @return   string    Status (pass/fail/manual_review).
	 */
	private function check_secure_systems() {
		$checks = array();
		
		// Check WordPress version
		global $wp_version;
		$latest_version = get_transient( 'slbp_latest_wp_version' );
		$checks['wp_updated'] = version_compare( $wp_version, $latest_version, '>=' );
		
		// Check plugin versions
		$updates = get_site_transient( 'update_plugins' );
		$checks['plugins_updated'] = empty( $updates->response );
		
		// Check secure payment forms
		$checks['secure_forms'] = $this->settings['secure_payment_forms'];
		
		$passed_checks = array_filter( $checks );
		
		return count( $passed_checks ) >= 2 ? 'pass' : 'fail';
	}

	/**
	 * Check access control implementation.
	 *
	 * @since    1.0.0
	 * @return   string    Status (pass/fail/manual_review).
	 */
	private function check_access_control() {
		$checks = array();
		
		// Check role-based access
		$checks['role_based_access'] = $this->settings['access_controls']['limit_payment_admin_access'];
		
		// Check 2FA requirement
		$checks['2fa_required'] = $this->settings['access_controls']['require_2fa_for_payments'];
		
		// Check access logging
		$checks['access_logging'] = $this->settings['access_controls']['log_all_payment_access'];
		
		$passed_checks = array_filter( $checks );
		
		return count( $passed_checks ) >= 2 ? 'pass' : 'fail';
	}

	/**
	 * Check authentication systems.
	 *
	 * @since    1.0.0
	 * @return   string    Status (pass/fail/manual_review).
	 */
	private function check_authentication() {
		$checks = array();
		
		// Check password policies
		$checks['password_policy'] = $this->check_password_policies();
		
		// Check 2FA availability
		$checks['2fa_available'] = class_exists( 'SLBP_Security_Manager' );
		
		// Check session management
		$checks['session_management'] = $this->check_session_management();
		
		$passed_checks = array_filter( $checks );
		
		return count( $passed_checks ) >= 2 ? 'pass' : 'fail';
	}

	/**
	 * Check monitoring and logging.
	 *
	 * @since    1.0.0
	 * @return   string    Status (pass/fail/manual_review).
	 */
	private function check_monitoring() {
		$checks = array();
		
		// Check audit logging
		$checks['audit_logging'] = $this->settings['audit_payment_access'];
		
		// Check monitoring enabled
		$checks['monitoring_enabled'] = $this->settings['compliance_monitoring'];
		
		// Check log retention
		$checks['log_retention'] = ! empty( $this->settings['data_retention_policy']['transaction_logs'] );
		
		$passed_checks = array_filter( $checks );
		
		return count( $passed_checks ) >= 2 ? 'pass' : 'fail';
	}

	/**
	 * Check security testing measures.
	 *
	 * @since    1.0.0
	 * @return   string    Status (pass/fail/manual_review).
	 */
	private function check_security_testing() {
		$checks = array();
		
		// Check vulnerability scanning
		$checks['vulnerability_scanning'] = $this->settings['vulnerability_scanning'];
		
		// Check penetration testing
		$checks['penetration_testing'] = $this->settings['penetration_testing'];
		
		$passed_checks = array_filter( $checks );
		
		// If neither is enabled, it's a fail for PCI compliance
		return count( $passed_checks ) >= 1 ? 'pass' : 'fail';
	}

	/**
	 * Check security policy implementation.
	 *
	 * @since    1.0.0
	 * @return   string    Status (pass/fail/manual_review).
	 */
	private function check_security_policy() {
		// This typically requires manual review of documentation
		// We can check for basic policy settings
		
		$policy_page = get_option( 'wp_page_for_privacy_policy' );
		$has_privacy_policy = ! empty( $policy_page );
		
		$security_settings = get_option( 'slbp_security_settings', array() );
		$has_security_config = ! empty( $security_settings );
		
		return ( $has_privacy_policy && $has_security_config ) ? 'pass' : 'manual_review';
	}

	/**
	 * Generate compliance recommendations.
	 *
	 * @since    1.0.0
	 * @param    array    $requirements    Assessment requirements.
	 * @return   array                    Recommendations.
	 */
	private function generate_compliance_recommendations( $requirements ) {
		$recommendations = array();
		
		foreach ( $requirements as $key => $requirement ) {
			if ( 'fail' === $requirement['status'] ) {
				switch ( $key ) {
					case 'firewall':
						$recommendations[] = __( 'Configure firewall rules and disable unnecessary services like XML-RPC.', 'skylearn-billing-pro' );
						break;
					case 'defaults':
						$recommendations[] = __( 'Change default database prefix, remove admin user, and configure security keys.', 'skylearn-billing-pro' );
						break;
					case 'data_protection':
						$recommendations[] = __( 'Enable data encryption, masking, and tokenization for sensitive payment data.', 'skylearn-billing-pro' );
						break;
					case 'transmission_encryption':
						$recommendations[] = __( 'Implement SSL/TLS encryption for all payment transactions and admin access.', 'skylearn-billing-pro' );
						break;
					case 'secure_systems':
						$recommendations[] = __( 'Keep WordPress and plugins updated, implement secure coding practices.', 'skylearn-billing-pro' );
						break;
					case 'access_control':
						$recommendations[] = __( 'Implement role-based access control and limit payment system access.', 'skylearn-billing-pro' );
						break;
					case 'authentication':
						$recommendations[] = __( 'Enforce strong passwords and implement two-factor authentication.', 'skylearn-billing-pro' );
						break;
					case 'monitoring':
						$recommendations[] = __( 'Enable comprehensive audit logging and monitoring systems.', 'skylearn-billing-pro' );
						break;
					case 'security_testing':
						$recommendations[] = __( 'Conduct regular vulnerability scans and penetration testing.', 'skylearn-billing-pro' );
						break;
					case 'security_policy':
						$recommendations[] = __( 'Develop and maintain comprehensive security policies and procedures.', 'skylearn-billing-pro' );
						break;
				}
			}
		}
		
		return $recommendations;
	}

	/**
	 * Check password policies.
	 *
	 * @since    1.0.0
	 * @return   bool    Whether password policies are adequate.
	 */
	private function check_password_policies() {
		// Check if there are any password policy plugins or settings
		$security_settings = get_option( 'slbp_security_settings', array() );
		return ! empty( $security_settings['password_policy'] );
	}

	/**
	 * Check session management.
	 *
	 * @since    1.0.0
	 * @return   bool    Whether session management is secure.
	 */
	private function check_session_management() {
		// Check for secure session settings
		return ini_get( 'session.cookie_secure' ) && ini_get( 'session.cookie_httponly' );
	}

	/**
	 * Get or generate encryption key.
	 *
	 * @since    1.0.0
	 * @return   string    Encryption key.
	 */
	private function get_encryption_key() {
		$key = get_option( 'slbp_encryption_key' );
		
		if ( empty( $key ) ) {
			$key = wp_generate_password( 64, true, true );
			update_option( 'slbp_encryption_key', $key );
		}
		
		return $key;
	}

	/**
	 * Check if current context is payment-related.
	 *
	 * @since    1.0.0
	 * @return   bool    Whether in payment context.
	 */
	private function is_payment_context() {
		// Check for payment pages, checkout, billing, etc.
		global $post;
		
		if ( is_admin() ) {
			$screen = get_current_screen();
			return $screen && strpos( $screen->id, 'slbp' ) !== false;
		}
		
		if ( $post ) {
			$content = $post->post_content;
			$payment_shortcodes = array( 'slbp_checkout', 'slbp_payment', 'slbp_billing' );
			
			foreach ( $payment_shortcodes as $shortcode ) {
				if ( has_shortcode( $content, $shortcode ) ) {
					return true;
				}
			}
		}
		
		// Check query vars for payment contexts
		$payment_contexts = array( 'payment', 'checkout', 'billing', 'subscription' );
		foreach ( $payment_contexts as $context ) {
			if ( get_query_var( $context ) ) {
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Run daily compliance check.
	 *
	 * @since    1.0.0
	 */
	public function run_daily_compliance_check() {
		if ( ! $this->settings['compliance_monitoring'] ) {
			return;
		}

		$assessment = $this->run_pci_assessment();
		
		// Alert if compliance score is below threshold
		if ( $assessment['score'] < 80 ) {
			$this->send_compliance_alert( $assessment );
		}
	}

	/**
	 * Send compliance alert email.
	 *
	 * @since    1.0.0
	 * @param    array    $assessment    Compliance assessment results.
	 */
	private function send_compliance_alert( $assessment ) {
		$admin_email = get_option( 'admin_email' );
		$site_name = get_bloginfo( 'name' );

		$subject = sprintf( 
			__( 'PCI Compliance Alert: %s - Score %d/100', 'skylearn-billing-pro' ), 
			$site_name, 
			$assessment['score'] 
		);

		$message = sprintf( 
			__( 'PCI compliance check completed for %s with a score of %d/100.', 'skylearn-billing-pro' ), 
			$site_name, 
			$assessment['score'] 
		);
		
		$message .= "\n\n" . __( 'Please review the compliance recommendations in your admin dashboard.', 'skylearn-billing-pro' );

		wp_mail( $admin_email, $subject, $message );
	}

	/**
	 * Add PCI admin menu.
	 *
	 * @since    1.0.0
	 */
	public function add_pci_admin_menu() {
		add_submenu_page(
			'slbp-admin',
			__( 'PCI Compliance', 'skylearn-billing-pro' ),
			__( 'PCI Compliance', 'skylearn-billing-pro' ),
			'manage_options',
			'slbp-pci-compliance',
			array( $this, 'pci_admin_page' )
		);
	}

	/**
	 * PCI compliance admin page.
	 *
	 * @since    1.0.0
	 */
	public function pci_admin_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'PCI DSS Compliance', 'skylearn-billing-pro' ); ?></h1>
			
			<div class="slbp-pci-dashboard">
				<div class="slbp-compliance-overview">
					<h2><?php esc_html_e( 'Compliance Overview', 'skylearn-billing-pro' ); ?></h2>
					<button type="button" id="slbp-run-pci-assessment" class="button button-primary">
						<?php esc_html_e( 'Run PCI Assessment', 'skylearn-billing-pro' ); ?>
					</button>
					<button type="button" id="slbp-generate-compliance-report" class="button">
						<?php esc_html_e( 'Generate Compliance Report', 'skylearn-billing-pro' ); ?>
					</button>
				</div>
				
				<div id="slbp-assessment-results" style="display: none;">
					<!-- Assessment results will be loaded here via AJAX -->
				</div>
			</div>
			
			<form method="post" action="options.php">
				<?php
				settings_fields( 'slbp_pci_settings' );
				do_settings_sections( 'slbp_pci_settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register PCI settings.
	 *
	 * @since    1.0.0
	 */
	public function register_pci_settings() {
		register_setting( 'slbp_pci_settings', 'slbp_pci_settings', array( $this, 'sanitize_pci_settings' ) );

		add_settings_section(
			'slbp_pci_general',
			__( 'General PCI Settings', 'skylearn-billing-pro' ),
			array( $this, 'pci_general_section_callback' ),
			'slbp_pci_settings'
		);

		add_settings_field(
			'encrypt_card_data',
			__( 'Encrypt Card Data', 'skylearn-billing-pro' ),
			array( $this, 'checkbox_field_callback' ),
			'slbp_pci_settings',
			'slbp_pci_general',
			array( 'field' => 'encrypt_card_data' )
		);

		add_settings_field(
			'require_ssl_payments',
			__( 'Require SSL for Payments', 'skylearn-billing-pro' ),
			array( $this, 'checkbox_field_callback' ),
			'slbp_pci_settings',
			'slbp_pci_general',
			array( 'field' => 'require_ssl_payments' )
		);
	}

	/**
	 * AJAX handler for running PCI assessment.
	 *
	 * @since    1.0.0
	 */
	public function ajax_run_pci_assessment() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'slbp_pci_nonce' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Security check failed.' );
		}

		$assessment = $this->run_pci_assessment();
		wp_send_json_success( $assessment );
	}

	/**
	 * AJAX handler for generating compliance report.
	 *
	 * @since    1.0.0
	 */
	public function ajax_generate_compliance_report() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'slbp_pci_nonce' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Security check failed.' );
		}

		$assessment = $this->run_pci_assessment();
		
		// Generate PDF or detailed report
		$report_url = $this->generate_compliance_report_file( $assessment );
		
		wp_send_json_success( array( 'report_url' => $report_url ) );
	}

	/**
	 * Generate compliance report file.
	 *
	 * @since    1.0.0
	 * @param    array    $assessment    Assessment results.
	 * @return   string                 Report file URL.
	 */
	private function generate_compliance_report_file( $assessment ) {
		$upload_dir = wp_upload_dir();
		$filename = 'slbp-pci-compliance-report-' . date( 'Y-m-d-H-i-s' ) . '.html';
		$file_path = $upload_dir['basedir'] . '/slbp-reports/' . $filename;

		wp_mkdir_p( dirname( $file_path ) );

		ob_start();
		include SLBP_PLUGIN_PATH . 'templates/compliance-report.php';
		$report_content = ob_get_clean();

		file_put_contents( $file_path, $report_content );

		return $upload_dir['baseurl'] . '/slbp-reports/' . $filename;
	}

	/**
	 * Sanitize PCI settings.
	 *
	 * @since    1.0.0
	 * @param    array    $input    Raw input data.
	 * @return   array             Sanitized settings.
	 */
	public function sanitize_pci_settings( $input ) {
		$sanitized = array();

		$sanitized['encrypt_card_data'] = ! empty( $input['encrypt_card_data'] );
		$sanitized['require_ssl_payments'] = ! empty( $input['require_ssl_payments'] );
		$sanitized['mask_sensitive_data'] = ! empty( $input['mask_sensitive_data'] );

		return array_merge( $this->settings, $sanitized );
	}

	/**
	 * PCI general section callback.
	 *
	 * @since    1.0.0
	 */
	public function pci_general_section_callback() {
		echo '<p>' . esc_html__( 'Configure PCI DSS compliance settings for payment processing.', 'skylearn-billing-pro' ) . '</p>';
	}

	/**
	 * Checkbox field callback.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Field arguments.
	 */
	public function checkbox_field_callback( $args ) {
		$field = $args['field'];
		$value = $this->settings[$field] ?? false;
		?>
		<input type="checkbox" name="slbp_pci_settings[<?php echo esc_attr( $field ); ?>]" value="1" <?php checked( $value ); ?> />
		<?php
	}
}