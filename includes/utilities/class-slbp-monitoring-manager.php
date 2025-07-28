<?php
/**
 * Monitoring and alerting system for SkyLearn Billing Pro
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/utilities
 */

/**
 * Monitoring and alerting manager.
 *
 * Handles system monitoring, metrics collection, and alerting.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/utilities
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Monitoring_Manager {

	/**
	 * Metrics storage table name.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $metrics_table    Metrics table name.
	 */
	private $metrics_table;

	/**
	 * Alerts configuration.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $alert_config    Alert configuration.
	 */
	private $alert_config;

	/**
	 * Metrics collection interval.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      int    $collection_interval    Collection interval in seconds.
	 */
	private $collection_interval = 300; // 5 minutes

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		global $wpdb;
		$this->metrics_table = $wpdb->prefix . 'slbp_metrics';
		$this->alert_config = $this->get_alert_config();

		// Create metrics table if it doesn't exist
		$this->maybe_create_metrics_table();

		// Hook into WordPress actions
		add_action( 'slbp_collect_metrics', array( $this, 'collect_metrics' ) );
		add_action( 'slbp_check_alerts', array( $this, 'check_alerts' ) );
		
		// Register REST API endpoints
		add_action( 'rest_api_init', array( $this, 'register_monitoring_endpoints' ) );

		// Schedule metrics collection
		$this->schedule_metrics_collection();
	}

	/**
	 * Register monitoring REST API endpoints.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function register_monitoring_endpoints() {
		// Metrics endpoint (Prometheus format)
		register_rest_route( 'skylearn-billing-pro/v1', '/metrics', array(
			'methods' => 'GET',
			'callback' => array( $this, 'get_prometheus_metrics' ),
			'permission_callback' => array( $this, 'check_monitoring_permissions' ),
		) );

		// Dashboard metrics endpoint
		register_rest_route( 'skylearn-billing-pro/v1', '/metrics/dashboard', array(
			'methods' => 'GET',
			'callback' => array( $this, 'get_dashboard_metrics' ),
			'permission_callback' => array( $this, 'check_monitoring_permissions' ),
		) );

		// Alerts endpoint
		register_rest_route( 'skylearn-billing-pro/v1', '/alerts', array(
			'methods' => 'GET',
			'callback' => array( $this, 'get_alerts' ),
			'permission_callback' => array( $this, 'check_monitoring_permissions' ),
		) );

		// Alert configuration endpoint
		register_rest_route( 'skylearn-billing-pro/v1', '/alerts/config', array(
			'methods' => array( 'GET', 'POST' ),
			'callback' => array( $this, 'manage_alert_config' ),
			'permission_callback' => array( $this, 'check_admin_permissions' ),
		) );
	}

	/**
	 * Collect system metrics.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function collect_metrics() {
		$metrics = array(
			'system' => $this->collect_system_metrics(),
			'database' => $this->collect_database_metrics(),
			'performance' => $this->collect_performance_metrics(),
			'business' => $this->collect_business_metrics(),
		);

		foreach ( $metrics as $category => $category_metrics ) {
			foreach ( $category_metrics as $metric_name => $metric_data ) {
				$this->store_metric( $category, $metric_name, $metric_data );
			}
		}

		// Check alerts after collecting metrics
		wp_schedule_single_event( time() + 60, 'slbp_check_alerts' );
	}

	/**
	 * Check for alert conditions.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function check_alerts() {
		foreach ( $this->alert_config as $alert_name => $config ) {
			if ( ! $config['enabled'] ) {
				continue;
			}

			$current_value = $this->get_current_metric_value( $config['metric'] );
			$threshold_exceeded = false;

			switch ( $config['condition'] ) {
				case 'greater_than':
					$threshold_exceeded = $current_value > $config['threshold'];
					break;

				case 'less_than':
					$threshold_exceeded = $current_value < $config['threshold'];
					break;

				case 'equals':
					$threshold_exceeded = $current_value == $config['threshold'];
					break;

				case 'not_equals':
					$threshold_exceeded = $current_value != $config['threshold'];
					break;
			}

			if ( $threshold_exceeded ) {
				$this->trigger_alert( $alert_name, $config, $current_value );
			}
		}
	}

	/**
	 * Get Prometheus-formatted metrics.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    REST request object.
	 * @return   WP_REST_Response           Prometheus metrics response.
	 */
	public function get_prometheus_metrics( $request ) {
		$metrics = $this->get_latest_metrics();
		$prometheus_output = $this->format_prometheus_metrics( $metrics );

		$response = new WP_REST_Response( $prometheus_output );
		$response->set_headers( array( 'Content-Type' => 'text/plain; charset=utf-8' ) );
		
		return $response;
	}

	/**
	 * Get dashboard metrics.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    REST request object.
	 * @return   WP_REST_Response           Dashboard metrics response.
	 */
	public function get_dashboard_metrics( $request ) {
		$timeframe = $request->get_param( 'timeframe' ) ?? '1h';
		$metrics = $this->get_metrics_for_timeframe( $timeframe );

		return new WP_REST_Response( array(
			'timeframe' => $timeframe,
			'metrics' => $metrics,
			'summary' => $this->get_metrics_summary( $metrics ),
			'alerts' => $this->get_active_alerts(),
		) );
	}

	/**
	 * Get alerts.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    REST request object.
	 * @return   WP_REST_Response           Alerts response.
	 */
	public function get_alerts( $request ) {
		$status = $request->get_param( 'status' ) ?? 'active';
		$alerts = $this->get_alerts_by_status( $status );

		return new WP_REST_Response( array(
			'alerts' => $alerts,
			'total' => count( $alerts ),
			'status' => $status,
		) );
	}

	/**
	 * Manage alert configuration.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    REST request object.
	 * @return   WP_REST_Response           Alert config response.
	 */
	public function manage_alert_config( $request ) {
		if ( $request->get_method() === 'POST' ) {
			$new_config = $request->get_json_params();
			
			if ( $this->validate_alert_config( $new_config ) ) {
				update_option( 'slbp_alert_config', $new_config );
				$this->alert_config = $new_config;
				
				return new WP_REST_Response( array(
					'success' => true,
					'message' => 'Alert configuration updated successfully',
					'config' => $new_config,
				) );
			} else {
				return new WP_REST_Response( array(
					'success' => false,
					'message' => 'Invalid alert configuration',
				), 400 );
			}
		}

		return new WP_REST_Response( array(
			'config' => $this->alert_config,
		) );
	}

	/**
	 * Check monitoring permissions.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    REST request object.
	 * @return   bool                       True if authorized, false otherwise.
	 */
	public function check_monitoring_permissions( $request ) {
		// Allow monitoring systems access
		$monitoring_ips = get_option( 'slbp_monitoring_ips', array() );
		$client_ip = $this->get_client_ip();

		if ( ! empty( $monitoring_ips ) && in_array( $client_ip, $monitoring_ips, true ) ) {
			return true;
		}

		// Fallback to user capabilities
		return current_user_can( 'manage_options' );
	}

	/**
	 * Check admin permissions.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    REST request object.
	 * @return   bool                       True if authorized, false otherwise.
	 */
	public function check_admin_permissions( $request ) {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Collect system metrics.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   array    System metrics.
	 */
	private function collect_system_metrics() {
		return array(
			'memory_usage' => array(
				'value' => memory_get_usage( true ),
				'unit' => 'bytes',
			),
			'memory_peak' => array(
				'value' => memory_get_peak_usage( true ),
				'unit' => 'bytes',
			),
			'memory_limit' => array(
				'value' => $this->convert_to_bytes( ini_get( 'memory_limit' ) ),
				'unit' => 'bytes',
			),
			'cpu_load' => array(
				'value' => $this->get_cpu_load(),
				'unit' => 'percent',
			),
			'disk_usage' => array(
				'value' => $this->get_disk_usage(),
				'unit' => 'percent',
			),
		);
	}

	/**
	 * Collect database metrics.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   array    Database metrics.
	 */
	private function collect_database_metrics() {
		global $wpdb;

		// Get database size
		$db_size = $wpdb->get_var(
			"SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'size_mb' 
			FROM information_schema.tables 
			WHERE table_schema = '{$wpdb->dbname}'"
		);

		// Get connection count
		$connections = $wpdb->get_var( "SHOW STATUS LIKE 'Threads_connected'" );
		$connection_count = is_array( $connections ) ? (int) $connections['Value'] : 0;

		// Get slow query count
		$slow_queries = $wpdb->get_var( "SHOW STATUS LIKE 'Slow_queries'" );
		$slow_query_count = is_array( $slow_queries ) ? (int) $slow_queries['Value'] : 0;

		return array(
			'database_size' => array(
				'value' => $db_size ?? 0,
				'unit' => 'megabytes',
			),
			'connections' => array(
				'value' => $connection_count,
				'unit' => 'count',
			),
			'slow_queries' => array(
				'value' => $slow_query_count,
				'unit' => 'count',
			),
			'query_count' => array(
				'value' => get_num_queries(),
				'unit' => 'count',
			),
		);
	}

	/**
	 * Collect performance metrics.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   array    Performance metrics.
	 */
	private function collect_performance_metrics() {
		$response_time = 0;
		if ( defined( 'WP_START_TIMESTAMP' ) ) {
			$response_time = ( microtime( true ) - WP_START_TIMESTAMP ) * 1000;
		}

		return array(
			'response_time' => array(
				'value' => $response_time,
				'unit' => 'milliseconds',
			),
			'cache_hit_ratio' => array(
				'value' => $this->get_cache_hit_ratio(),
				'unit' => 'percent',
			),
			'opcache_hit_ratio' => array(
				'value' => $this->get_opcache_hit_ratio(),
				'unit' => 'percent',
			),
		);
	}

	/**
	 * Collect business metrics.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   array    Business metrics.
	 */
	private function collect_business_metrics() {
		global $wpdb;

		$transactions_table = $wpdb->prefix . 'slbp_transactions';
		$subscriptions_table = $wpdb->prefix . 'slbp_subscriptions';

		// Transactions in last 24 hours
		$recent_transactions = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$transactions_table} 
				WHERE created_at >= %s",
				date( 'Y-m-d H:i:s', strtotime( '-24 hours' ) )
			)
		);

		// Active subscriptions
		$active_subscriptions = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$subscriptions_table} 
			WHERE status = 'active'"
		);

		// Failed transactions in last hour
		$failed_transactions = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$transactions_table} 
				WHERE status = 'failed' 
				AND created_at >= %s",
				date( 'Y-m-d H:i:s', strtotime( '-1 hour' ) )
			)
		);

		return array(
			'transactions_24h' => array(
				'value' => (int) $recent_transactions,
				'unit' => 'count',
			),
			'active_subscriptions' => array(
				'value' => (int) $active_subscriptions,
				'unit' => 'count',
			),
			'failed_transactions_1h' => array(
				'value' => (int) $failed_transactions,
				'unit' => 'count',
			),
		);
	}

	/**
	 * Store metric in database.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $category      Metric category.
	 * @param    string $metric_name   Metric name.
	 * @param    array  $metric_data   Metric data.
	 * @return   bool                 True on success, false on failure.
	 */
	private function store_metric( $category, $metric_name, $metric_data ) {
		global $wpdb;

		return $wpdb->insert(
			$this->metrics_table,
			array(
				'category' => $category,
				'metric_name' => $metric_name,
				'metric_value' => $metric_data['value'],
				'metric_unit' => $metric_data['unit'],
				'collected_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%f', '%s', '%s' )
		) !== false;
	}

	/**
	 * Get current metric value.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $metric_key    Metric key (category.metric_name).
	 * @return   float|null           Current metric value or null if not found.
	 */
	private function get_current_metric_value( $metric_key ) {
		global $wpdb;

		list( $category, $metric_name ) = explode( '.', $metric_key, 2 );

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT metric_value FROM {$this->metrics_table} 
				WHERE category = %s 
				AND metric_name = %s 
				ORDER BY collected_at DESC 
				LIMIT 1",
				$category,
				$metric_name
			)
		);
	}

	/**
	 * Trigger alert.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $alert_name     Alert name.
	 * @param    array  $config        Alert configuration.
	 * @param    float  $current_value Current metric value.
	 * @return   void
	 */
	private function trigger_alert( $alert_name, $config, $current_value ) {
		// Check if alert was recently triggered to avoid spam
		$last_triggered = get_transient( "slbp_alert_{$alert_name}_last_triggered" );
		
		if ( $last_triggered && ( time() - $last_triggered ) < $config['cooldown'] ) {
			return;
		}

		// Store alert in database
		$this->store_alert( $alert_name, $config, $current_value );

		// Send notifications
		$this->send_alert_notifications( $alert_name, $config, $current_value );

		// Set cooldown
		set_transient( "slbp_alert_{$alert_name}_last_triggered", time(), $config['cooldown'] );
	}

	/**
	 * Store alert in database.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $alert_name     Alert name.
	 * @param    array  $config        Alert configuration.
	 * @param    float  $current_value Current metric value.
	 * @return   void
	 */
	private function store_alert( $alert_name, $config, $current_value ) {
		global $wpdb;

		$alerts_table = $wpdb->prefix . 'slbp_alerts';

		$wpdb->insert(
			$alerts_table,
			array(
				'alert_name' => $alert_name,
				'metric_name' => $config['metric'],
				'threshold_value' => $config['threshold'],
				'current_value' => $current_value,
				'severity' => $config['severity'],
				'status' => 'active',
				'message' => $config['message'],
				'triggered_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Send alert notifications.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $alert_name     Alert name.
	 * @param    array  $config        Alert configuration.
	 * @param    float  $current_value Current metric value.
	 * @return   void
	 */
	private function send_alert_notifications( $alert_name, $config, $current_value ) {
		$message = sprintf(
			$config['message'],
			$current_value,
			$config['threshold']
		);

		// Email notification
		if ( ! empty( $config['email_recipients'] ) ) {
			$this->send_email_alert( $config['email_recipients'], $alert_name, $message, $config['severity'] );
		}

		// Webhook notification
		if ( ! empty( $config['webhook_url'] ) ) {
			$this->send_webhook_alert( $config['webhook_url'], $alert_name, $message, $current_value, $config );
		}

		// Slack notification
		if ( ! empty( $config['slack_webhook'] ) ) {
			$this->send_slack_alert( $config['slack_webhook'], $alert_name, $message, $config['severity'] );
		}
	}

	/**
	 * Send email alert.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array  $recipients    Email recipients.
	 * @param    string $alert_name    Alert name.
	 * @param    string $message       Alert message.
	 * @param    string $severity      Alert severity.
	 * @return   void
	 */
	private function send_email_alert( $recipients, $alert_name, $message, $severity ) {
		$subject = sprintf(
			'[%s] SkyLearn Billing Pro Alert: %s',
			strtoupper( $severity ),
			$alert_name
		);

		$body = "Alert Details:\n\n";
		$body .= "Alert Name: {$alert_name}\n";
		$body .= "Severity: {$severity}\n";
		$body .= "Message: {$message}\n";
		$body .= "Time: " . current_time( 'c' ) . "\n";
		$body .= "Instance: " . get_option( 'slbp_instance_id', 'unknown' ) . "\n";

		foreach ( $recipients as $recipient ) {
			wp_mail( $recipient, $subject, $body );
		}
	}

	/**
	 * Send webhook alert.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $webhook_url    Webhook URL.
	 * @param    string $alert_name     Alert name.
	 * @param    string $message        Alert message.
	 * @param    float  $current_value  Current metric value.
	 * @param    array  $config         Alert configuration.
	 * @return   void
	 */
	private function send_webhook_alert( $webhook_url, $alert_name, $message, $current_value, $config ) {
		$payload = array(
			'alert_name' => $alert_name,
			'message' => $message,
			'severity' => $config['severity'],
			'metric' => $config['metric'],
			'current_value' => $current_value,
			'threshold' => $config['threshold'],
			'timestamp' => current_time( 'c' ),
			'instance_id' => get_option( 'slbp_instance_id', 'unknown' ),
		);

		wp_remote_post( $webhook_url, array(
			'body' => wp_json_encode( $payload ),
			'headers' => array( 'Content-Type' => 'application/json' ),
			'timeout' => 10,
		) );
	}

	/**
	 * Send Slack alert.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $slack_webhook  Slack webhook URL.
	 * @param    string $alert_name     Alert name.
	 * @param    string $message        Alert message.
	 * @param    string $severity       Alert severity.
	 * @return   void
	 */
	private function send_slack_alert( $slack_webhook, $alert_name, $message, $severity ) {
		$color_map = array(
			'info' => '#36a64f',
			'warning' => '#ffaa00',
			'error' => '#ff0000',
			'critical' => '#800000',
		);

		$payload = array(
			'attachments' => array(
				array(
					'color' => $color_map[ $severity ] ?? '#cccccc',
					'title' => 'SkyLearn Billing Pro Alert',
					'fields' => array(
						array(
							'title' => 'Alert',
							'value' => $alert_name,
							'short' => true,
						),
						array(
							'title' => 'Severity',
							'value' => strtoupper( $severity ),
							'short' => true,
						),
						array(
							'title' => 'Message',
							'value' => $message,
							'short' => false,
						),
					),
					'timestamp' => time(),
				),
			),
		);

		wp_remote_post( $slack_webhook, array(
			'body' => wp_json_encode( $payload ),
			'headers' => array( 'Content-Type' => 'application/json' ),
			'timeout' => 10,
		) );
	}

	/**
	 * Get alert configuration.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   array    Alert configuration.
	 */
	private function get_alert_config() {
		$default_config = array(
			'high_memory_usage' => array(
				'enabled' => true,
				'metric' => 'system.memory_usage',
				'condition' => 'greater_than',
				'threshold' => 536870912, // 512MB
				'severity' => 'warning',
				'message' => 'High memory usage detected: %.2f bytes (threshold: %.2f bytes)',
				'cooldown' => 300, // 5 minutes
				'email_recipients' => array(),
				'webhook_url' => '',
				'slack_webhook' => '',
			),
			'database_connection_failure' => array(
				'enabled' => true,
				'metric' => 'database.connections',
				'condition' => 'equals',
				'threshold' => 0,
				'severity' => 'critical',
				'message' => 'Database connection failure detected',
				'cooldown' => 60, // 1 minute
				'email_recipients' => array(),
				'webhook_url' => '',
				'slack_webhook' => '',
			),
			'high_failed_transactions' => array(
				'enabled' => true,
				'metric' => 'business.failed_transactions_1h',
				'condition' => 'greater_than',
				'threshold' => 10,
				'severity' => 'error',
				'message' => 'High number of failed transactions: %.0f in the last hour (threshold: %.0f)',
				'cooldown' => 600, // 10 minutes
				'email_recipients' => array(),
				'webhook_url' => '',
				'slack_webhook' => '',
			),
		);

		return get_option( 'slbp_alert_config', $default_config );
	}

	/**
	 * Schedule metrics collection.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   void
	 */
	private function schedule_metrics_collection() {
		if ( ! wp_next_scheduled( 'slbp_collect_metrics' ) ) {
			wp_schedule_event( time(), 'slbp_metrics_interval', 'slbp_collect_metrics' );
		}

		// Add custom cron interval
		add_filter( 'cron_schedules', array( $this, 'add_metrics_cron_interval' ) );
	}

	/**
	 * Add custom cron interval for metrics collection.
	 *
	 * @since    1.0.0
	 * @param    array $schedules    Existing cron schedules.
	 * @return   array              Modified cron schedules.
	 */
	public function add_metrics_cron_interval( $schedules ) {
		$schedules['slbp_metrics_interval'] = array(
			'interval' => $this->collection_interval,
			'display' => __( 'Every 5 Minutes (SLBP Metrics)', 'skylearn-billing-pro' ),
		);

		return $schedules;
	}

	/**
	 * Create metrics table if it doesn't exist.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   void
	 */
	private function maybe_create_metrics_table() {
		global $wpdb;

		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$this->metrics_table}'" );

		if ( $table_exists !== $this->metrics_table ) {
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE {$this->metrics_table} (
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

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}

		// Create alerts table
		$alerts_table = $wpdb->prefix . 'slbp_alerts';
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$alerts_table}'" );

		if ( $table_exists !== $alerts_table ) {
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE {$alerts_table} (
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

			dbDelta( $sql );
		}
	}

	/**
	 * Get latest metrics.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   array    Latest metrics.
	 */
	private function get_latest_metrics() {
		global $wpdb;

		return $wpdb->get_results(
			"SELECT category, metric_name, metric_value, metric_unit, collected_at 
			FROM {$this->metrics_table} m1
			WHERE collected_at = (
				SELECT MAX(collected_at) 
				FROM {$this->metrics_table} m2 
				WHERE m2.category = m1.category 
				AND m2.metric_name = m1.metric_name
			)",
			ARRAY_A
		);
	}

	/**
	 * Format metrics for Prometheus.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array $metrics    Metrics data.
	 * @return   string           Prometheus formatted metrics.
	 */
	private function format_prometheus_metrics( $metrics ) {
		$output = '';

		foreach ( $metrics as $metric ) {
			$metric_name = 'slbp_' . $metric['category'] . '_' . $metric['metric_name'];
			$metric_name = str_replace( array( '-', ' ' ), '_', $metric_name );

			$output .= "# TYPE {$metric_name} gauge\n";
			$output .= "{$metric_name} {$metric['metric_value']}\n";
		}

		return $output;
	}

	/**
	 * Get metrics for timeframe.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $timeframe    Timeframe (1h, 6h, 24h, 7d).
	 * @return   array               Metrics data.
	 */
	private function get_metrics_for_timeframe( $timeframe ) {
		global $wpdb;

		$hours_map = array(
			'1h' => 1,
			'6h' => 6,
			'24h' => 24,
			'7d' => 168,
		);

		$hours = $hours_map[ $timeframe ] ?? 1;
		$start_time = date( 'Y-m-d H:i:s', strtotime( "-{$hours} hours" ) );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT category, metric_name, metric_value, metric_unit, collected_at 
				FROM {$this->metrics_table} 
				WHERE collected_at >= %s 
				ORDER BY collected_at DESC",
				$start_time
			),
			ARRAY_A
		);
	}

	/**
	 * Get metrics summary.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array $metrics    Metrics data.
	 * @return   array            Metrics summary.
	 */
	private function get_metrics_summary( $metrics ) {
		$summary = array();

		foreach ( $metrics as $metric ) {
			$key = $metric['category'] . '.' . $metric['metric_name'];
			
			if ( ! isset( $summary[ $key ] ) ) {
				$summary[ $key ] = array(
					'count' => 0,
					'sum' => 0,
					'min' => PHP_FLOAT_MAX,
					'max' => PHP_FLOAT_MIN,
					'unit' => $metric['metric_unit'],
				);
			}

			$value = (float) $metric['metric_value'];
			$summary[ $key ]['count']++;
			$summary[ $key ]['sum'] += $value;
			$summary[ $key ]['min'] = min( $summary[ $key ]['min'], $value );
			$summary[ $key ]['max'] = max( $summary[ $key ]['max'], $value );
			$summary[ $key ]['avg'] = $summary[ $key ]['sum'] / $summary[ $key ]['count'];
		}

		return $summary;
	}

	/**
	 * Get active alerts.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   array    Active alerts.
	 */
	private function get_active_alerts() {
		global $wpdb;

		$alerts_table = $wpdb->prefix . 'slbp_alerts';

		return $wpdb->get_results(
			"SELECT * FROM {$alerts_table} 
			WHERE status = 'active' 
			ORDER BY triggered_at DESC 
			LIMIT 10",
			ARRAY_A
		);
	}

	/**
	 * Get alerts by status.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $status    Alert status.
	 * @return   array            Alerts.
	 */
	private function get_alerts_by_status( $status ) {
		global $wpdb;

		$alerts_table = $wpdb->prefix . 'slbp_alerts';

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$alerts_table} 
				WHERE status = %s 
				ORDER BY triggered_at DESC 
				LIMIT 50",
				$status
			),
			ARRAY_A
		);
	}

	/**
	 * Validate alert configuration.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array $config    Alert configuration.
	 * @return   bool            True if valid, false otherwise.
	 */
	private function validate_alert_config( $config ) {
		if ( ! is_array( $config ) ) {
			return false;
		}

		$required_fields = array( 'enabled', 'metric', 'condition', 'threshold', 'severity' );

		foreach ( $config as $alert_name => $alert_config ) {
			foreach ( $required_fields as $field ) {
				if ( ! isset( $alert_config[ $field ] ) ) {
					return false;
				}
			}

			if ( ! in_array( $alert_config['condition'], array( 'greater_than', 'less_than', 'equals', 'not_equals' ), true ) ) {
				return false;
			}

			if ( ! in_array( $alert_config['severity'], array( 'info', 'warning', 'error', 'critical' ), true ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Convert memory size to bytes.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $size    Memory size (e.g., "128M").
	 * @return   int            Size in bytes.
	 */
	private function convert_to_bytes( $size ) {
		$size = trim( $size );
		$last = strtolower( $size[ strlen( $size ) - 1 ] );
		$size = (int) $size;

		switch ( $last ) {
			case 'g':
				$size *= 1024;
				// Fall through.
			case 'm':
				$size *= 1024;
				// Fall through.
			case 'k':
				$size *= 1024;
		}

		return $size;
	}

	/**
	 * Get CPU load.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   float    CPU load percentage.
	 */
	private function get_cpu_load() {
		if ( function_exists( 'sys_getloadavg' ) ) {
			$load = sys_getloadavg();
			return $load[0] ?? 0;
		}

		return 0;
	}

	/**
	 * Get disk usage.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   float    Disk usage percentage.
	 */
	private function get_disk_usage() {
		$upload_dir = wp_upload_dir();
		$total_space = disk_total_space( $upload_dir['basedir'] );
		$free_space = disk_free_space( $upload_dir['basedir'] );

		if ( $total_space && $free_space ) {
			return ( ( $total_space - $free_space ) / $total_space ) * 100;
		}

		return 0;
	}

	/**
	 * Get cache hit ratio.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   float    Cache hit ratio percentage.
	 */
	private function get_cache_hit_ratio() {
		// This would require implementing cache statistics tracking
		// For now, return a placeholder value
		return 85.0;
	}

	/**
	 * Get OPcache hit ratio.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   float    OPcache hit ratio percentage.
	 */
	private function get_opcache_hit_ratio() {
		if ( function_exists( 'opcache_get_status' ) ) {
			$status = opcache_get_status( false );
			if ( isset( $status['opcache_statistics'] ) ) {
				$stats = $status['opcache_statistics'];
				$hits = $stats['hits'];
				$misses = $stats['misses'];
				$total = $hits + $misses;

				return $total > 0 ? ( $hits / $total ) * 100 : 0;
			}
		}

		return 0;
	}

	/**
	 * Get client IP address.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   string    Client IP address.
	 */
	private function get_client_ip() {
		$ip_headers = array(
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		);

		foreach ( $ip_headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = $_SERVER[ $header ];
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				return $ip;
			}
		}

		return '127.0.0.1';
	}
}