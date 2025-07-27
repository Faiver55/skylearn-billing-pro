<?php
/**
 * The audit logging functionality of the plugin.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/compliance
 */

/**
 * The audit logging functionality of the plugin.
 *
 * Provides comprehensive audit logging for billing events, admin actions,
 * user activities, and API access for compliance and security purposes.
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/compliance
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Audit_Logger {

	/**
	 * The audit log table name.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $table_name    The audit log table name.
	 */
	private $table_name;

	/**
	 * Maximum log retention period in days.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      int    $retention_days    Log retention period.
	 */
	private $retention_days;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'slbp_audit_logs';
		$this->retention_days = get_option( 'slbp_audit_retention_days', 90 );
		$this->init_hooks();
	}

	/**
	 * Initialize audit logging hooks.
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		// Billing events
		add_action( 'slbp_payment_completed', array( $this, 'log_payment_completed' ), 10, 2 );
		add_action( 'slbp_payment_failed', array( $this, 'log_payment_failed' ), 10, 2 );
		add_action( 'slbp_payment_refunded', array( $this, 'log_payment_refunded' ), 10, 2 );
		add_action( 'slbp_subscription_created', array( $this, 'log_subscription_created' ), 10, 2 );
		add_action( 'slbp_subscription_cancelled', array( $this, 'log_subscription_cancelled' ), 10, 2 );

		// User events
		add_action( 'wp_login', array( $this, 'log_user_login' ), 10, 2 );
		add_action( 'wp_login_failed', array( $this, 'log_login_failed' ), 10, 1 );
		add_action( 'slbp_user_enrolled', array( $this, 'log_user_enrolled' ), 10, 3 );
		add_action( 'slbp_user_access_granted', array( $this, 'log_access_granted' ), 10, 3 );

		// Admin events
		add_action( 'slbp_settings_updated', array( $this, 'log_settings_updated' ), 10, 2 );
		add_action( 'slbp_gateway_configured', array( $this, 'log_gateway_configured' ), 10, 2 );
		add_action( 'slbp_user_data_exported', array( $this, 'log_data_export' ), 10, 2 );
		add_action( 'slbp_user_data_deleted', array( $this, 'log_data_deletion' ), 10, 2 );

		// API events
		add_action( 'slbp_api_key_created', array( $this, 'log_api_key_created' ), 10, 2 );
		add_action( 'slbp_api_request', array( $this, 'log_api_request' ), 10, 3 );
		add_action( 'slbp_webhook_received', array( $this, 'log_webhook_received' ), 10, 3 );

		// Cleanup old logs
		add_action( 'slbp_daily_cleanup', array( $this, 'cleanup_old_logs' ) );
	}

	/**
	 * Log a generic audit event.
	 *
	 * @since    1.0.0
	 * @param    string    $event_type     Type of event (payment, user, admin, api).
	 * @param    string    $action         Specific action taken.
	 * @param    int       $user_id        ID of the user involved.
	 * @param    array     $metadata       Additional event metadata.
	 * @param    string    $severity       Event severity (info, warning, error).
	 * @return   int|false                 Log ID on success, false on failure.
	 */
	public function log_event( $event_type, $action, $user_id = 0, $metadata = array(), $severity = 'info' ) {
		global $wpdb;

		// Get current user if not specified
		if ( 0 === $user_id ) {
			$user_id = get_current_user_id();
		}

		// Prepare log data
		$log_data = array(
			'event_type' => sanitize_text_field( $event_type ),
			'action' => sanitize_text_field( $action ),
			'user_id' => intval( $user_id ),
			'user_ip' => $this->get_user_ip(),
			'user_agent' => substr( $_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255 ),
			'metadata' => wp_json_encode( $metadata ),
			'severity' => sanitize_text_field( $severity ),
			'created_at' => current_time( 'mysql' ),
		);

		// Insert log entry
		$result = $wpdb->insert(
			$this->table_name,
			$log_data,
			array( '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false !== $result ) {
			// Trigger action for real-time notifications if needed
			do_action( 'slbp_audit_logged', $wpdb->insert_id, $log_data );
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Get audit logs with filtering and pagination.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments.
	 * @return   array             Array of log entries and total count.
	 */
	public function get_logs( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'event_type' => '',
			'action' => '',
			'user_id' => 0,
			'severity' => '',
			'start_date' => '',
			'end_date' => '',
			'search' => '',
			'orderby' => 'created_at',
			'order' => 'DESC',
			'limit' => 20,
			'offset' => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		// Build WHERE conditions
		$where_conditions = array( '1=1' );
		$where_values = array();

		if ( ! empty( $args['event_type'] ) ) {
			$where_conditions[] = 'event_type = %s';
			$where_values[] = $args['event_type'];
		}

		if ( ! empty( $args['action'] ) ) {
			$where_conditions[] = 'action = %s';
			$where_values[] = $args['action'];
		}

		if ( ! empty( $args['user_id'] ) ) {
			$where_conditions[] = 'user_id = %d';
			$where_values[] = $args['user_id'];
		}

		if ( ! empty( $args['severity'] ) ) {
			$where_conditions[] = 'severity = %s';
			$where_values[] = $args['severity'];
		}

		if ( ! empty( $args['start_date'] ) ) {
			$where_conditions[] = 'created_at >= %s';
			$where_values[] = $args['start_date'];
		}

		if ( ! empty( $args['end_date'] ) ) {
			$where_conditions[] = 'created_at <= %s';
			$where_values[] = $args['end_date'];
		}

		if ( ! empty( $args['search'] ) ) {
			$where_conditions[] = '(action LIKE %s OR metadata LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		$where_clause = implode( ' AND ', $where_conditions );

		// Get total count
		$count_query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}";
		if ( ! empty( $where_values ) ) {
			$count_query = $wpdb->prepare( $count_query, $where_values );
		}
		$total_count = $wpdb->get_var( $count_query );

		// Get logs with pagination
		$orderby = in_array( $args['orderby'], array( 'id', 'event_type', 'action', 'user_id', 'severity', 'created_at' ), true ) ? $args['orderby'] : 'created_at';
		$order = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		$query = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$query_values = array_merge( $where_values, array( $args['limit'], $args['offset'] ) );
		$logs_query = $wpdb->prepare( $query, $query_values );
		$logs = $wpdb->get_results( $logs_query );

		// Decode metadata for each log entry
		foreach ( $logs as $log ) {
			$log->metadata = json_decode( $log->metadata, true );
		}

		return array(
			'logs' => $logs,
			'total' => $total_count,
		);
	}

	/**
	 * Export audit logs to CSV.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments for filtering.
	 * @return   string|WP_Error   CSV file URL or error.
	 */
	public function export_logs_csv( $args = array() ) {
		// Remove pagination for export
		$args['limit'] = 0;
		$args['offset'] = 0;

		$result = $this->get_logs( $args );
		$logs = $result['logs'];

		if ( empty( $logs ) ) {
			return new WP_Error( 'no_logs', __( 'No audit logs found for the specified criteria.', 'skylearn-billing-pro' ) );
		}

		$upload_dir = wp_upload_dir();
		$filename = 'slbp-audit-logs-' . date( 'Y-m-d-H-i-s' ) . '.csv';
		$file_path = $upload_dir['basedir'] . '/slbp-exports/' . $filename;

		// Create exports directory if it doesn't exist
		wp_mkdir_p( dirname( $file_path ) );

		$file_handle = fopen( $file_path, 'w' );

		if ( false === $file_handle ) {
			return new WP_Error( 'file_creation_failed', __( 'Failed to create export file.', 'skylearn-billing-pro' ) );
		}

		// Write CSV headers
		$headers = array(
			'ID',
			'Event Type',
			'Action',
			'User ID',
			'User IP',
			'User Agent',
			'Severity',
			'Metadata',
			'Created At',
		);
		fputcsv( $file_handle, $headers );

		// Write log data
		foreach ( $logs as $log ) {
			$row = array(
				$log->id,
				$log->event_type,
				$log->action,
				$log->user_id,
				$log->user_ip,
				$log->user_agent,
				$log->severity,
				wp_json_encode( $log->metadata ),
				$log->created_at,
			);
			fputcsv( $file_handle, $row );
		}

		fclose( $file_handle );

		return $upload_dir['baseurl'] . '/slbp-exports/' . $filename;
	}

	/**
	 * Log payment completion event.
	 *
	 * @since    1.0.0
	 * @param    array    $payment_data    Payment data.
	 * @param    int      $user_id        User ID.
	 */
	public function log_payment_completed( $payment_data, $user_id ) {
		$metadata = array(
			'payment_id' => $payment_data['payment_id'] ?? '',
			'amount' => $payment_data['amount'] ?? 0,
			'currency' => $payment_data['currency'] ?? 'USD',
			'gateway' => $payment_data['gateway'] ?? '',
			'transaction_id' => $payment_data['transaction_id'] ?? '',
		);

		$this->log_event( 'payment', 'payment_completed', $user_id, $metadata, 'info' );
	}

	/**
	 * Log payment failure event.
	 *
	 * @since    1.0.0
	 * @param    array    $payment_data    Payment data.
	 * @param    int      $user_id        User ID.
	 */
	public function log_payment_failed( $payment_data, $user_id ) {
		$metadata = array(
			'payment_id' => $payment_data['payment_id'] ?? '',
			'amount' => $payment_data['amount'] ?? 0,
			'currency' => $payment_data['currency'] ?? 'USD',
			'gateway' => $payment_data['gateway'] ?? '',
			'error_message' => $payment_data['error_message'] ?? '',
		);

		$this->log_event( 'payment', 'payment_failed', $user_id, $metadata, 'error' );
	}

	/**
	 * Log payment refund event.
	 *
	 * @since    1.0.0
	 * @param    array    $refund_data    Refund data.
	 * @param    int      $user_id       User ID.
	 */
	public function log_payment_refunded( $refund_data, $user_id ) {
		$metadata = array(
			'refund_id' => $refund_data['refund_id'] ?? '',
			'payment_id' => $refund_data['payment_id'] ?? '',
			'amount' => $refund_data['amount'] ?? 0,
			'reason' => $refund_data['reason'] ?? '',
		);

		$this->log_event( 'payment', 'payment_refunded', $user_id, $metadata, 'warning' );
	}

	/**
	 * Log user login event.
	 *
	 * @since    1.0.0
	 * @param    string    $user_login    Username.
	 * @param    WP_User   $user         User object.
	 */
	public function log_user_login( $user_login, $user ) {
		$metadata = array(
			'username' => $user_login,
			'user_email' => $user->user_email,
		);

		$this->log_event( 'user', 'login', $user->ID, $metadata, 'info' );
	}

	/**
	 * Log failed login attempt.
	 *
	 * @since    1.0.0
	 * @param    string    $username    Username.
	 */
	public function log_login_failed( $username ) {
		$metadata = array(
			'username' => $username,
		);

		$this->log_event( 'user', 'login_failed', 0, $metadata, 'warning' );
	}

	/**
	 * Log settings update event.
	 *
	 * @since    1.0.0
	 * @param    string    $setting_group    Setting group name.
	 * @param    array     $old_values      Previous setting values.
	 */
	public function log_settings_updated( $setting_group, $old_values ) {
		$metadata = array(
			'setting_group' => $setting_group,
			'old_values' => $old_values,
		);

		$this->log_event( 'admin', 'settings_updated', get_current_user_id(), $metadata, 'info' );
	}

	/**
	 * Log API request.
	 *
	 * @since    1.0.0
	 * @param    string    $endpoint    API endpoint.
	 * @param    string    $method     HTTP method.
	 * @param    int       $user_id    User ID making the request.
	 */
	public function log_api_request( $endpoint, $method, $user_id ) {
		$metadata = array(
			'endpoint' => $endpoint,
			'method' => $method,
		);

		$this->log_event( 'api', 'api_request', $user_id, $metadata, 'info' );
	}

	/**
	 * Get the user's IP address.
	 *
	 * @since    1.0.0
	 * @return   string    User IP address.
	 */
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

	/**
	 * Clean up old audit logs based on retention policy.
	 *
	 * @since    1.0.0
	 */
	public function cleanup_old_logs() {
		global $wpdb;

		$cutoff_date = date( 'Y-m-d H:i:s', strtotime( "-{$this->retention_days} days" ) );

		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->table_name} WHERE created_at < %s",
				$cutoff_date
			)
		);

		if ( $deleted ) {
			$this->log_event( 'system', 'audit_logs_cleaned', 0, array( 'deleted_count' => $deleted ), 'info' );
		}
	}

	/**
	 * Create the audit log table.
	 *
	 * @since    1.0.0
	 */
	public function create_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->table_name} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			event_type varchar(50) NOT NULL,
			action varchar(100) NOT NULL,
			user_id bigint(20) DEFAULT 0,
			user_ip varchar(45) NOT NULL,
			user_agent varchar(255) DEFAULT '',
			metadata longtext DEFAULT '',
			severity varchar(20) DEFAULT 'info',
			created_at datetime NOT NULL,
			PRIMARY KEY (id),
			KEY event_type (event_type),
			KEY action (action),
			KEY user_id (user_id),
			KEY severity (severity),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}