<?php
/**
 * Advanced reporting functionality for the plugin.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/reporting
 */

/**
 * Advanced reporting functionality for the plugin.
 *
 * Provides comprehensive reporting capabilities with customizable reports,
 * scheduled exports, and real-time dashboard widgets.
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/reporting
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Advanced_Reports {

	/**
	 * The analytics instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SLBP_Analytics    $analytics    The analytics instance.
	 */
	private $analytics;

	/**
	 * The audit logger instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SLBP_Audit_Logger    $audit_logger    The audit logger instance.
	 */
	private $audit_logger;

	/**
	 * Available report types.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $report_types    Available report types.
	 */
	private $report_types;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->analytics = new SLBP_Analytics();
		$this->audit_logger = new SLBP_Audit_Logger();
		$this->init_report_types();
		$this->init_hooks();
	}

	/**
	 * Initialize available report types.
	 *
	 * @since    1.0.0
	 */
	private function init_report_types() {
		$this->report_types = array(
			'revenue' => array(
				'name' => __( 'Revenue Report', 'skylearn-billing-pro' ),
				'description' => __( 'Detailed revenue analytics and trends', 'skylearn-billing-pro' ),
				'fields' => array( 'date_range', 'currency', 'gateway', 'product_id' ),
				'formats' => array( 'csv', 'pdf', 'json' ),
			),
			'subscriptions' => array(
				'name' => __( 'Subscription Report', 'skylearn-billing-pro' ),
				'description' => __( 'Subscription metrics and lifecycle analysis', 'skylearn-billing-pro' ),
				'fields' => array( 'date_range', 'status', 'plan_type', 'churn_analysis' ),
				'formats' => array( 'csv', 'pdf', 'json' ),
			),
			'user_activity' => array(
				'name' => __( 'User Activity Report', 'skylearn-billing-pro' ),
				'description' => __( 'User engagement and activity patterns', 'skylearn-billing-pro' ),
				'fields' => array( 'date_range', 'user_role', 'activity_type', 'course_id' ),
				'formats' => array( 'csv', 'json' ),
			),
			'course_performance' => array(
				'name' => __( 'Course Performance Report', 'skylearn-billing-pro' ),
				'description' => __( 'Course enrollment, completion, and revenue metrics', 'skylearn-billing-pro' ),
				'fields' => array( 'date_range', 'course_id', 'completion_rate', 'revenue_per_course' ),
				'formats' => array( 'csv', 'pdf', 'json' ),
			),
			'refunds' => array(
				'name' => __( 'Refund Report', 'skylearn-billing-pro' ),
				'description' => __( 'Refund analysis and trends', 'skylearn-billing-pro' ),
				'fields' => array( 'date_range', 'refund_reason', 'gateway', 'amount_range' ),
				'formats' => array( 'csv', 'json' ),
			),
			'compliance' => array(
				'name' => __( 'Compliance Report', 'skylearn-billing-pro' ),
				'description' => __( 'Data handling and privacy compliance metrics', 'skylearn-billing-pro' ),
				'fields' => array( 'date_range', 'event_type', 'severity', 'user_requests' ),
				'formats' => array( 'csv', 'pdf' ),
			),
		);

		// Allow custom report types via filter
		$this->report_types = apply_filters( 'slbp_report_types', $this->report_types );
	}

	/**
	 * Initialize hooks for advanced reporting.
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		// AJAX handlers
		add_action( 'wp_ajax_slbp_generate_report', array( $this, 'ajax_generate_report' ) );
		add_action( 'wp_ajax_slbp_schedule_report', array( $this, 'ajax_schedule_report' ) );
		add_action( 'wp_ajax_slbp_get_report_data', array( $this, 'ajax_get_report_data' ) );
		add_action( 'wp_ajax_slbp_delete_scheduled_report', array( $this, 'ajax_delete_scheduled_report' ) );

		// Scheduled report hooks
		add_action( 'slbp_send_scheduled_report', array( $this, 'send_scheduled_report' ), 10, 2 );
		add_action( 'init', array( $this, 'schedule_report_crons' ) );

		// Dashboard widgets
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widgets' ) );
	}

	/**
	 * Generate a custom report.
	 *
	 * @since    1.0.0
	 * @param    string    $report_type    Type of report to generate.
	 * @param    array     $filters       Report filters and parameters.
	 * @param    string    $format        Output format (csv, pdf, json).
	 * @return   array|WP_Error          Report data or error.
	 */
	public function generate_report( $report_type, $filters = array(), $format = 'json' ) {
		if ( ! isset( $this->report_types[ $report_type ] ) ) {
			return new WP_Error( 'invalid_report_type', __( 'Invalid report type specified.', 'skylearn-billing-pro' ) );
		}

		$report_config = $this->report_types[ $report_type ];

		// Validate filters against allowed fields
		$validated_filters = $this->validate_report_filters( $filters, $report_config['fields'] );

		// Generate report data based on type
		switch ( $report_type ) {
			case 'revenue':
				$data = $this->generate_revenue_report( $validated_filters );
				break;
			case 'subscriptions':
				$data = $this->generate_subscription_report( $validated_filters );
				break;
			case 'user_activity':
				$data = $this->generate_user_activity_report( $validated_filters );
				break;
			case 'course_performance':
				$data = $this->generate_course_performance_report( $validated_filters );
				break;
			case 'refunds':
				$data = $this->generate_refund_report( $validated_filters );
				break;
			case 'compliance':
				$data = $this->generate_compliance_report( $validated_filters );
				break;
			default:
				$data = apply_filters( "slbp_generate_{$report_type}_report", array(), $validated_filters );
		}

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		// Format the output
		$formatted_data = $this->format_report_output( $data, $format, $report_type );

		// Log report generation
		$this->audit_logger->log_event(
			'admin',
			'report_generated',
			get_current_user_id(),
			array(
				'report_type' => $report_type,
				'format' => $format,
				'filters' => $validated_filters,
				'records_count' => is_array( $data ) ? count( $data ) : 0,
			),
			'info'
		);

		return $formatted_data;
	}

	/**
	 * Schedule a recurring report.
	 *
	 * @since    1.0.0
	 * @param    array    $schedule_config    Schedule configuration.
	 * @return   bool|WP_Error              Success status or error.
	 */
	public function schedule_report( $schedule_config ) {
		$defaults = array(
			'report_type' => '',
			'filters' => array(),
			'format' => 'csv',
			'frequency' => 'weekly',
			'recipients' => array(),
			'subject' => '',
			'enabled' => true,
		);

		$config = wp_parse_args( $schedule_config, $defaults );

		// Validate required fields
		if ( empty( $config['report_type'] ) || empty( $config['recipients'] ) ) {
			return new WP_Error( 'missing_required_fields', __( 'Report type and recipients are required.', 'skylearn-billing-pro' ) );
		}

		if ( ! isset( $this->report_types[ $config['report_type'] ] ) ) {
			return new WP_Error( 'invalid_report_type', __( 'Invalid report type specified.', 'skylearn-billing-pro' ) );
		}

		// Generate unique ID for this scheduled report
		$schedule_id = uniqid( 'slbp_report_' );

		// Save schedule configuration
		$scheduled_reports = get_option( 'slbp_scheduled_reports', array() );
		$scheduled_reports[ $schedule_id ] = array(
			'config' => $config,
			'created_at' => current_time( 'mysql' ),
			'created_by' => get_current_user_id(),
			'last_sent' => '',
			'next_send' => $this->calculate_next_send_time( $config['frequency'] ),
		);

		update_option( 'slbp_scheduled_reports', $scheduled_reports );

		// Schedule the WP Cron event
		$this->schedule_wp_cron( $schedule_id, $config['frequency'] );

		// Log the scheduling
		$this->audit_logger->log_event(
			'admin',
			'report_scheduled',
			get_current_user_id(),
			array(
				'schedule_id' => $schedule_id,
				'report_type' => $config['report_type'],
				'frequency' => $config['frequency'],
				'recipients' => $config['recipients'],
			),
			'info'
		);

		return $schedule_id;
	}

	/**
	 * Generate revenue report data.
	 *
	 * @since    1.0.0
	 * @param    array    $filters    Report filters.
	 * @return   array               Revenue report data.
	 */
	private function generate_revenue_report( $filters ) {
		global $wpdb;
		$transactions_table = $wpdb->prefix . 'slbp_transactions';

		// Build query conditions
		$where_conditions = array( "status = 'completed'" );
		$where_values = array();

		if ( ! empty( $filters['date_range'] ) ) {
			$date_range = $this->parse_date_range( $filters['date_range'] );
			$where_conditions[] = 'created_at >= %s AND created_at <= %s';
			$where_values[] = $date_range['start'];
			$where_values[] = $date_range['end'];
		}

		if ( ! empty( $filters['gateway'] ) ) {
			$where_conditions[] = 'gateway = %s';
			$where_values[] = $filters['gateway'];
		}

		if ( ! empty( $filters['currency'] ) ) {
			$where_conditions[] = 'currency = %s';
			$where_values[] = $filters['currency'];
		}

		$where_clause = implode( ' AND ', $where_conditions );

		// Get transaction data
		$query = "SELECT DATE(created_at) as transaction_date, 
		                 SUM(amount) as daily_revenue,
		                 COUNT(*) as transaction_count,
		                 AVG(amount) as avg_transaction_value,
		                 gateway,
		                 currency
		          FROM {$transactions_table} 
		          WHERE {$where_clause}
		          GROUP BY DATE(created_at), gateway, currency
		          ORDER BY transaction_date DESC";

		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare( $query, $where_values );
		}

		$results = $wpdb->get_results( $query );

		// Add summary metrics
		$summary_query = "SELECT SUM(amount) as total_revenue,
		                         COUNT(*) as total_transactions,
		                         AVG(amount) as avg_transaction_value
		                  FROM {$transactions_table} 
		                  WHERE {$where_clause}";

		if ( ! empty( $where_values ) ) {
			$summary_query = $wpdb->prepare( $summary_query, $where_values );
		}

		$summary = $wpdb->get_row( $summary_query );

		return array(
			'summary' => $summary,
			'daily_breakdown' => $results,
			'filters_applied' => $filters,
			'generated_at' => current_time( 'mysql' ),
		);
	}

	/**
	 * Generate subscription report data.
	 *
	 * @since    1.0.0
	 * @param    array    $filters    Report filters.
	 * @return   array               Subscription report data.
	 */
	private function generate_subscription_report( $filters ) {
		global $wpdb;
		$subscriptions_table = $wpdb->prefix . 'slbp_subscriptions';

		// Build query conditions
		$where_conditions = array( '1=1' );
		$where_values = array();

		if ( ! empty( $filters['date_range'] ) ) {
			$date_range = $this->parse_date_range( $filters['date_range'] );
			$where_conditions[] = 'created_at >= %s AND created_at <= %s';
			$where_values[] = $date_range['start'];
			$where_values[] = $date_range['end'];
		}

		if ( ! empty( $filters['status'] ) ) {
			$where_conditions[] = 'status = %s';
			$where_values[] = $filters['status'];
		}

		$where_clause = implode( ' AND ', $where_conditions );

		// Get subscription metrics
		$query = "SELECT status,
		                 plan_name,
		                 COUNT(*) as subscription_count,
		                 SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
		                 SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
		                 DATE(created_at) as creation_date
		          FROM {$subscriptions_table} 
		          WHERE {$where_clause}
		          GROUP BY status, plan_name, DATE(created_at)
		          ORDER BY creation_date DESC";

		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare( $query, $where_values );
		}

		$results = $wpdb->get_results( $query );

		// Calculate churn rate
		$churn_data = $this->calculate_subscription_churn( $filters );

		return array(
			'subscription_breakdown' => $results,
			'churn_analysis' => $churn_data,
			'filters_applied' => $filters,
			'generated_at' => current_time( 'mysql' ),
		);
	}

	/**
	 * Generate user activity report data.
	 *
	 * @since    1.0.0
	 * @param    array    $filters    Report filters.
	 * @return   array               User activity report data.
	 */
	private function generate_user_activity_report( $filters ) {
		$activity_data = $this->audit_logger->get_logs( array(
			'event_type' => 'user',
			'start_date' => $filters['start_date'] ?? date( 'Y-m-01' ),
			'end_date' => $filters['end_date'] ?? date( 'Y-m-d' ),
			'limit' => 0, // Get all records
		) );

		// Process activity patterns
		$activity_summary = array();
		$daily_activity = array();

		foreach ( $activity_data['logs'] as $log ) {
			$date = date( 'Y-m-d', strtotime( $log->created_at ) );
			
			if ( ! isset( $daily_activity[ $date ] ) ) {
				$daily_activity[ $date ] = array(
					'login_count' => 0,
					'failed_login_count' => 0,
					'unique_users' => array(),
				);
			}

			if ( 'login' === $log->action ) {
				$daily_activity[ $date ]['login_count']++;
				$daily_activity[ $date ]['unique_users'][] = $log->user_id;
			} elseif ( 'login_failed' === $log->action ) {
				$daily_activity[ $date ]['failed_login_count']++;
			}
		}

		// Calculate unique users per day
		foreach ( $daily_activity as $date => $data ) {
			$daily_activity[ $date ]['unique_users'] = count( array_unique( $data['unique_users'] ) );
		}

		return array(
			'daily_activity' => $daily_activity,
			'total_events' => $activity_data['total'],
			'filters_applied' => $filters,
			'generated_at' => current_time( 'mysql' ),
		);
	}

	/**
	 * Generate course performance report data.
	 *
	 * @since    1.0.0
	 * @param    array    $filters    Report filters.
	 * @return   array               Course performance report data.
	 */
	private function generate_course_performance_report( $filters ) {
		// Get LearnDash courses if available
		$courses_data = array();

		if ( function_exists( 'learndash_get_posts_by_type' ) ) {
			$courses = learndash_get_posts_by_type( 'sfwd-courses', array( 'posts_per_page' => -1 ) );

			foreach ( $courses as $course ) {
				$course_id = $course->ID;
				
				// Get enrollment count
				$enrolled_users = learndash_get_users_for_course( $course_id );
				$enrollment_count = is_array( $enrolled_users ) ? count( $enrolled_users ) : 0;

				// Get completion count
				$completed_users = array();
				if ( is_array( $enrolled_users ) ) {
					foreach ( $enrolled_users as $user_id ) {
						if ( learndash_course_completed( $user_id, $course_id ) ) {
							$completed_users[] = $user_id;
						}
					}
				}

				$completion_count = count( $completed_users );
				$completion_rate = $enrollment_count > 0 ? ( $completion_count / $enrollment_count ) * 100 : 0;

				// Get revenue data for this course (mock data for now)
				$course_revenue = $this->get_course_revenue( $course_id, $filters );

				$courses_data[] = array(
					'course_id' => $course_id,
					'course_title' => $course->post_title,
					'enrollment_count' => $enrollment_count,
					'completion_count' => $completion_count,
					'completion_rate' => round( $completion_rate, 2 ),
					'revenue' => $course_revenue,
				);
			}
		}

		return array(
			'courses' => $courses_data,
			'filters_applied' => $filters,
			'generated_at' => current_time( 'mysql' ),
		);
	}

	/**
	 * Generate refund report data.
	 *
	 * @since    1.0.0
	 * @param    array    $filters    Report filters.
	 * @return   array               Refund report data.
	 */
	private function generate_refund_report( $filters ) {
		global $wpdb;
		$transactions_table = $wpdb->prefix . 'slbp_transactions';

		// Build query for refunded transactions
		$where_conditions = array( "status = 'refunded'" );
		$where_values = array();

		if ( ! empty( $filters['date_range'] ) ) {
			$date_range = $this->parse_date_range( $filters['date_range'] );
			$where_conditions[] = 'updated_at >= %s AND updated_at <= %s';
			$where_values[] = $date_range['start'];
			$where_values[] = $date_range['end'];
		}

		$where_clause = implode( ' AND ', $where_conditions );

		$query = "SELECT DATE(updated_at) as refund_date,
		                 COUNT(*) as refund_count,
		                 SUM(amount) as refund_amount,
		                 gateway,
		                 refund_reason
		          FROM {$transactions_table} 
		          WHERE {$where_clause}
		          GROUP BY DATE(updated_at), gateway, refund_reason
		          ORDER BY refund_date DESC";

		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare( $query, $where_values );
		}

		$refund_data = $wpdb->get_results( $query );

		// Calculate refund rate
		$total_transactions_query = "SELECT COUNT(*) as total_count, SUM(amount) as total_amount
		                             FROM {$transactions_table} 
		                             WHERE status IN ('completed', 'refunded')";

		$total_data = $wpdb->get_row( $total_transactions_query );
		$total_refunds = array_sum( wp_list_pluck( $refund_data, 'refund_count' ) );
		$refund_rate = $total_data->total_count > 0 ? ( $total_refunds / $total_data->total_count ) * 100 : 0;

		return array(
			'refund_breakdown' => $refund_data,
			'refund_rate' => round( $refund_rate, 2 ),
			'total_refund_amount' => array_sum( wp_list_pluck( $refund_data, 'refund_amount' ) ),
			'filters_applied' => $filters,
			'generated_at' => current_time( 'mysql' ),
		);
	}

	/**
	 * Generate compliance report data.
	 *
	 * @since    1.0.0
	 * @param    array    $filters    Report filters.
	 * @return   array               Compliance report data.
	 */
	private function generate_compliance_report( $filters ) {
		$compliance_logs = $this->audit_logger->get_logs( array(
			'event_type' => 'compliance',
			'start_date' => $filters['start_date'] ?? date( 'Y-m-01' ),
			'end_date' => $filters['end_date'] ?? date( 'Y-m-d' ),
			'limit' => 0,
		) );

		// Categorize compliance events
		$event_summary = array();
		foreach ( $compliance_logs['logs'] as $log ) {
			$action = $log->action;
			if ( ! isset( $event_summary[ $action ] ) ) {
				$event_summary[ $action ] = 0;
			}
			$event_summary[ $action ]++;
		}

		// Get data export/deletion requests
		$data_requests = get_posts( array(
			'post_type' => 'user_request',
			'post_status' => 'any',
			'posts_per_page' => -1,
			'meta_query' => array(
				array(
					'key' => '_wp_user_request_confirmed_timestamp',
					'compare' => 'EXISTS',
				),
			),
		) );

		return array(
			'compliance_events' => $event_summary,
			'total_events' => $compliance_logs['total'],
			'data_requests' => count( $data_requests ),
			'filters_applied' => $filters,
			'generated_at' => current_time( 'mysql' ),
		);
	}

	/**
	 * AJAX handler for generating reports.
	 *
	 * @since    1.0.0
	 */
	public function ajax_generate_report() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'slbp_reports_nonce' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Security check failed.' );
		}

		$report_type = sanitize_text_field( $_POST['report_type'] );
		$filters = isset( $_POST['filters'] ) ? $_POST['filters'] : array();
		$format = sanitize_text_field( $_POST['format'] ?? 'json' );

		$report = $this->generate_report( $report_type, $filters, $format );

		if ( is_wp_error( $report ) ) {
			wp_send_json_error( $report->get_error_message() );
		}

		wp_send_json_success( $report );
	}

	/**
	 * AJAX handler for scheduling reports.
	 *
	 * @since    1.0.0
	 */
	public function ajax_schedule_report() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'slbp_reports_nonce' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Security check failed.' );
		}

		$schedule_config = $_POST['schedule_config'];
		$schedule_id = $this->schedule_report( $schedule_config );

		if ( is_wp_error( $schedule_id ) ) {
			wp_send_json_error( $schedule_id->get_error_message() );
		}

		wp_send_json_success( array( 'schedule_id' => $schedule_id ) );
	}

	/**
	 * Send a scheduled report.
	 *
	 * @since    1.0.0
	 * @param    string    $schedule_id    Scheduled report ID.
	 * @param    array     $config        Report configuration.
	 */
	public function send_scheduled_report( $schedule_id, $config ) {
		// Generate the report
		$report = $this->generate_report( $config['report_type'], $config['filters'], $config['format'] );

		if ( is_wp_error( $report ) ) {
			error_log( 'SLBP: Failed to generate scheduled report: ' . $report->get_error_message() );
			return;
		}

		// Create email content
		$subject = ! empty( $config['subject'] ) ? $config['subject'] : 
		          sprintf( __( 'Scheduled %s Report', 'skylearn-billing-pro' ), 
		                  $this->report_types[ $config['report_type'] ]['name'] );

		$message = $this->generate_report_email( $report, $config );

		// Send email to recipients
		foreach ( $config['recipients'] as $recipient ) {
			wp_mail( $recipient, $subject, $message, array( 'Content-Type: text/html; charset=UTF-8' ) );
		}

		// Update last sent timestamp
		$scheduled_reports = get_option( 'slbp_scheduled_reports', array() );
		if ( isset( $scheduled_reports[ $schedule_id ] ) ) {
			$scheduled_reports[ $schedule_id ]['last_sent'] = current_time( 'mysql' );
			$scheduled_reports[ $schedule_id ]['next_send'] = $this->calculate_next_send_time( $config['frequency'] );
			update_option( 'slbp_scheduled_reports', $scheduled_reports );
		}

		// Log the sent report
		$this->audit_logger->log_event(
			'admin',
			'scheduled_report_sent',
			0,
			array(
				'schedule_id' => $schedule_id,
				'report_type' => $config['report_type'],
				'recipients_count' => count( $config['recipients'] ),
			),
			'info'
		);
	}

	/**
	 * Helper methods and utilities.
	 */
	
	private function validate_report_filters( $filters, $allowed_fields ) {
		$validated = array();
		
		foreach ( $allowed_fields as $field ) {
			if ( isset( $filters[ $field ] ) ) {
				$validated[ $field ] = sanitize_text_field( $filters[ $field ] );
			}
		}

		return $validated;
	}

	private function parse_date_range( $date_range ) {
		switch ( $date_range ) {
			case 'last_7_days':
				return array(
					'start' => date( 'Y-m-d', strtotime( '-7 days' ) ),
					'end' => date( 'Y-m-d' ),
				);
			case 'last_30_days':
				return array(
					'start' => date( 'Y-m-d', strtotime( '-30 days' ) ),
					'end' => date( 'Y-m-d' ),
				);
			case 'this_month':
				return array(
					'start' => date( 'Y-m-01' ),
					'end' => date( 'Y-m-d' ),
				);
			case 'last_month':
				return array(
					'start' => date( 'Y-m-01', strtotime( 'first day of last month' ) ),
					'end' => date( 'Y-m-t', strtotime( 'last day of last month' ) ),
				);
			default:
				return array(
					'start' => date( 'Y-m-01' ),
					'end' => date( 'Y-m-d' ),
				);
		}
	}

	private function format_report_output( $data, $format, $report_type ) {
		switch ( $format ) {
			case 'csv':
				return $this->export_to_csv( $data, $report_type );
			case 'pdf':
				return $this->export_to_pdf( $data, $report_type );
			case 'json':
			default:
				return $data;
		}
	}

	private function export_to_csv( $data, $report_type ) {
		// This would be implemented to create CSV files
		// For now, return a placeholder
		return array( 'format' => 'csv', 'data' => $data );
	}

	private function export_to_pdf( $data, $report_type ) {
		// This would be implemented to create PDF files
		// For now, return a placeholder
		return array( 'format' => 'pdf', 'data' => $data );
	}

	private function calculate_subscription_churn( $filters ) {
		// Mock churn calculation
		return array(
			'monthly_churn_rate' => 5.2,
			'annual_churn_rate' => 15.8,
		);
	}

	private function get_course_revenue( $course_id, $filters ) {
		// Mock revenue calculation for a specific course
		return rand( 1000, 5000 );
	}

	private function calculate_next_send_time( $frequency ) {
		switch ( $frequency ) {
			case 'daily':
				return date( 'Y-m-d H:i:s', strtotime( '+1 day' ) );
			case 'weekly':
				return date( 'Y-m-d H:i:s', strtotime( '+1 week' ) );
			case 'monthly':
				return date( 'Y-m-d H:i:s', strtotime( '+1 month' ) );
			default:
				return date( 'Y-m-d H:i:s', strtotime( '+1 week' ) );
		}
	}

	private function schedule_wp_cron( $schedule_id, $frequency ) {
		$hook = 'slbp_send_scheduled_report';
		$args = array( $schedule_id );

		if ( ! wp_next_scheduled( $hook, $args ) ) {
			wp_schedule_event( time(), $frequency, $hook, $args );
		}
	}

	private function generate_report_email( $report, $config ) {
		$html = '<html><body>';
		$html .= '<h2>' . sprintf( __( 'Your Scheduled %s Report', 'skylearn-billing-pro' ), 
		                          $this->report_types[ $config['report_type'] ]['name'] ) . '</h2>';
		$html .= '<p>' . sprintf( __( 'Generated on: %s', 'skylearn-billing-pro' ), 
		                         current_time( 'F j, Y g:i a' ) ) . '</p>';
		
		// Add report summary based on type
		if ( isset( $report['summary'] ) ) {
			$html .= '<h3>' . __( 'Summary', 'skylearn-billing-pro' ) . '</h3>';
			$html .= '<p>' . wp_json_encode( $report['summary'] ) . '</p>';
		}
		
		$html .= '</body></html>';
		
		return $html;
	}

	public function schedule_report_crons() {
		// Ensure custom cron schedules are available
		add_filter( 'cron_schedules', array( $this, 'add_custom_cron_schedules' ) );
	}

	public function add_custom_cron_schedules( $schedules ) {
		if ( ! isset( $schedules['weekly'] ) ) {
			$schedules['weekly'] = array(
				'interval' => 604800, // 7 days
				'display' => __( 'Once Weekly', 'skylearn-billing-pro' ),
			);
		}
		return $schedules;
	}

	public function add_dashboard_widgets() {
		wp_add_dashboard_widget(
			'slbp_revenue_widget',
			__( 'Revenue Overview', 'skylearn-billing-pro' ),
			array( $this, 'render_revenue_widget' )
		);

		wp_add_dashboard_widget(
			'slbp_subscription_widget',
			__( 'Subscription Metrics', 'skylearn-billing-pro' ),
			array( $this, 'render_subscription_widget' )
		);
	}

	public function render_revenue_widget() {
		$revenue_data = $this->analytics->get_dashboard_metrics();
		echo '<div class="slbp-widget">';
		echo '<p><strong>' . __( 'Total Revenue:', 'skylearn-billing-pro' ) . '</strong> $' . number_format( $revenue_data['total_revenue'], 2 ) . '</p>';
		echo '<p><strong>' . __( 'MRR:', 'skylearn-billing-pro' ) . '</strong> $' . number_format( $revenue_data['mrr'], 2 ) . '</p>';
		echo '</div>';
	}

	public function render_subscription_widget() {
		$subscription_data = $this->analytics->get_subscription_analytics();
		echo '<div class="slbp-widget">';
		echo '<p><strong>' . __( 'Active Subscriptions:', 'skylearn-billing-pro' ) . '</strong> ' . $subscription_data['active_subscriptions'] . '</p>';
		echo '<p><strong>' . __( 'Churn Rate:', 'skylearn-billing-pro' ) . '</strong> ' . $subscription_data['subscription_churn'] . '%</p>';
		echo '</div>';
	}

	/**
	 * Get available report types.
	 *
	 * @since    1.0.0
	 * @return   array    Available report types.
	 */
	public function get_report_types() {
		return $this->report_types;
	}
}