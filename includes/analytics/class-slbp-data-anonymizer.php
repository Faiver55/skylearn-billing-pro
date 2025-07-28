<?php
/**
 * Data anonymization and privacy protection for analytics.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/analytics
 */

/**
 * Data anonymization class.
 *
 * Handles anonymization of user data for analytics and reporting while
 * maintaining data integrity for business intelligence purposes.
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/analytics
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Data_Anonymizer {

	/**
	 * Anonymization rules.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $anonymization_rules    Rules for data anonymization.
	 */
	private $anonymization_rules;

	/**
	 * Audit logger instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SLBP_Audit_Logger    $audit_logger    Audit logger instance.
	 */
	private $audit_logger;

	/**
	 * Initialize the class.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->init_anonymization_rules();
		$this->audit_logger = class_exists( 'SLBP_Audit_Logger' ) ? new SLBP_Audit_Logger() : null;
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		// AJAX handlers for anonymization management
		add_action( 'wp_ajax_slbp_anonymize_data', array( $this, 'ajax_anonymize_data' ) );
		add_action( 'wp_ajax_slbp_get_anonymization_status', array( $this, 'ajax_get_anonymization_status' ) );
		add_action( 'wp_ajax_slbp_update_anonymization_settings', array( $this, 'ajax_update_anonymization_settings' ) );

		// Scheduled anonymization
		add_action( 'slbp_scheduled_anonymization', array( $this, 'run_scheduled_anonymization' ) );

		// User data export/deletion hooks (GDPR compliance)
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_personal_data_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_personal_data_eraser' ) );
	}

	/**
	 * Initialize anonymization rules.
	 *
	 * @since    1.0.0
	 */
	private function init_anonymization_rules() {
		$this->anonymization_rules = array(
			'user_data' => array(
				'email' => array(
					'method' => 'hash',
					'preserve_domain' => true,
				),
				'ip_address' => array(
					'method' => 'mask_ip',
					'preserve_country' => true,
				),
				'name' => array(
					'method' => 'pseudonymize',
					'pattern' => 'User_{id}',
				),
				'phone' => array(
					'method' => 'remove',
				),
				'address' => array(
					'method' => 'generalize',
					'level' => 'city',
				),
			),
			'transaction_data' => array(
				'user_id' => array(
					'method' => 'preserve',
				),
				'amount' => array(
					'method' => 'preserve',
				),
				'gateway_transaction_id' => array(
					'method' => 'hash',
				),
				'metadata' => array(
					'method' => 'filter',
					'allowed_fields' => array( 'course_id', 'product_type' ),
				),
			),
			'analytics_data' => array(
				'session_id' => array(
					'method' => 'hash',
				),
				'user_agent' => array(
					'method' => 'generalize',
					'level' => 'browser_family',
				),
				'referrer' => array(
					'method' => 'generalize',
					'level' => 'domain',
				),
			),
		);

		// Allow customization via filter
		$this->anonymization_rules = apply_filters( 'slbp_anonymization_rules', $this->anonymization_rules );
	}

	/**
	 * Anonymize data based on type and rules.
	 *
	 * @since    1.0.0
	 * @param    array     $data      Data to anonymize.
	 * @param    string    $data_type Data type.
	 * @param    array     $options   Anonymization options.
	 * @return   array                Anonymized data.
	 */
	public function anonymize_data( $data, $data_type, $options = array() ) {
		if ( ! isset( $this->anonymization_rules[ $data_type ] ) ) {
			return $data;
		}

		$rules = $this->anonymization_rules[ $data_type ];
		$anonymized_data = array();

		foreach ( $data as $item ) {
			$anonymized_item = array();

			foreach ( $item as $field => $value ) {
				if ( isset( $rules[ $field ] ) ) {
					$anonymized_item[ $field ] = $this->apply_anonymization_rule( $value, $rules[ $field ], $field );
				} else {
					// Default behavior: preserve fields not in rules
					$anonymized_item[ $field ] = $value;
				}
			}

			$anonymized_data[] = $anonymized_item;
		}

		// Log anonymization activity
		$this->log_anonymization_activity( $data_type, count( $data ), $options );

		return $anonymized_data;
	}

	/**
	 * Apply anonymization rule to a value.
	 *
	 * @since    1.0.0
	 * @param    mixed    $value    Value to anonymize.
	 * @param    array    $rule     Anonymization rule.
	 * @param    string   $field    Field name.
	 * @return   mixed             Anonymized value.
	 */
	private function apply_anonymization_rule( $value, $rule, $field ) {
		switch ( $rule['method'] ) {
			case 'hash':
				return $this->hash_value( $value, $rule );

			case 'mask_ip':
				return $this->mask_ip_address( $value, $rule );

			case 'pseudonymize':
				return $this->pseudonymize_value( $value, $rule );

			case 'remove':
				return '';

			case 'generalize':
				return $this->generalize_value( $value, $rule );

			case 'filter':
				return $this->filter_value( $value, $rule );

			case 'preserve':
			default:
				return $value;
		}
	}

	/**
	 * Hash a value with optional domain preservation.
	 *
	 * @since    1.0.0
	 * @param    string    $value    Value to hash.
	 * @param    array     $rule     Rule configuration.
	 * @return   string             Hashed value.
	 */
	private function hash_value( $value, $rule ) {
		if ( empty( $value ) ) {
			return $value;
		}

		$hash = hash( 'sha256', $value . $this->get_salt() );

		// For emails, optionally preserve domain
		if ( isset( $rule['preserve_domain'] ) && $rule['preserve_domain'] && strpos( $value, '@' ) !== false ) {
			$parts = explode( '@', $value );
			if ( count( $parts ) === 2 ) {
				return substr( $hash, 0, 16 ) . '@' . $parts[1];
			}
		}

		return substr( $hash, 0, 16 );
	}

	/**
	 * Mask IP address while optionally preserving country-level data.
	 *
	 * @since    1.0.0
	 * @param    string    $ip_address    IP address to mask.
	 * @param    array     $rule          Rule configuration.
	 * @return   string                   Masked IP address.
	 */
	private function mask_ip_address( $ip_address, $rule ) {
		if ( empty( $ip_address ) ) {
			return $ip_address;
		}

		// IPv4 masking
		if ( filter_var( $ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$parts = explode( '.', $ip_address );
			if ( count( $parts ) === 4 ) {
				// Mask last octet for minimal anonymization, or last two for stronger anonymization
				if ( isset( $rule['preserve_country'] ) && $rule['preserve_country'] ) {
					return $parts[0] . '.' . $parts[1] . '.0.0';
				} else {
					return $parts[0] . '.0.0.0';
				}
			}
		}

		// IPv6 masking
		if ( filter_var( $ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$parts = explode( ':', $ip_address );
			if ( count( $parts ) >= 4 ) {
				// Keep first 64 bits for country-level identification
				if ( isset( $rule['preserve_country'] ) && $rule['preserve_country'] ) {
					return implode( ':', array_slice( $parts, 0, 4 ) ) . '::';
				} else {
					return $parts[0] . '::';
				}
			}
		}

		return hash( 'sha256', $ip_address . $this->get_salt() );
	}

	/**
	 * Pseudonymize a value using a pattern.
	 *
	 * @since    1.0.0
	 * @param    string    $value    Value to pseudonymize.
	 * @param    array     $rule     Rule configuration.
	 * @return   string             Pseudonymized value.
	 */
	private function pseudonymize_value( $value, $rule ) {
		if ( empty( $value ) ) {
			return $value;
		}

		$pattern = $rule['pattern'] ?? 'Anonymous_{hash}';
		$hash = substr( hash( 'sha256', $value . $this->get_salt() ), 0, 8 );

		return str_replace(
			array( '{hash}', '{id}' ),
			array( $hash, $hash ),
			$pattern
		);
	}

	/**
	 * Generalize a value to a less specific form.
	 *
	 * @since    1.0.0
	 * @param    string    $value    Value to generalize.
	 * @param    array     $rule     Rule configuration.
	 * @return   string             Generalized value.
	 */
	private function generalize_value( $value, $rule ) {
		$level = $rule['level'] ?? 'partial';

		switch ( $level ) {
			case 'city':
				// For addresses, keep only city level
				return $this->extract_city_from_address( $value );

			case 'browser_family':
				// For user agents, keep only browser family
				return $this->extract_browser_family( $value );

			case 'domain':
				// For URLs, keep only domain
				return $this->extract_domain_from_url( $value );

			default:
				return $value;
		}
	}

	/**
	 * Filter value based on allowed fields.
	 *
	 * @since    1.0.0
	 * @param    mixed    $value    Value to filter.
	 * @param    array    $rule     Rule configuration.
	 * @return   mixed             Filtered value.
	 */
	private function filter_value( $value, $rule ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}

		$allowed_fields = $rule['allowed_fields'] ?? array();
		$filtered_value = array();

		foreach ( $allowed_fields as $field ) {
			if ( isset( $value[ $field ] ) ) {
				$filtered_value[ $field ] = $value[ $field ];
			}
		}

		return $filtered_value;
	}

	/**
	 * Get or generate salt for hashing.
	 *
	 * @since    1.0.0
	 * @return   string    Salt value.
	 */
	private function get_salt() {
		$salt = get_option( 'slbp_anonymization_salt' );

		if ( empty( $salt ) ) {
			$salt = wp_generate_password( 32, false );
			update_option( 'slbp_anonymization_salt', $salt );
		}

		return $salt;
	}

	/**
	 * Helper methods for data generalization.
	 */
	private function extract_city_from_address( $address ) {
		// Simple implementation - in practice, this would use a proper address parser
		$parts = explode( ',', $address );
		return trim( $parts[0] ?? '' );
	}

	private function extract_browser_family( $user_agent ) {
		// Simple browser detection
		if ( strpos( $user_agent, 'Chrome' ) !== false ) {
			return 'Chrome';
		} elseif ( strpos( $user_agent, 'Firefox' ) !== false ) {
			return 'Firefox';
		} elseif ( strpos( $user_agent, 'Safari' ) !== false ) {
			return 'Safari';
		} else {
			return 'Other';
		}
	}

	private function extract_domain_from_url( $url ) {
		$parsed = parse_url( $url );
		return $parsed['host'] ?? '';
	}

	/**
	 * Log anonymization activity.
	 *
	 * @since    1.0.0
	 * @param    string    $data_type     Data type anonymized.
	 * @param    int       $record_count  Number of records processed.
	 * @param    array     $options       Anonymization options.
	 */
	private function log_anonymization_activity( $data_type, $record_count, $options ) {
		if ( $this->audit_logger ) {
			$this->audit_logger->log_event(
				'compliance',
				'data_anonymized',
				get_current_user_id(),
				array(
					'data_type' => $data_type,
					'record_count' => $record_count,
					'options' => $options,
				),
				'info'
			);
		}
	}

	/**
	 * Get anonymized analytics data for export.
	 *
	 * @since    1.0.0
	 * @param    string    $data_type    Type of data to anonymize.
	 * @param    array     $filters      Data filters.
	 * @param    array     $options      Anonymization options.
	 * @return   array                  Anonymized data.
	 */
	public function get_anonymized_export_data( $data_type, $filters = array(), $options = array() ) {
		// Get raw data based on type
		$raw_data = $this->get_raw_data( $data_type, $filters );

		// Apply anonymization
		return $this->anonymize_data( $raw_data, $data_type, $options );
	}

	/**
	 * Get raw data for anonymization.
	 *
	 * @since    1.0.0
	 * @param    string    $data_type    Data type.
	 * @param    array     $filters      Filters.
	 * @return   array                  Raw data.
	 */
	private function get_raw_data( $data_type, $filters = array() ) {
		global $wpdb;

		switch ( $data_type ) {
			case 'transactions':
				$table = $wpdb->prefix . 'slbp_transactions';
				return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC", ARRAY_A );

			case 'users':
				return $wpdb->get_results( "SELECT ID, user_email, user_login, display_name FROM {$wpdb->users}", ARRAY_A );

			case 'analytics_sessions':
				// This would query analytics session data
				return array();

			default:
				return array();
		}
	}

	/**
	 * Run scheduled anonymization.
	 *
	 * @since    1.0.0
	 */
	public function run_scheduled_anonymization() {
		$settings = get_option( 'slbp_anonymization_settings', array() );

		if ( ! $settings['enabled'] ?? false ) {
			return;
		}

		$retention_days = $settings['retention_days'] ?? 365;
		$cutoff_date = date( 'Y-m-d', strtotime( "-{$retention_days} days" ) );

		// Anonymize old transaction data
		$this->anonymize_old_transactions( $cutoff_date );

		// Anonymize old user activity data
		$this->anonymize_old_user_activity( $cutoff_date );
	}

	/**
	 * Anonymize old transactions.
	 *
	 * @since    1.0.0
	 * @param    string    $cutoff_date    Cutoff date.
	 */
	private function anonymize_old_transactions( $cutoff_date ) {
		global $wpdb;
		$table = $wpdb->prefix . 'slbp_transactions';

		$old_transactions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE created_at < %s AND anonymized = 0",
				$cutoff_date
			),
			ARRAY_A
		);

		if ( empty( $old_transactions ) ) {
			return;
		}

		$anonymized_transactions = $this->anonymize_data( $old_transactions, 'transaction_data' );

		// Update transactions with anonymized data
		foreach ( $anonymized_transactions as $index => $transaction ) {
			$original_id = $old_transactions[ $index ]['id'];

			$wpdb->update(
				$table,
				array(
					'user_email' => $transaction['user_email'] ?? '',
					'gateway_transaction_id' => $transaction['gateway_transaction_id'] ?? '',
					'anonymized' => 1,
				),
				array( 'id' => $original_id ),
				array( '%s', '%s', '%d' ),
				array( '%d' )
			);
		}

		$this->log_anonymization_activity( 'transactions', count( $old_transactions ), array( 'cutoff_date' => $cutoff_date ) );
	}

	/**
	 * Anonymize old user activity.
	 *
	 * @since    1.0.0
	 * @param    string    $cutoff_date    Cutoff date.
	 */
	private function anonymize_old_user_activity( $cutoff_date ) {
		// Implementation for anonymizing user activity data
		// This would work with activity logs, session data, etc.
	}

	/**
	 * AJAX handlers.
	 */
	public function ajax_anonymize_data() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'slbp_anonymization_nonce' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Security check failed.' );
		}

		$data_type = sanitize_text_field( $_POST['data_type'] );
		$filters = $_POST['filters'] ?? array();

		$result = $this->run_anonymization( $data_type, $filters );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( $result );
	}

	public function ajax_get_anonymization_status() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'slbp_anonymization_nonce' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Security check failed.' );
		}

		$status = array(
			'enabled' => get_option( 'slbp_anonymization_enabled', false ),
			'last_run' => get_option( 'slbp_last_anonymization_run', '' ),
			'processed_records' => get_option( 'slbp_anonymized_records_count', 0 ),
		);

		wp_send_json_success( $status );
	}

	public function ajax_update_anonymization_settings() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'slbp_anonymization_nonce' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Security check failed.' );
		}

		$settings = array(
			'enabled' => (bool) $_POST['enabled'],
			'retention_days' => intval( $_POST['retention_days'] ),
			'schedule' => sanitize_text_field( $_POST['schedule'] ),
		);

		update_option( 'slbp_anonymization_settings', $settings );

		wp_send_json_success();
	}

	/**
	 * GDPR compliance methods.
	 */
	public function register_personal_data_exporter( $exporters ) {
		$exporters['slbp-billing-data'] = array(
			'exporter_friendly_name' => __( 'SkyLearn Billing Data', 'skylearn-billing-pro' ),
			'callback' => array( $this, 'export_personal_data' ),
		);

		return $exporters;
	}

	public function register_personal_data_eraser( $erasers ) {
		$erasers['slbp-billing-data'] = array(
			'eraser_friendly_name' => __( 'SkyLearn Billing Data', 'skylearn-billing-pro' ),
			'callback' => array( $this, 'erase_personal_data' ),
		);

		return $erasers;
	}

	public function export_personal_data( $email_address, $page = 1 ) {
		$data_to_export = array();
		$done = true;

		// Export user's billing data
		global $wpdb;
		$table = $wpdb->prefix . 'slbp_transactions';

		$transactions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE user_email = %s",
				$email_address
			)
		);

		foreach ( $transactions as $transaction ) {
			$data_to_export[] = array(
				'group_id' => 'slbp-transactions',
				'group_label' => __( 'Billing Transactions', 'skylearn-billing-pro' ),
				'item_id' => "transaction-{$transaction->id}",
				'data' => array(
					array(
						'name' => __( 'Transaction ID', 'skylearn-billing-pro' ),
						'value' => $transaction->id,
					),
					array(
						'name' => __( 'Amount', 'skylearn-billing-pro' ),
						'value' => $transaction->amount,
					),
					array(
						'name' => __( 'Date', 'skylearn-billing-pro' ),
						'value' => $transaction->created_at,
					),
				),
			);
		}

		return array(
			'data' => $data_to_export,
			'done' => $done,
		);
	}

	public function erase_personal_data( $email_address, $page = 1 ) {
		$items_removed = false;
		$items_retained = false;
		$messages = array();

		global $wpdb;
		$table = $wpdb->prefix . 'slbp_transactions';

		// Anonymize instead of deleting to maintain business intelligence
		$result = $wpdb->update(
			$table,
			array(
				'user_email' => $this->hash_value( $email_address, array() ),
				'anonymized' => 1,
			),
			array( 'user_email' => $email_address ),
			array( '%s', '%d' ),
			array( '%s' )
		);

		if ( $result !== false ) {
			$items_removed = true;
			$messages[] = sprintf( __( 'Anonymized %d transaction records.', 'skylearn-billing-pro' ), $result );
		}

		return array(
			'items_removed' => $items_removed,
			'items_retained' => $items_retained,
			'messages' => $messages,
			'done' => true,
		);
	}

	/**
	 * Get anonymization settings.
	 *
	 * @since    1.0.0
	 * @return   array    Anonymization settings.
	 */
	public function get_anonymization_settings() {
		return get_option( 'slbp_anonymization_settings', array(
			'enabled' => false,
			'retention_days' => 365,
			'schedule' => 'monthly',
		) );
	}

	/**
	 * Run anonymization for specific data type.
	 *
	 * @since    1.0.0
	 * @param    string    $data_type    Data type.
	 * @param    array     $filters      Filters.
	 * @return   array|WP_Error         Result or error.
	 */
	private function run_anonymization( $data_type, $filters = array() ) {
		$processed_count = 0;

		switch ( $data_type ) {
			case 'transactions':
				$processed_count = $this->anonymize_transaction_batch( $filters );
				break;

			case 'users':
				$processed_count = $this->anonymize_user_batch( $filters );
				break;

			default:
				return new WP_Error( 'invalid_data_type', __( 'Invalid data type for anonymization.', 'skylearn-billing-pro' ) );
		}

		update_option( 'slbp_last_anonymization_run', current_time( 'mysql' ) );
		$total_count = get_option( 'slbp_anonymized_records_count', 0 );
		update_option( 'slbp_anonymized_records_count', $total_count + $processed_count );

		return array(
			'processed_count' => $processed_count,
			'data_type' => $data_type,
		);
	}

	private function anonymize_transaction_batch( $filters = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'slbp_transactions';

		$where_clause = 'anonymized = 0';
		if ( isset( $filters['older_than_days'] ) ) {
			$cutoff_date = date( 'Y-m-d', strtotime( "-{$filters['older_than_days']} days" ) );
			$where_clause .= $wpdb->prepare( ' AND created_at < %s', $cutoff_date );
		}

		$transactions = $wpdb->get_results( "SELECT * FROM {$table} WHERE {$where_clause} LIMIT 100", ARRAY_A );

		if ( empty( $transactions ) ) {
			return 0;
		}

		$anonymized = $this->anonymize_data( $transactions, 'transaction_data' );

		// Update records
		foreach ( $anonymized as $index => $transaction ) {
			$original_id = $transactions[ $index ]['id'];
			$wpdb->update(
				$table,
				array(
					'user_email' => $transaction['user_email'] ?? '',
					'anonymized' => 1,
				),
				array( 'id' => $original_id ),
				array( '%s', '%d' ),
				array( '%d' )
			);
		}

		return count( $anonymized );
	}

	private function anonymize_user_batch( $filters = array() ) {
		// Implementation for anonymizing user data batch
		return 0;
	}
}