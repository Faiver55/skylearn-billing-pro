<?php
/**
 * Fired during plugin deactivation
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/core
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/core
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Deactivator {

	/**
	 * Plugin deactivation handler.
	 *
	 * Performs cleanup operations when the plugin is deactivated.
	 * This includes clearing scheduled events, temporary data cleanup,
	 * and graceful shutdown procedures.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		// Clear scheduled cron jobs
		self::clear_scheduled_events();

		// Clean up temporary data
		self::cleanup_temporary_data();

		// Clear cache
		self::clear_cache();

		// Flush rewrite rules
		flush_rewrite_rules();

		// Log deactivation
		self::log_deactivation();

		// Perform graceful shutdown
		self::graceful_shutdown();
	}

	/**
	 * Clear all scheduled events related to the plugin.
	 *
	 * @since    1.0.0
	 */
	private static function clear_scheduled_events() {
		// Remove license check cron job
		$timestamp = wp_next_scheduled( 'slbp_license_check' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'slbp_license_check' );
		}

		// Remove subscription check cron job
		$timestamp = wp_next_scheduled( 'slbp_subscription_check' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'slbp_subscription_check' );
		}

		// Remove cleanup logs cron job
		$timestamp = wp_next_scheduled( 'slbp_cleanup_logs' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'slbp_cleanup_logs' );
		}

		// Clear all plugin-related cron jobs
		wp_clear_scheduled_hook( 'slbp_license_check' );
		wp_clear_scheduled_hook( 'slbp_subscription_check' );
		wp_clear_scheduled_hook( 'slbp_cleanup_logs' );
	}

	/**
	 * Clean up temporary data and transients.
	 *
	 * @since    1.0.0
	 */
	private static function cleanup_temporary_data() {
		global $wpdb;

		// Remove transients
		$transients = array(
			'slbp_payment_gateways_cache',
			'slbp_license_validation_cache',
			'slbp_subscription_status_cache',
			'slbp_course_pricing_cache',
		);

		foreach ( $transients as $transient ) {
			delete_transient( $transient );
			delete_site_transient( $transient );
		}

		// Clean up temporary options
		$temp_options = array(
			'slbp_temp_payment_data',
			'slbp_temp_subscription_data',
			'slbp_temp_webhook_data',
		);

		foreach ( $temp_options as $option ) {
			delete_option( $option );
			delete_site_option( $option );
		}

		// Clean up expired sessions or temporary user meta
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->usermeta} 
				WHERE meta_key LIKE %s 
				AND meta_value < %d",
				'slbp_temp_%',
				time() - DAY_IN_SECONDS
			)
		);
	}

	/**
	 * Clear plugin-related cache.
	 *
	 * @since    1.0.0
	 */
	private static function clear_cache() {
		// Clear WordPress object cache
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}

		// Clear plugin-specific cache groups
		$cache_groups = array(
			'slbp_settings',
			'slbp_transactions',
			'slbp_subscriptions',
			'slbp_courses',
			'slbp_licenses',
		);

		foreach ( $cache_groups as $group ) {
			wp_cache_delete( $group, 'slbp' );
		}

		// Clear any external caching plugins if methods exist
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		}

		if ( function_exists( 'wp_rocket_clean_domain' ) ) {
			wp_rocket_clean_domain();
		}

		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
	}

	/**
	 * Log the plugin deactivation.
	 *
	 * @since    1.0.0
	 */
	private static function log_deactivation() {
		// Update deactivation timestamp
		update_option( 'slbp_deactivation_time', time() );

		// Log deactivation reason if debug mode is enabled
		$settings = get_option( 'slbp_settings', array() );
		if ( isset( $settings['general']['debug_mode'] ) && $settings['general']['debug_mode'] ) {
			error_log( '[SkyLearn Billing Pro] Plugin deactivated at ' . current_time( 'mysql' ) );
		}
	}

	/**
	 * Perform graceful shutdown procedures.
	 *
	 * @since    1.0.0
	 */
	private static function graceful_shutdown() {
		// Cancel any ongoing webhook processing
		self::cancel_webhook_processing();

		// Close any open payment gateway connections
		self::close_gateway_connections();

		// Save any pending data
		self::save_pending_data();

		// Set plugin status to deactivated
		update_option( 'slbp_status', 'deactivated' );
	}

	/**
	 * Cancel any ongoing webhook processing.
	 *
	 * @since    1.0.0
	 */
	private static function cancel_webhook_processing() {
		// Remove any webhook processing flags
		delete_option( 'slbp_webhook_processing' );
		delete_transient( 'slbp_webhook_lock' );
	}

	/**
	 * Close any open payment gateway connections.
	 *
	 * @since    1.0.0
	 */
	private static function close_gateway_connections() {
		// Clear any stored API connections or tokens
		delete_transient( 'slbp_lemon_squeezy_token' );
		delete_transient( 'slbp_stripe_connection' );
		delete_transient( 'slbp_paypal_connection' );
	}

	/**
	 * Save any pending data before shutdown.
	 *
	 * @since    1.0.0
	 */
	private static function save_pending_data() {
		// Force save any pending transactions
		$pending_transactions = get_transient( 'slbp_pending_transactions' );
		if ( $pending_transactions && is_array( $pending_transactions ) ) {
			foreach ( $pending_transactions as $transaction ) {
				// Save transaction to database
				self::save_transaction( $transaction );
			}
			delete_transient( 'slbp_pending_transactions' );
		}

		// Save any pending subscription updates
		$pending_subscriptions = get_transient( 'slbp_pending_subscriptions' );
		if ( $pending_subscriptions && is_array( $pending_subscriptions ) ) {
			foreach ( $pending_subscriptions as $subscription ) {
				// Save subscription to database
				self::save_subscription( $subscription );
			}
			delete_transient( 'slbp_pending_subscriptions' );
		}
	}

	/**
	 * Save a transaction to the database.
	 *
	 * @since    1.0.0
	 * @param    array    $transaction    Transaction data to save.
	 */
	private static function save_transaction( $transaction ) {
		global $wpdb;

		if ( ! is_array( $transaction ) || empty( $transaction ) ) {
			return;
		}

		$table_name = $wpdb->prefix . 'slbp_transactions';
		
		$wpdb->insert(
			$table_name,
			array(
				'user_id'        => isset( $transaction['user_id'] ) ? intval( $transaction['user_id'] ) : 0,
				'order_id'       => isset( $transaction['order_id'] ) ? sanitize_text_field( $transaction['order_id'] ) : '',
				'transaction_id' => isset( $transaction['transaction_id'] ) ? sanitize_text_field( $transaction['transaction_id'] ) : '',
				'payment_gateway' => isset( $transaction['payment_gateway'] ) ? sanitize_text_field( $transaction['payment_gateway'] ) : '',
				'amount'         => isset( $transaction['amount'] ) ? floatval( $transaction['amount'] ) : 0,
				'currency'       => isset( $transaction['currency'] ) ? sanitize_text_field( $transaction['currency'] ) : 'USD',
				'status'         => isset( $transaction['status'] ) ? sanitize_text_field( $transaction['status'] ) : 'pending',
				'course_id'      => isset( $transaction['course_id'] ) ? intval( $transaction['course_id'] ) : null,
				'metadata'       => isset( $transaction['metadata'] ) ? wp_json_encode( $transaction['metadata'] ) : null,
			),
			array( '%d', '%s', '%s', '%s', '%f', '%s', '%s', '%d', '%s' )
		);
	}

	/**
	 * Save a subscription to the database.
	 *
	 * @since    1.0.0
	 * @param    array    $subscription    Subscription data to save.
	 */
	private static function save_subscription( $subscription ) {
		global $wpdb;

		if ( ! is_array( $subscription ) || empty( $subscription ) ) {
			return;
		}

		$table_name = $wpdb->prefix . 'slbp_subscriptions';
		
		$wpdb->insert(
			$table_name,
			array(
				'user_id'           => isset( $subscription['user_id'] ) ? intval( $subscription['user_id'] ) : 0,
				'subscription_id'   => isset( $subscription['subscription_id'] ) ? sanitize_text_field( $subscription['subscription_id'] ) : '',
				'payment_gateway'   => isset( $subscription['payment_gateway'] ) ? sanitize_text_field( $subscription['payment_gateway'] ) : '',
				'plan_id'           => isset( $subscription['plan_id'] ) ? sanitize_text_field( $subscription['plan_id'] ) : '',
				'status'            => isset( $subscription['status'] ) ? sanitize_text_field( $subscription['status'] ) : 'pending',
				'amount'            => isset( $subscription['amount'] ) ? floatval( $subscription['amount'] ) : 0,
				'currency'          => isset( $subscription['currency'] ) ? sanitize_text_field( $subscription['currency'] ) : 'USD',
				'billing_cycle'     => isset( $subscription['billing_cycle'] ) ? sanitize_text_field( $subscription['billing_cycle'] ) : 'monthly',
				'next_billing_date' => isset( $subscription['next_billing_date'] ) ? sanitize_text_field( $subscription['next_billing_date'] ) : null,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s' )
		);
	}
}