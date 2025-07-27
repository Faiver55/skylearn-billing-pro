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
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY subscription_id (subscription_id),
			KEY payment_gateway (payment_gateway),
			KEY status (status)
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

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		
		dbDelta( $sql_transactions );
		dbDelta( $sql_subscriptions );
		dbDelta( $sql_licenses );

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
	}
}