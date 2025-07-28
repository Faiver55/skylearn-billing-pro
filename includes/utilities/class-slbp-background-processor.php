<?php
/**
 * Background processing and async task management for SkyLearn Billing Pro
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/utilities
 */

/**
 * Background processing manager.
 *
 * Handles asynchronous processing of heavy tasks like billing runs and notifications.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/utilities
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Background_Processor {

	/**
	 * Task queue table name.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $queue_table    Task queue table name.
	 */
	private $queue_table;

	/**
	 * Maximum number of tasks to process per batch.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      int    $batch_size    Batch processing size.
	 */
	private $batch_size = 10;

	/**
	 * Maximum execution time for background processing.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      int    $max_execution_time    Maximum execution time in seconds.
	 */
	private $max_execution_time = 30;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		global $wpdb;
		$this->queue_table = $wpdb->prefix . 'slbp_background_tasks';
		
		$this->batch_size = apply_filters( 'slbp_background_batch_size', $this->batch_size );
		$this->max_execution_time = apply_filters( 'slbp_background_max_execution_time', $this->max_execution_time );

		// Create queue table if it doesn't exist
		$this->maybe_create_queue_table();

		// Hook into WordPress cron
		add_action( 'slbp_process_background_tasks', array( $this, 'process_tasks' ) );
	}

	/**
	 * Queue a background task.
	 *
	 * @since    1.0.0
	 * @param    string $task_type    Type of task to queue.
	 * @param    array  $task_data    Task data and parameters.
	 * @param    int    $priority     Task priority (lower numbers = higher priority).
	 * @param    string $scheduled_at Optional. When to execute the task (Y-m-d H:i:s format).
	 * @return   int|false            Task ID on success, false on failure.
	 */
	public function queue_task( $task_type, $task_data, $priority = 10, $scheduled_at = null ) {
		global $wpdb;

		if ( null === $scheduled_at ) {
			$scheduled_at = current_time( 'mysql' );
		}

		$task_id = $wpdb->insert(
			$this->queue_table,
			array(
				'task_type' => $task_type,
				'task_data' => wp_json_encode( $task_data ),
				'priority' => $priority,
				'status' => 'pending',
				'scheduled_at' => $scheduled_at,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%d', '%s', '%s', '%s' )
		);

		if ( false === $task_id ) {
			error_log( 'SLBP Background Processor: Failed to queue task ' . $task_type );
			return false;
		}

		// Schedule background processing if not already scheduled
		if ( ! wp_next_scheduled( 'slbp_process_background_tasks' ) ) {
			wp_schedule_single_event( time() + 60, 'slbp_process_background_tasks' );
		}

		return $wpdb->insert_id;
	}

	/**
	 * Process pending background tasks.
	 *
	 * @since    1.0.0
	 * @return   int    Number of tasks processed.
	 */
	public function process_tasks() {
		global $wpdb;

		$start_time = microtime( true );
		$processed_tasks = 0;

		// Get pending tasks ordered by priority and scheduled time
		$tasks = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->queue_table} 
				WHERE status = 'pending' 
				AND scheduled_at <= %s 
				ORDER BY priority ASC, scheduled_at ASC 
				LIMIT %d",
				current_time( 'mysql' ),
				$this->batch_size
			),
			ARRAY_A
		);

		if ( empty( $tasks ) ) {
			return 0;
		}

		foreach ( $tasks as $task ) {
			// Check execution time limit
			if ( ( microtime( true ) - $start_time ) >= $this->max_execution_time ) {
				break;
			}

			$this->process_single_task( $task );
			$processed_tasks++;
		}

		// Schedule next batch if there are more tasks
		$remaining_tasks = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->queue_table} 
				WHERE status = 'pending' 
				AND scheduled_at <= %s",
				current_time( 'mysql' )
			)
		);

		if ( $remaining_tasks > 0 ) {
			wp_schedule_single_event( time() + 60, 'slbp_process_background_tasks' );
		}

		return $processed_tasks;
	}

	/**
	 * Process a single background task.
	 *
	 * @since    1.0.0
	 * @param    array $task    Task data from database.
	 * @return   bool          True on success, false on failure.
	 */
	private function process_single_task( $task ) {
		global $wpdb;

		// Mark task as processing
		$wpdb->update(
			$this->queue_table,
			array(
				'status' => 'processing',
				'started_at' => current_time( 'mysql' ),
			),
			array( 'id' => $task['id'] ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		$task_data = json_decode( $task['task_data'], true );
		$success = false;
		$error_message = '';

		try {
			switch ( $task['task_type'] ) {
				case 'billing_run':
					$success = $this->process_billing_run( $task_data );
					break;

				case 'send_notifications':
					$success = $this->process_notifications( $task_data );
					break;

				case 'subscription_renewal':
					$success = $this->process_subscription_renewal( $task_data );
					break;

				case 'email_campaign':
					$success = $this->process_email_campaign( $task_data );
					break;

				case 'data_export':
					$success = $this->process_data_export( $task_data );
					break;

				case 'cleanup_logs':
					$success = $this->process_cleanup_logs( $task_data );
					break;

				case 'sync_external_data':
					$success = $this->process_external_sync( $task_data );
					break;

				default:
					$success = apply_filters( 'slbp_process_custom_background_task', false, $task['task_type'], $task_data );
					break;
			}
		} catch ( Exception $e ) {
			$error_message = $e->getMessage();
			error_log( 'SLBP Background Task Error: ' . $error_message );
		}

		// Update task status
		$final_status = $success ? 'completed' : 'failed';
		$update_data = array(
			'status' => $final_status,
			'completed_at' => current_time( 'mysql' ),
		);

		if ( ! empty( $error_message ) ) {
			$update_data['error_message'] = $error_message;
		}

		$wpdb->update(
			$this->queue_table,
			$update_data,
			array( 'id' => $task['id'] ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);

		// Retry failed tasks (up to 3 times)
		if ( ! $success && $task['retry_count'] < 3 ) {
			$this->retry_task( $task['id'] );
		}

		return $success;
	}

	/**
	 * Process billing run task.
	 *
	 * @since    1.0.0
	 * @param    array $task_data    Task data.
	 * @return   bool               True on success, false on failure.
	 */
	private function process_billing_run( $task_data ) {
		// Get subscriptions that need billing
		global $wpdb;

		$subscriptions_table = $wpdb->prefix . 'slbp_subscriptions';
		$subscriptions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$subscriptions_table} 
				WHERE status = 'active' 
				AND next_billing_date <= %s",
				current_time( 'mysql' )
			),
			ARRAY_A
		);

		$success_count = 0;
		foreach ( $subscriptions as $subscription ) {
			if ( $this->process_subscription_billing( $subscription ) ) {
				$success_count++;
			}
		}

		return $success_count > 0 || empty( $subscriptions );
	}

	/**
	 * Process notifications task.
	 *
	 * @since    1.0.0
	 * @param    array $task_data    Task data.
	 * @return   bool               True on success, false on failure.
	 */
	private function process_notifications( $task_data ) {
		$notification_type = $task_data['type'] ?? 'general';
		$recipients = $task_data['recipients'] ?? array();
		$message_data = $task_data['message'] ?? array();

		if ( empty( $recipients ) || empty( $message_data ) ) {
			return false;
		}

		$success_count = 0;
		foreach ( $recipients as $recipient ) {
			if ( $this->send_notification( $recipient, $notification_type, $message_data ) ) {
				$success_count++;
			}
		}

		return $success_count > 0;
	}

	/**
	 * Process subscription renewal task.
	 *
	 * @since    1.0.0
	 * @param    array $task_data    Task data.
	 * @return   bool               True on success, false on failure.
	 */
	private function process_subscription_renewal( $task_data ) {
		$subscription_id = $task_data['subscription_id'] ?? 0;

		if ( empty( $subscription_id ) ) {
			return false;
		}

		global $wpdb;
		$subscriptions_table = $wpdb->prefix . 'slbp_subscriptions';
		
		$subscription = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$subscriptions_table} WHERE id = %d",
				$subscription_id
			),
			ARRAY_A
		);

		if ( ! $subscription ) {
			return false;
		}

		return $this->process_subscription_billing( $subscription );
	}

	/**
	 * Process email campaign task.
	 *
	 * @since    1.0.0
	 * @param    array $task_data    Task data.
	 * @return   bool               True on success, false on failure.
	 */
	private function process_email_campaign( $task_data ) {
		$campaign_id = $task_data['campaign_id'] ?? 0;
		$batch_size = $task_data['batch_size'] ?? 50;

		// This would typically integrate with an email service
		// For now, return true as a placeholder
		return true;
	}

	/**
	 * Process data export task.
	 *
	 * @since    1.0.0
	 * @param    array $task_data    Task data.
	 * @return   bool               True on success, false on failure.
	 */
	private function process_data_export( $task_data ) {
		$export_type = $task_data['type'] ?? 'transactions';
		$date_range = $task_data['date_range'] ?? array();
		$user_id = $task_data['user_id'] ?? 0;

		if ( empty( $user_id ) ) {
			return false;
		}

		$export_data = $this->generate_export_data( $export_type, $date_range );
		
		if ( empty( $export_data ) ) {
			return false;
		}

		// Generate export file
		$upload_dir = wp_upload_dir();
		$export_dir = $upload_dir['basedir'] . '/slbp-exports/';
		
		if ( ! file_exists( $export_dir ) ) {
			wp_mkdir_p( $export_dir );
		}

		$filename = $export_type . '_export_' . date( 'Y-m-d_H-i-s' ) . '.csv';
		$file_path = $export_dir . $filename;

		$csv_content = $this->array_to_csv( $export_data );
		file_put_contents( $file_path, $csv_content );

		// Notify user that export is ready
		$this->send_export_notification( $user_id, $filename );

		return true;
	}

	/**
	 * Process log cleanup task.
	 *
	 * @since    1.0.0
	 * @param    array $task_data    Task data.
	 * @return   bool               True on success, false on failure.
	 */
	private function process_cleanup_logs( $task_data ) {
		global $wpdb;

		$retention_days = $task_data['retention_days'] ?? 90;
		$cleanup_date = date( 'Y-m-d H:i:s', strtotime( "-{$retention_days} days" ) );

		// Clean up API logs
		$api_logs_table = $wpdb->prefix . 'slbp_api_logs';
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$api_logs_table} WHERE created_at < %s",
				$cleanup_date
			)
		);

		// Clean up webhook logs
		$webhook_logs_table = $wpdb->prefix . 'slbp_webhook_logs';
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$webhook_logs_table} WHERE created_at < %s",
				$cleanup_date
			)
		);

		// Clean up completed background tasks
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->queue_table} 
				WHERE status IN ('completed', 'failed') 
				AND completed_at < %s",
				$cleanup_date
			)
		);

		return true;
	}

	/**
	 * Process external data sync task.
	 *
	 * @since    1.0.0
	 * @param    array $task_data    Task data.
	 * @return   bool               True on success, false on failure.
	 */
	private function process_external_sync( $task_data ) {
		$sync_type = $task_data['type'] ?? 'lemon_squeezy';

		switch ( $sync_type ) {
			case 'lemon_squeezy':
				return $this->sync_lemon_squeezy_data( $task_data );

			case 'learndash':
				return $this->sync_learndash_data( $task_data );

			default:
				return apply_filters( 'slbp_sync_external_data', false, $sync_type, $task_data );
		}
	}

	/**
	 * Retry a failed task.
	 *
	 * @since    1.0.0
	 * @param    int $task_id    Task ID to retry.
	 * @return   bool           True on success, false on failure.
	 */
	private function retry_task( $task_id ) {
		global $wpdb;

		// Calculate delay for retry (exponential backoff)
		$retry_delay = rand( 300, 900 ); // 5-15 minutes

		return $wpdb->update(
			$this->queue_table,
			array(
				'status' => 'pending',
				'retry_count' => new \mysqli_sql\expression( 'retry_count + 1' ),
				'scheduled_at' => date( 'Y-m-d H:i:s', time() + $retry_delay ),
				'error_message' => null,
			),
			array( 'id' => $task_id ),
			array( '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Create background tasks queue table if it doesn't exist.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	private function maybe_create_queue_table() {
		global $wpdb;

		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$this->queue_table}'" );

		if ( $table_exists !== $this->queue_table ) {
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE {$this->queue_table} (
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

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}
	}

	/**
	 * Process subscription billing.
	 *
	 * @since    1.0.0
	 * @param    array $subscription    Subscription data.
	 * @return   bool                  True on success, false on failure.
	 */
	private function process_subscription_billing( $subscription ) {
		// This would integrate with the payment gateway to process billing
		// For now, return true as a placeholder
		return true;
	}

	/**
	 * Send notification to user.
	 *
	 * @since    1.0.0
	 * @param    int    $user_id         User ID.
	 * @param    string $type            Notification type.
	 * @param    array  $message_data    Message data.
	 * @return   bool                   True on success, false on failure.
	 */
	private function send_notification( $user_id, $type, $message_data ) {
		global $wpdb;

		$notifications_table = $wpdb->prefix . 'slbp_notifications';
		
		return $wpdb->insert(
			$notifications_table,
			array(
				'user_id' => $user_id,
				'type' => $type,
				'title' => $message_data['title'] ?? '',
				'message' => $message_data['message'] ?? '',
				'data' => wp_json_encode( $message_data['data'] ?? array() ),
			),
			array( '%d', '%s', '%s', '%s', '%s' )
		) !== false;
	}

	/**
	 * Generate export data.
	 *
	 * @since    1.0.0
	 * @param    string $export_type    Export type.
	 * @param    array  $date_range     Date range for export.
	 * @return   array                 Export data.
	 */
	private function generate_export_data( $export_type, $date_range ) {
		global $wpdb;

		switch ( $export_type ) {
			case 'transactions':
				$table = $wpdb->prefix . 'slbp_transactions';
				break;

			case 'subscriptions':
				$table = $wpdb->prefix . 'slbp_subscriptions';
				break;

			default:
				return array();
		}

		$where_clause = '';
		if ( ! empty( $date_range['start'] ) && ! empty( $date_range['end'] ) ) {
			$where_clause = $wpdb->prepare(
				"WHERE created_at BETWEEN %s AND %s",
				$date_range['start'],
				$date_range['end']
			);
		}

		return $wpdb->get_results( "SELECT * FROM {$table} {$where_clause}", ARRAY_A );
	}

	/**
	 * Convert array to CSV format.
	 *
	 * @since    1.0.0
	 * @param    array $data    Data to convert.
	 * @return   string        CSV content.
	 */
	private function array_to_csv( $data ) {
		if ( empty( $data ) ) {
			return '';
		}

		$output = fopen( 'php://temp', 'r+' );
		
		// Add headers
		fputcsv( $output, array_keys( $data[0] ) );
		
		// Add data rows
		foreach ( $data as $row ) {
			fputcsv( $output, $row );
		}

		rewind( $output );
		$csv_content = stream_get_contents( $output );
		fclose( $output );

		return $csv_content;
	}

	/**
	 * Send export notification to user.
	 *
	 * @since    1.0.0
	 * @param    int    $user_id     User ID.
	 * @param    string $filename    Export filename.
	 * @return   void
	 */
	private function send_export_notification( $user_id, $filename ) {
		$this->send_notification(
			$user_id,
			'export_ready',
			array(
				'title' => __( 'Data Export Ready', 'skylearn-billing-pro' ),
				'message' => sprintf( 
					__( 'Your data export "%s" is ready for download.', 'skylearn-billing-pro' ), 
					$filename 
				),
				'data' => array( 'filename' => $filename ),
			)
		);
	}

	/**
	 * Sync Lemon Squeezy data.
	 *
	 * @since    1.0.0
	 * @param    array $task_data    Task data.
	 * @return   bool               True on success, false on failure.
	 */
	private function sync_lemon_squeezy_data( $task_data ) {
		// This would integrate with Lemon Squeezy API
		// For now, return true as a placeholder
		return true;
	}

	/**
	 * Sync LearnDash data.
	 *
	 * @since    1.0.0
	 * @param    array $task_data    Task data.
	 * @return   bool               True on success, false on failure.
	 */
	private function sync_learndash_data( $task_data ) {
		// This would integrate with LearnDash data
		// For now, return true as a placeholder
		return true;
	}

	/**
	 * Get queue statistics.
	 *
	 * @since    1.0.0
	 * @return   array    Queue statistics.
	 */
	public function get_queue_stats() {
		global $wpdb;

		return array(
			'pending' => $wpdb->get_var( "SELECT COUNT(*) FROM {$this->queue_table} WHERE status = 'pending'" ),
			'processing' => $wpdb->get_var( "SELECT COUNT(*) FROM {$this->queue_table} WHERE status = 'processing'" ),
			'completed' => $wpdb->get_var( "SELECT COUNT(*) FROM {$this->queue_table} WHERE status = 'completed'" ),
			'failed' => $wpdb->get_var( "SELECT COUNT(*) FROM {$this->queue_table} WHERE status = 'failed'" ),
		);
	}

	/**
	 * Clear completed tasks older than specified days.
	 *
	 * @since    1.0.0
	 * @param    int $days    Number of days to retain completed tasks.
	 * @return   int         Number of tasks cleared.
	 */
	public function clear_old_tasks( $days = 7 ) {
		global $wpdb;

		$cleanup_date = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		return $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->queue_table} 
				WHERE status IN ('completed', 'failed') 
				AND completed_at < %s",
				$cleanup_date
			)
		);
	}
}