<?php
/**
 * KPI Manager for custom KPI creation and management.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/analytics
 */

/**
 * KPI Manager class.
 *
 * Handles creation, management, and calculation of custom KPIs.
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/analytics
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_KPI_Manager {

	/**
	 * Analytics instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SLBP_Analytics    $analytics    Analytics instance.
	 */
	private $analytics;

	/**
	 * Available metrics for KPI calculations.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $available_metrics    Available metrics.
	 */
	private $available_metrics;

	/**
	 * Initialize the class.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->analytics = new SLBP_Analytics();
		$this->init_available_metrics();
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		add_action( 'wp_ajax_slbp_save_kpi', array( $this, 'ajax_save_kpi' ) );
		add_action( 'wp_ajax_slbp_delete_kpi', array( $this, 'ajax_delete_kpi' ) );
		add_action( 'wp_ajax_slbp_get_kpi_data', array( $this, 'ajax_get_kpi_data' ) );
		add_action( 'wp_ajax_slbp_get_kpi_chart_data', array( $this, 'ajax_get_kpi_chart_data' ) );
		
		// Schedule daily KPI calculation
		add_action( 'slbp_daily_kpi_calculation', array( $this, 'calculate_all_kpis' ) );
		
		// Check thresholds and send alerts
		add_action( 'slbp_kpi_threshold_check', array( $this, 'check_all_thresholds' ) );
	}

	/**
	 * Initialize available metrics.
	 *
	 * @since    1.0.0
	 */
	private function init_available_metrics() {
		$this->available_metrics = array(
			'total_revenue' => array(
				'name' => __( 'Total Revenue', 'skylearn-billing-pro' ),
				'description' => __( 'Total revenue from all transactions', 'skylearn-billing-pro' ),
				'type' => 'currency',
				'source' => 'analytics',
			),
			'mrr' => array(
				'name' => __( 'Monthly Recurring Revenue', 'skylearn-billing-pro' ),
				'description' => __( 'Monthly recurring revenue from subscriptions', 'skylearn-billing-pro' ),
				'type' => 'currency',
				'source' => 'analytics',
			),
			'active_users' => array(
				'name' => __( 'Active Users', 'skylearn-billing-pro' ),
				'description' => __( 'Number of active users', 'skylearn-billing-pro' ),
				'type' => 'number',
				'source' => 'analytics',
			),
			'churn_rate' => array(
				'name' => __( 'Churn Rate', 'skylearn-billing-pro' ),
				'description' => __( 'Percentage of users who cancelled', 'skylearn-billing-pro' ),
				'type' => 'percentage',
				'source' => 'analytics',
			),
			'new_signups' => array(
				'name' => __( 'New Signups', 'skylearn-billing-pro' ),
				'description' => __( 'Number of new user registrations', 'skylearn-billing-pro' ),
				'type' => 'number',
				'source' => 'analytics',
			),
			'course_completions' => array(
				'name' => __( 'Course Completions', 'skylearn-billing-pro' ),
				'description' => __( 'Number of completed courses', 'skylearn-billing-pro' ),
				'type' => 'number',
				'source' => 'analytics',
			),
			'refund_rate' => array(
				'name' => __( 'Refund Rate', 'skylearn-billing-pro' ),
				'description' => __( 'Percentage of transactions refunded', 'skylearn-billing-pro' ),
				'type' => 'percentage',
				'source' => 'analytics',
			),
			'average_order_value' => array(
				'name' => __( 'Average Order Value', 'skylearn-billing-pro' ),
				'description' => __( 'Average value per transaction', 'skylearn-billing-pro' ),
				'type' => 'currency',
				'source' => 'calculated',
			),
			'customer_acquisition_cost' => array(
				'name' => __( 'Customer Acquisition Cost', 'skylearn-billing-pro' ),
				'description' => __( 'Cost to acquire each new customer', 'skylearn-billing-pro' ),
				'type' => 'currency',
				'source' => 'calculated',
			),
			'total_customers' => array(
				'name' => __( 'Total Customers', 'skylearn-billing-pro' ),
				'description' => __( 'Total number of customers', 'skylearn-billing-pro' ),
				'type' => 'number',
				'source' => 'calculated',
			),
		);

		// Allow custom metrics via filter
		$this->available_metrics = apply_filters( 'slbp_available_metrics', $this->available_metrics );
	}

	/**
	 * Get all custom KPIs.
	 *
	 * @since    1.0.0
	 * @return   array    Custom KPIs.
	 */
	public function get_custom_kpis() {
		return get_option( 'slbp_custom_kpis', array() );
	}

	/**
	 * Get available metrics.
	 *
	 * @since    1.0.0
	 * @return   array    Available metrics.
	 */
	public function get_available_metrics() {
		return $this->available_metrics;
	}

	/**
	 * Save a custom KPI.
	 *
	 * @since    1.0.0
	 * @param    array    $kpi_data    KPI data.
	 * @return   string|WP_Error     KPI ID or error.
	 */
	public function save_kpi( $kpi_data ) {
		// Validate required fields
		if ( empty( $kpi_data['name'] ) || empty( $kpi_data['calculation_type'] ) ) {
			return new WP_Error( 'missing_required_fields', __( 'Name and calculation type are required.', 'skylearn-billing-pro' ) );
		}

		// Sanitize data
		$sanitized_data = array(
			'name' => sanitize_text_field( $kpi_data['name'] ),
			'description' => sanitize_textarea_field( $kpi_data['description'] ?? '' ),
			'calculation_type' => sanitize_text_field( $kpi_data['calculation_type'] ),
			'unit' => sanitize_text_field( $kpi_data['unit'] ?? 'number' ),
			'active' => (bool) ( $kpi_data['active'] ?? true ),
			'created_at' => current_time( 'mysql' ),
			'created_by' => get_current_user_id(),
		);

		// Handle calculation configuration
		switch ( $kpi_data['calculation_type'] ) {
			case 'simple':
				$sanitized_data['calculation'] = array(
					'type' => 'simple',
					'metric' => sanitize_text_field( $kpi_data['simple_metric'] ),
				);
				break;
			case 'ratio':
				$sanitized_data['calculation'] = array(
					'type' => 'ratio',
					'numerator' => sanitize_text_field( $kpi_data['ratio_numerator'] ),
					'denominator' => sanitize_text_field( $kpi_data['ratio_denominator'] ),
				);
				break;
			case 'growth':
				$sanitized_data['calculation'] = array(
					'type' => 'growth',
					'metric' => sanitize_text_field( $kpi_data['simple_metric'] ),
					'period' => 'monthly', // Default to monthly growth
				);
				break;
			case 'average':
				$sanitized_data['calculation'] = array(
					'type' => 'average',
					'metric' => sanitize_text_field( $kpi_data['simple_metric'] ),
					'period' => sanitize_text_field( $kpi_data['average_period'] ?? 'daily' ),
				);
				break;
			case 'custom':
				$sanitized_data['calculation'] = array(
					'type' => 'custom',
					'formula' => sanitize_textarea_field( $kpi_data['custom_formula'] ),
				);
				break;
		}

		// Handle thresholds
		if ( ! empty( $kpi_data['threshold_warning'] ) || ! empty( $kpi_data['threshold_critical'] ) ) {
			$sanitized_data['threshold'] = array();
			
			if ( ! empty( $kpi_data['threshold_warning'] ) ) {
				$sanitized_data['threshold']['warning'] = floatval( $kpi_data['threshold_warning'] );
			}
			
			if ( ! empty( $kpi_data['threshold_critical'] ) ) {
				$sanitized_data['threshold']['critical'] = floatval( $kpi_data['threshold_critical'] );
			}
		}

		// Generate KPI ID or use existing one
		$kpi_id = ! empty( $kpi_data['kpi_id'] ) ? $kpi_data['kpi_id'] : uniqid( 'kpi_' );
		
		// If updating, preserve created_at and created_by
		$existing_kpis = $this->get_custom_kpis();
		if ( isset( $existing_kpis[ $kpi_id ] ) ) {
			$sanitized_data['created_at'] = $existing_kpis[ $kpi_id ]['created_at'];
			$sanitized_data['created_by'] = $existing_kpis[ $kpi_id ]['created_by'];
			$sanitized_data['updated_at'] = current_time( 'mysql' );
		}

		// Save KPI
		$existing_kpis[ $kpi_id ] = $sanitized_data;
		update_option( 'slbp_custom_kpis', $existing_kpis );

		return $kpi_id;
	}

	/**
	 * Delete a custom KPI.
	 *
	 * @since    1.0.0
	 * @param    string    $kpi_id    KPI ID.
	 * @return   bool                Success status.
	 */
	public function delete_kpi( $kpi_id ) {
		$kpis = $this->get_custom_kpis();
		
		if ( ! isset( $kpis[ $kpi_id ] ) ) {
			return false;
		}

		unset( $kpis[ $kpi_id ] );
		return update_option( 'slbp_custom_kpis', $kpis );
	}

	/**
	 * Calculate KPI value.
	 *
	 * @since    1.0.0
	 * @param    string    $kpi_id    KPI ID.
	 * @param    array     $filters   Optional filters.
	 * @return   float|null           Calculated value or null.
	 */
	public function calculate_kpi_value( $kpi_id, $filters = array() ) {
		$kpis = $this->get_custom_kpis();
		
		if ( ! isset( $kpis[ $kpi_id ] ) ) {
			return null;
		}

		$kpi = $kpis[ $kpi_id ];
		$calculation = $kpi['calculation'];

		switch ( $calculation['type'] ) {
			case 'simple':
				return $this->get_metric_value( $calculation['metric'], $filters );
			
			case 'ratio':
				$numerator = $this->get_metric_value( $calculation['numerator'], $filters );
				$denominator = $this->get_metric_value( $calculation['denominator'], $filters );
				
				if ( $denominator == 0 ) {
					return 0;
				}
				
				return ( $numerator / $denominator ) * 100; // Return as percentage
			
			case 'growth':
				return $this->calculate_growth_rate( $calculation['metric'], $calculation['period'], $filters );
			
			case 'average':
				return $this->calculate_average( $calculation['metric'], $calculation['period'], $filters );
			
			case 'custom':
				return $this->evaluate_custom_formula( $calculation['formula'], $filters );
		}

		return null;
	}

	/**
	 * Get metric value.
	 *
	 * @since    1.0.0
	 * @param    string    $metric_id    Metric ID.
	 * @param    array     $filters      Filters.
	 * @return   float                  Metric value.
	 */
	private function get_metric_value( $metric_id, $filters = array() ) {
		if ( ! isset( $this->available_metrics[ $metric_id ] ) ) {
			return 0;
		}

		$metric = $this->available_metrics[ $metric_id ];

		switch ( $metric['source'] ) {
			case 'analytics':
				$dashboard_metrics = $this->analytics->get_dashboard_metrics( $filters );
				return $dashboard_metrics[ $metric_id ] ?? 0;
			
			case 'calculated':
				return $this->calculate_derived_metric( $metric_id, $filters );
		}

		return 0;
	}

	/**
	 * Calculate derived metrics.
	 *
	 * @since    1.0.0
	 * @param    string    $metric_id    Metric ID.
	 * @param    array     $filters      Filters.
	 * @return   float                  Calculated value.
	 */
	private function calculate_derived_metric( $metric_id, $filters = array() ) {
		switch ( $metric_id ) {
			case 'average_order_value':
				$total_revenue = $this->get_metric_value( 'total_revenue', $filters );
				$transaction_count = $this->get_transaction_count( $filters );
				return $transaction_count > 0 ? $total_revenue / $transaction_count : 0;
			
			case 'customer_acquisition_cost':
				// This would need marketing spend data
				return 50; // Placeholder value
			
			case 'total_customers':
				return $this->get_total_customers( $filters );
		}

		return 0;
	}

	/**
	 * Calculate growth rate.
	 *
	 * @since    1.0.0
	 * @param    string    $metric_id    Metric ID.
	 * @param    string    $period       Period (monthly, weekly, etc.).
	 * @param    array     $filters      Filters.
	 * @return   float                  Growth rate percentage.
	 */
	private function calculate_growth_rate( $metric_id, $period, $filters = array() ) {
		// Get current period value
		$current_value = $this->get_metric_value( $metric_id, $filters );
		
		// Get previous period value
		$previous_filters = $this->get_previous_period_filters( $period, $filters );
		$previous_value = $this->get_metric_value( $metric_id, $previous_filters );
		
		if ( $previous_value == 0 ) {
			return 0;
		}
		
		return ( ( $current_value - $previous_value ) / $previous_value ) * 100;
	}

	/**
	 * Calculate average value.
	 *
	 * @since    1.0.0
	 * @param    string    $metric_id    Metric ID.
	 * @param    string    $period       Period.
	 * @param    array     $filters      Filters.
	 * @return   float                  Average value.
	 */
	private function calculate_average( $metric_id, $period, $filters = array() ) {
		// This would implement rolling average calculation
		// For now, return current value
		return $this->get_metric_value( $metric_id, $filters );
	}

	/**
	 * Evaluate custom formula.
	 *
	 * @since    1.0.0
	 * @param    string    $formula    Formula string.
	 * @param    array     $filters    Filters.
	 * @return   float                Calculated result.
	 */
	private function evaluate_custom_formula( $formula, $filters = array() ) {
		// Replace metric placeholders with actual values
		$processed_formula = $formula;
		
		// Find all metric placeholders in the format {metric_name}
		preg_match_all( '/\{([^}]+)\}/', $formula, $matches );
		
		foreach ( $matches[1] as $metric_id ) {
			$value = $this->get_metric_value( $metric_id, $filters );
			$processed_formula = str_replace( '{' . $metric_id . '}', $value, $processed_formula );
		}
		
		// Safely evaluate the mathematical expression
		return $this->safe_eval( $processed_formula );
	}

	/**
	 * Safely evaluate mathematical expression.
	 *
	 * @since    1.0.0
	 * @param    string    $expression    Mathematical expression.
	 * @return   float                   Result.
	 */
	private function safe_eval( $expression ) {
		// Remove whitespace and validate expression contains only safe characters
		$expression = preg_replace( '/\s+/', '', $expression );
		
		if ( ! preg_match( '/^[0-9+\-*\/\(\).]+$/', $expression ) ) {
			return 0; // Invalid expression
		}
		
		// Use eval with caution - in production, consider a math parser library
		try {
			$result = eval( "return $expression;" );
			return is_numeric( $result ) ? (float) $result : 0;
		} catch ( Throwable $e ) {
			error_log( 'SLBP KPI Formula Error: ' . $e->getMessage() );
			return 0;
		}
	}

	/**
	 * Get KPI status based on thresholds.
	 *
	 * @since    1.0.0
	 * @param    string    $kpi_id    KPI ID.
	 * @param    float     $value     Current value.
	 * @return   string              Status (good, warning, critical).
	 */
	public function get_kpi_status( $kpi_id, $value ) {
		$kpis = $this->get_custom_kpis();
		
		if ( ! isset( $kpis[ $kpi_id ]['threshold'] ) ) {
			return 'good';
		}
		
		$threshold = $kpis[ $kpi_id ]['threshold'];
		
		if ( isset( $threshold['critical'] ) && $value <= $threshold['critical'] ) {
			return 'critical';
		}
		
		if ( isset( $threshold['warning'] ) && $value <= $threshold['warning'] ) {
			return 'warning';
		}
		
		return 'good';
	}

	/**
	 * Format KPI value for display.
	 *
	 * @since    1.0.0
	 * @param    float    $value    Value to format.
	 * @param    array    $kpi      KPI configuration.
	 * @return   string            Formatted value.
	 */
	public function format_kpi_value( $value, $kpi ) {
		switch ( $kpi['unit'] ) {
			case 'currency':
				return '$' . number_format( $value, 2 );
			case 'percentage':
				return number_format( $value, 1 ) . '%';
			case 'time':
				return $this->format_time_value( $value );
			case 'number':
			default:
				return number_format( $value );
		}
	}

	/**
	 * Format time value.
	 *
	 * @since    1.0.0
	 * @param    float    $value    Time value in seconds.
	 * @return   string            Formatted time.
	 */
	private function format_time_value( $value ) {
		if ( $value < 60 ) {
			return number_format( $value ) . 's';
		} elseif ( $value < 3600 ) {
			return number_format( $value / 60, 1 ) . 'm';
		} else {
			return number_format( $value / 3600, 1 ) . 'h';
		}
	}

	/**
	 * AJAX handler for saving KPI.
	 *
	 * @since    1.0.0
	 */
	public function ajax_save_kpi() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'slbp_kpi_nonce' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Security check failed.' );
		}

		$kpi_data = $_POST['kpi_data'];
		$result = $this->save_kpi( $kpi_data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( array( 'kpi_id' => $result ) );
	}

	/**
	 * AJAX handler for deleting KPI.
	 *
	 * @since    1.0.0
	 */
	public function ajax_delete_kpi() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'slbp_kpi_nonce' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Security check failed.' );
		}

		$kpi_id = sanitize_text_field( $_POST['kpi_id'] );
		$result = $this->delete_kpi( $kpi_id );

		if ( ! $result ) {
			wp_send_json_error( __( 'Failed to delete KPI.', 'skylearn-billing-pro' ) );
		}

		wp_send_json_success();
	}

	/**
	 * AJAX handler for getting KPI chart data.
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_kpi_chart_data() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'slbp_kpi_nonce' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Security check failed.' );
		}

		$kpi_id = sanitize_text_field( $_POST['kpi_id'] );
		$timeframe = sanitize_text_field( $_POST['timeframe'] ?? 'last_30_days' );

		$chart_data = $this->get_kpi_chart_data( $kpi_id, $timeframe );

		wp_send_json_success( $chart_data );
	}

	/**
	 * Get KPI chart data.
	 *
	 * @since    1.0.0
	 * @param    string    $kpi_id      KPI ID.
	 * @param    string    $timeframe   Timeframe.
	 * @return   array                 Chart data.
	 */
	public function get_kpi_chart_data( $kpi_id, $timeframe = 'last_30_days' ) {
		// Generate historical data for the KPI
		$days = $this->get_days_from_timeframe( $timeframe );
		$labels = array();
		$data = array();

		for ( $i = $days - 1; $i >= 0; $i-- ) {
			$date = date( 'Y-m-d', strtotime( "-{$i} days" ) );
			$labels[] = date( 'M j', strtotime( $date ) );
			
			// Calculate KPI value for this date
			$filters = array( 'end_date' => $date );
			$value = $this->calculate_kpi_value( $kpi_id, $filters );
			$data[] = $value ?? 0;
		}

		return array(
			'labels' => $labels,
			'datasets' => array(
				array(
					'label' => $this->get_kpi_name( $kpi_id ),
					'data' => $data,
					'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
					'borderColor' => 'rgba(54, 162, 235, 1)',
					'borderWidth' => 2,
					'fill' => true,
				),
			),
		);
	}

	/**
	 * Helper methods.
	 */
	private function get_transaction_count( $filters = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'slbp_transactions';
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'completed'" );
	}

	private function get_total_customers( $filters = array() ) {
		$user_count = count_users();
		return $user_count['total_users'];
	}

	private function get_previous_period_filters( $period, $current_filters ) {
		// Implement logic to generate filters for previous period
		return $current_filters; // Placeholder
	}

	private function get_days_from_timeframe( $timeframe ) {
		switch ( $timeframe ) {
			case 'last_7_days':
				return 7;
			case 'last_30_days':
				return 30;
			case 'last_90_days':
				return 90;
			default:
				return 30;
		}
	}

	private function get_kpi_name( $kpi_id ) {
		$kpis = $this->get_custom_kpis();
		return $kpis[ $kpi_id ]['name'] ?? 'Unknown KPI';
	}

	/**
	 * Calculate all KPIs (scheduled task).
	 *
	 * @since    1.0.0
	 */
	public function calculate_all_kpis() {
		$kpis = $this->get_custom_kpis();
		
		foreach ( $kpis as $kpi_id => $kpi ) {
			if ( ! $kpi['active'] ) {
				continue;
			}
			
			$value = $this->calculate_kpi_value( $kpi_id );
			
			// Store calculated value with timestamp
			$kpi_values = get_option( 'slbp_kpi_values', array() );
			$kpi_values[ $kpi_id ][ date( 'Y-m-d' ) ] = $value;
			update_option( 'slbp_kpi_values', $kpi_values );
		}
	}

	/**
	 * Check all KPI thresholds.
	 *
	 * @since    1.0.0
	 */
	public function check_all_thresholds() {
		$kpis = $this->get_custom_kpis();
		
		foreach ( $kpis as $kpi_id => $kpi ) {
			if ( ! $kpi['active'] || ! isset( $kpi['threshold'] ) ) {
				continue;
			}
			
			$current_value = $this->calculate_kpi_value( $kpi_id );
			$status = $this->get_kpi_status( $kpi_id, $current_value );
			
			if ( in_array( $status, array( 'warning', 'critical' ), true ) ) {
				$this->send_threshold_alert( $kpi_id, $kpi, $current_value, $status );
			}
		}
	}

	/**
	 * Send threshold alert.
	 *
	 * @since    1.0.0
	 * @param    string    $kpi_id         KPI ID.
	 * @param    array     $kpi            KPI data.
	 * @param    float     $current_value  Current value.
	 * @param    string    $status         Alert status.
	 */
	private function send_threshold_alert( $kpi_id, $kpi, $current_value, $status ) {
		$admin_email = get_option( 'admin_email' );
		$subject = sprintf( 
			__( 'KPI Alert: %s threshold exceeded', 'skylearn-billing-pro' ), 
			$kpi['name'] 
		);
		
		$message = sprintf(
			__( 'The KPI "%s" has exceeded its %s threshold.\n\nCurrent value: %s\nThreshold: %s\n\nPlease review your analytics dashboard for more details.', 'skylearn-billing-pro' ),
			$kpi['name'],
			$status,
			$this->format_kpi_value( $current_value, $kpi ),
			$this->format_kpi_value( $kpi['threshold'][ $status ], $kpi )
		);
		
		wp_mail( $admin_email, $subject, $message );
	}
}