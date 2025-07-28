<?php
/**
 * Fired during plugin activation
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/core
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/core
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// Check minimum WordPress version
		if ( ! self::check_wordpress_version() ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die(
				esc_html__( 'SkyLearn Billing Pro requires WordPress 5.0 or higher.', 'skylearn-billing-pro' ),
				esc_html__( 'Plugin Activation Error', 'skylearn-billing-pro' ),
				array( 'back_link' => true )
			);
		}

		// Check minimum PHP version
		if ( ! self::check_php_version() ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die(
				esc_html__( 'SkyLearn Billing Pro requires PHP 7.4 or higher.', 'skylearn-billing-pro' ),
				esc_html__( 'Plugin Activation Error', 'skylearn-billing-pro' ),
				array( 'back_link' => true )
			);
		}

		// Create database tables
		self::create_tables();

		// Set default options
		self::set_default_options();

		// Initialize license system
		self::initialize_license();

		// Set activation timestamp
		update_option( 'slbp_activation_time', time() );

		// Set redirect to setup wizard
		set_transient( 'slbp_setup_wizard_redirect', true, 300 );

		// Clear any cached data
		self::clear_cache();

		// Schedule any cron jobs
		self::schedule_cron_jobs();

		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Check if WordPress version is compatible.
	 *
	 * @since    1.0.0
	 * @return   bool    True if compatible, false otherwise.
	 */
	private static function check_wordpress_version() {
		global $wp_version;
		return version_compare( $wp_version, '5.0', '>=' );
	}

	/**
	 * Check if PHP version is compatible.
	 *
	 * @since    1.0.0
	 * @return   bool    True if compatible, false otherwise.
	 */
	private static function check_php_version() {
		return version_compare( PHP_VERSION, '7.4', '>=' );
	}

	/**
	 * Create plugin database tables.
	 *
	 * @since    1.0.0
	 */
	private static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Table for billing transactions
		$table_transactions = $wpdb->prefix . 'slbp_transactions';
		$sql_transactions = "CREATE TABLE $table_transactions (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			order_id varchar(100) NOT NULL,
			transaction_id varchar(100) NOT NULL,
			payment_gateway varchar(50) NOT NULL,
			amount decimal(10,2) NOT NULL,
			currency varchar(3) NOT NULL,
			status varchar(20) NOT NULL,
			course_id bigint(20) DEFAULT NULL,
			metadata longtext DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY order_id (order_id),
			KEY transaction_id (transaction_id),
			KEY payment_gateway (payment_gateway),
			KEY status (status),
			KEY course_id (course_id)
		) $charset_collate;";

		// Table for subscription management
		$table_subscriptions = $wpdb->prefix . 'slbp_subscriptions';
		$sql_subscriptions = "CREATE TABLE $table_subscriptions (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			subscription_id varchar(100) NOT NULL,
			payment_gateway varchar(50) NOT NULL,
			plan_id varchar(100) NOT NULL,
			status varchar(20) NOT NULL,
			amount decimal(10,2) NOT NULL,
			currency varchar(3) NOT NULL,
			billing_cycle varchar(20) NOT NULL,
			next_billing_date datetime DEFAULT NULL,
			expires_at datetime DEFAULT NULL,
			expiry_notified tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY subscription_id (subscription_id),
			KEY payment_gateway (payment_gateway),
			KEY status (status),
			KEY expires_at (expires_at),
			KEY expiry_notified (expiry_notified)
		) $charset_collate;";

		// Table for license keys
		$table_licenses = $wpdb->prefix . 'slbp_licenses';
		$sql_licenses = "CREATE TABLE $table_licenses (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			license_key varchar(255) NOT NULL,
			email varchar(255) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'inactive',
			activations_count int(11) NOT NULL DEFAULT 0,
			max_activations int(11) NOT NULL DEFAULT 1,
			expires_at datetime DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY license_key (license_key),
			KEY email (email),
			KEY status (status)
		) $charset_collate;";

		// Table for enrollment logs
		$table_enrollment_logs = $wpdb->prefix . 'slbp_enrollment_logs';
		$sql_enrollment_logs = "CREATE TABLE $table_enrollment_logs (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			course_id bigint(20) NOT NULL,
			action varchar(50) NOT NULL,
			status varchar(20) NOT NULL,
			transaction_id varchar(100) DEFAULT NULL,
			lms varchar(50) NOT NULL,
			notes text DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY course_id (course_id),
			KEY action (action),
			KEY status (status),
			KEY transaction_id (transaction_id),
			KEY lms (lms),
			KEY created_at (created_at)
		) $charset_collate;";

		// Table for in-app notifications
		$table_notifications = $wpdb->prefix . 'slbp_notifications';
		$sql_notifications = "CREATE TABLE $table_notifications (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			type varchar(50) NOT NULL,
			title varchar(255) NOT NULL,
			message text NOT NULL,
			data longtext DEFAULT NULL,
			is_read tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY type (type),
			KEY is_read (is_read),
			KEY created_at (created_at)
		) $charset_collate;";

		// Table for API keys
		$table_api_keys = $wpdb->prefix . 'slbp_api_keys';
		$sql_api_keys = "CREATE TABLE $table_api_keys (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			name varchar(255) NOT NULL,
			api_key varchar(64) NOT NULL,
			permissions longtext NOT NULL,
			rate_limit int(11) NOT NULL DEFAULT 1000,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			last_used_at datetime DEFAULT NULL,
			expires_at datetime DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY api_key (api_key),
			KEY user_id (user_id),
			KEY is_active (is_active),
			KEY expires_at (expires_at)
		) $charset_collate;";

		// Table for webhooks
		$table_webhooks = $wpdb->prefix . 'slbp_webhooks';
		$sql_webhooks = "CREATE TABLE $table_webhooks (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			name varchar(255) NOT NULL,
			url varchar(500) NOT NULL,
			events longtext NOT NULL,
			secret varchar(255) NOT NULL,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			failed_attempts int(11) NOT NULL DEFAULT 0,
			last_success_at datetime DEFAULT NULL,
			last_failure_at datetime DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY is_active (is_active),
			KEY failed_attempts (failed_attempts)
		) $charset_collate;";

		// Table for webhook logs
		$table_webhook_logs = $wpdb->prefix . 'slbp_webhook_logs';
		$sql_webhook_logs = "CREATE TABLE $table_webhook_logs (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			webhook_id bigint(20) NOT NULL,
			event varchar(50) NOT NULL,
			payload longtext NOT NULL,
			response_code int(11) DEFAULT NULL,
			response_body text DEFAULT NULL,
			status varchar(20) NOT NULL,
			attempts int(11) NOT NULL DEFAULT 1,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY webhook_id (webhook_id),
			KEY event (event),
			KEY status (status),
			KEY created_at (created_at)
		) $charset_collate;";

		// Table for API request logs
		$table_api_logs = $wpdb->prefix . 'slbp_api_logs';
		$sql_api_logs = "CREATE TABLE $table_api_logs (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			api_key_id bigint(20) DEFAULT NULL,
			user_id bigint(20) DEFAULT NULL,
			endpoint varchar(255) NOT NULL,
			method varchar(10) NOT NULL,
			request_params longtext DEFAULT NULL,
			response_code int(11) NOT NULL,
			response_time float NOT NULL,
			ip_address varchar(45) NOT NULL,
			user_agent text DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY api_key_id (api_key_id),
			KEY user_id (user_id),
			KEY endpoint (endpoint),
			KEY method (method),
			KEY response_code (response_code),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		
		dbDelta( $sql_transactions );
		dbDelta( $sql_subscriptions );
		dbDelta( $sql_licenses );
		dbDelta( $sql_enrollment_logs );
		dbDelta( $sql_notifications );
		dbDelta( $sql_api_keys );
		dbDelta( $sql_webhooks );
		dbDelta( $sql_webhook_logs );
		dbDelta( $sql_api_logs );

		// Create Phase 8 tables
		self::create_phase_8_tables();

		// Phase 10 tables (Internationalization) - Removed in Phase 3 refactor

		// Create Phase 11 tables (Scalability, Performance, and Reliability)
		self::create_phase_11_tables();

		// Update database version
		update_option( 'slbp_db_version', SLBP_VERSION );
	}

	/**
	 * Set default plugin options.
	 *
	 * @since    1.0.0
	 */
	private static function set_default_options() {
		$default_options = array(
			'slbp_settings' => array(
				'payment_gateways' => array(
					'lemon_squeezy' => array(
						'enabled' => false,
						'api_key' => '',
						'store_id' => '',
						'test_mode' => true,
					),
				),
				'general' => array(
					'currency' => 'USD',
					'decimal_places' => 2,
					'thousand_separator' => ',',
					'decimal_separator' => '.',
					'debug_mode' => false,
				),
				'learndash' => array(
					'enabled' => true,
					'auto_enroll' => true,
					'access_duration' => 0, // 0 = lifetime
				),
				'email' => array(
					'purchase_confirmation' => true,
					'subscription_renewal' => true,
					'payment_failed' => true,
				),
			),
		);

		foreach ( $default_options as $option_name => $option_value ) {
			if ( ! get_option( $option_name ) ) {
				add_option( $option_name, $option_value );
			}
		}
	}

	/**
	 * Initialize license system.
	 *
	 * @since    1.0.0
	 */
	private static function initialize_license() {
		// Set default license status
		if ( ! get_option( 'slbp_license_key' ) ) {
			add_option( 'slbp_license_key', '' );
		}

		if ( ! get_option( 'slbp_license_status' ) ) {
			add_option( 'slbp_license_status', 'inactive' );
		}

		if ( ! get_option( 'slbp_license_expires' ) ) {
			add_option( 'slbp_license_expires', '' );
		}
	}

	/**
	 * Clear any cached data.
	 *
	 * @since    1.0.0
	 */
	private static function clear_cache() {
		// Clear any plugin-specific cache
		wp_cache_delete( 'slbp_settings', 'options' );
		
		// Clear object cache if available
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}
	}

	/**
	 * Schedule cron jobs.
	 *
	 * @since    1.0.0
	 */
	private static function schedule_cron_jobs() {
		// Schedule license check
		if ( ! wp_next_scheduled( 'slbp_license_check' ) ) {
			wp_schedule_event( time(), 'daily', 'slbp_license_check' );
		}

		// Schedule subscription status check
		if ( ! wp_next_scheduled( 'slbp_subscription_check' ) ) {
			wp_schedule_event( time(), 'hourly', 'slbp_subscription_check' );
		}

		// Schedule cleanup of old transaction logs
		if ( ! wp_next_scheduled( 'slbp_cleanup_logs' ) ) {
			wp_schedule_event( time(), 'weekly', 'slbp_cleanup_logs' );
		}

		// Schedule daily notification checks
		if ( ! wp_next_scheduled( 'slbp_daily_cron' ) ) {
			wp_schedule_event( time(), 'daily', 'slbp_daily_cron' );
		}

		// Schedule Phase 8 cron jobs
		self::schedule_phase_8_cron_jobs();
	}

	/**
	 * Create Phase 8 database tables.
	 *
	 * @since    1.0.0
	 */
	private static function create_phase_8_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	}

	/**
	 * Schedule Phase 8 cron jobs.
	 *
	 * @since    1.0.0
	 */
	private static function schedule_phase_8_cron_jobs() {
		// Schedule external analytics sync
		if ( ! wp_next_scheduled( 'slbp_external_analytics_sync' ) ) {
			wp_schedule_event( time(), 'daily', 'slbp_external_analytics_sync' );
		}
	}

	/**
	 * Create Phase 11 database tables for scalability, performance, and reliability.
	 *
	 * @since    1.0.0
	 */
	private static function create_phase_11_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Background tasks table
		$table_background_tasks = $wpdb->prefix . 'slbp_background_tasks';
		$sql_background_tasks = "CREATE TABLE $table_background_tasks (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			task_type varchar(50) NOT NULL,
			task_data longtext NOT NULL,
			priority int(11) NOT NULL DEFAULT 10,
			status varchar(20) NOT NULL DEFAULT 'pending',
			retry_count int(11) NOT NULL DEFAULT 0,
			scheduled_at datetime NOT NULL,
			started_at datetime DEFAULT NULL,
			completed_at datetime DEFAULT NULL,
			error_message text DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY task_type (task_type),
			KEY status (status),
			KEY priority (priority),
			KEY scheduled_at (scheduled_at),
			KEY created_at (created_at)
		) $charset_collate;";

		// Sessions table for stateless scaling
		$table_sessions = $wpdb->prefix . 'slbp_sessions';
		$sql_sessions = "CREATE TABLE $table_sessions (
			session_key varchar(255) NOT NULL,
			session_data longtext NOT NULL,
			expires_at datetime NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (session_key),
			KEY expires_at (expires_at)
		) $charset_collate;";

		// Metrics table for monitoring
		$table_metrics = $wpdb->prefix . 'slbp_metrics';
		$sql_metrics = "CREATE TABLE $table_metrics (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			category varchar(50) NOT NULL,
			metric_name varchar(100) NOT NULL,
			metric_value float NOT NULL,
			metric_unit varchar(20) NOT NULL,
			collected_at datetime NOT NULL,
			PRIMARY KEY (id),
			KEY category (category),
			KEY metric_name (metric_name),
			KEY collected_at (collected_at),
			KEY category_metric (category, metric_name)
		) $charset_collate;";

		// Alerts table for monitoring
		$table_alerts = $wpdb->prefix . 'slbp_alerts';
		$sql_alerts = "CREATE TABLE $table_alerts (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			alert_name varchar(100) NOT NULL,
			metric_name varchar(100) NOT NULL,
			threshold_value float NOT NULL,
			current_value float NOT NULL,
			severity varchar(20) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			message text NOT NULL,
			triggered_at datetime NOT NULL,
			resolved_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			KEY alert_name (alert_name),
			KEY status (status),
			KEY severity (severity),
			KEY triggered_at (triggered_at)
		) $charset_collate;";

		// Rate limits table for throttling
		$table_rate_limits = $wpdb->prefix . 'slbp_rate_limits';
		$sql_rate_limits = "CREATE TABLE $table_rate_limits (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			client_id varchar(255) NOT NULL,
			endpoint varchar(255) NOT NULL,
			request_time datetime NOT NULL,
			ip_address varchar(45) NOT NULL,
			user_agent text DEFAULT NULL,
			PRIMARY KEY (id),
			KEY client_endpoint_time (client_id, endpoint, request_time),
			KEY request_time (request_time),
			KEY ip_address (ip_address)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		
		dbDelta( $sql_background_tasks );
		dbDelta( $sql_sessions );
		dbDelta( $sql_metrics );
		dbDelta( $sql_alerts );
		dbDelta( $sql_rate_limits );
	}
}