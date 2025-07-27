<?php
/**
 * The analytics admin functionality of the plugin.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/analytics
 */

/**
 * The analytics admin functionality of the plugin.
 *
 * Defines the admin interface for analytics, AJAX handlers, and data management.
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/analytics
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Analytics_Admin {

	/**
	 * The analytics instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SLBP_Analytics    $analytics    The analytics instance.
	 */
	private $analytics;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->analytics = new SLBP_Analytics();
		$this->init_hooks();
	}

	/**
	 * Initialize admin hooks.
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		// AJAX handlers for analytics data
		add_action( 'wp_ajax_slbp_get_dashboard_metrics', array( $this, 'ajax_get_dashboard_metrics' ) );
		add_action( 'wp_ajax_slbp_get_revenue_chart', array( $this, 'ajax_get_revenue_chart' ) );
		add_action( 'wp_ajax_slbp_export_analytics', array( $this, 'ajax_export_analytics' ) );
		add_action( 'wp_ajax_slbp_get_subscription_analytics', array( $this, 'ajax_get_subscription_analytics' ) );
	}

	/**
	 * AJAX handler for getting dashboard metrics.
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_dashboard_metrics() {
		// Verify nonce for security
		if ( ! wp_verify_nonce( $_POST['nonce'], 'slbp_analytics_nonce' ) ) {
			wp_die( 'Security check failed.' );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions.' );
		}

		// Parse filters from request
		$filters = $this->parse_ajax_filters();

		// Get metrics
		$metrics = $this->analytics->get_dashboard_metrics( $filters );

		// Format metrics for display
		$formatted_metrics = $this->format_metrics_for_display( $metrics );

		wp_send_json_success( $formatted_metrics );
	}

	/**
	 * AJAX handler for getting revenue chart data.
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_revenue_chart() {
		// Verify nonce for security
		if ( ! wp_verify_nonce( $_POST['nonce'], 'slbp_analytics_nonce' ) ) {
			wp_die( 'Security check failed.' );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions.' );
		}

		// Parse filters from request
		$filters = $this->parse_ajax_filters();

		// Get chart data
		$chart_data = $this->analytics->get_revenue_chart_data( $filters );

		wp_send_json_success( $chart_data );
	}

	/**
	 * AJAX handler for getting subscription analytics.
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_subscription_analytics() {
		// Verify nonce for security
		if ( ! wp_verify_nonce( $_POST['nonce'], 'slbp_analytics_nonce' ) ) {
			wp_die( 'Security check failed.' );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions.' );
		}

		// Parse filters from request
		$filters = $this->parse_ajax_filters();

		// Get subscription analytics
		$analytics = $this->analytics->get_subscription_analytics( $filters );

		wp_send_json_success( $analytics );
	}

	/**
	 * AJAX handler for exporting analytics data.
	 *
	 * @since    1.0.0
	 */
	public function ajax_export_analytics() {
		// Verify nonce for security
		if ( ! wp_verify_nonce( $_POST['nonce'], 'slbp_analytics_nonce' ) ) {
			wp_die( 'Security check failed.' );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions.' );
		}

		// Get export type and filters
		$export_type = isset( $_POST['export_type'] ) ? sanitize_text_field( $_POST['export_type'] ) : 'revenue';
		$filters = $this->parse_ajax_filters();

		// Export data
		$export_result = $this->analytics->export_to_csv( $export_type, $filters );

		if ( is_wp_error( $export_result ) ) {
			wp_send_json_error( $export_result->get_error_message() );
		}

		wp_send_json_success( array( 'download_url' => $export_result ) );
	}

	/**
	 * Parse filters from AJAX request.
	 *
	 * @since    1.0.0
	 * @return   array    Parsed and sanitized filters.
	 */
	private function parse_ajax_filters() {
		$filters = array();

		if ( isset( $_POST['date_range'] ) ) {
			$filters['date_range'] = sanitize_text_field( $_POST['date_range'] );
		}

		if ( isset( $_POST['start_date'] ) ) {
			$filters['start_date'] = sanitize_text_field( $_POST['start_date'] );
		}

		if ( isset( $_POST['end_date'] ) ) {
			$filters['end_date'] = sanitize_text_field( $_POST['end_date'] );
		}

		if ( isset( $_POST['course_id'] ) ) {
			$filters['course_id'] = intval( $_POST['course_id'] );
		}

		if ( isset( $_POST['product_id'] ) ) {
			$filters['product_id'] = intval( $_POST['product_id'] );
		}

		if ( isset( $_POST['grouping'] ) ) {
			$filters['grouping'] = sanitize_text_field( $_POST['grouping'] );
		}

		return $filters;
	}

	/**
	 * Format metrics for display.
	 *
	 * @since    1.0.0
	 * @param    array    $metrics    Raw metrics data.
	 * @return   array                Formatted metrics.
	 */
	private function format_metrics_for_display( $metrics ) {
		return array(
			'total_revenue' => array(
				'value' => number_format( $metrics['total_revenue'], 2 ),
				'formatted' => '$' . number_format( $metrics['total_revenue'], 2 ),
				'change' => '+12.5%', // Sample change percentage
				'trend' => 'up',
			),
			'mrr' => array(
				'value' => number_format( $metrics['mrr'], 2 ),
				'formatted' => '$' . number_format( $metrics['mrr'], 2 ),
				'change' => '+8.3%',
				'trend' => 'up',
			),
			'active_users' => array(
				'value' => $metrics['active_users'],
				'formatted' => number_format( $metrics['active_users'] ),
				'change' => '+15.2%',
				'trend' => 'up',
			),
			'churn_rate' => array(
				'value' => $metrics['churn_rate'],
				'formatted' => number_format( $metrics['churn_rate'], 1 ) . '%',
				'change' => '-2.1%',
				'trend' => 'down',
			),
			'new_signups' => array(
				'value' => $metrics['new_signups'],
				'formatted' => number_format( $metrics['new_signups'] ),
				'change' => '+25.8%',
				'trend' => 'up',
			),
			'course_completions' => array(
				'value' => $metrics['course_completions'],
				'formatted' => number_format( $metrics['course_completions'] ),
				'change' => '+18.7%',
				'trend' => 'up',
			),
			'refund_rate' => array(
				'value' => $metrics['refund_rate'],
				'formatted' => number_format( $metrics['refund_rate'], 1 ) . '%',
				'change' => '-1.2%',
				'trend' => 'down',
			),
			'top_products' => $metrics['top_products'],
		);
	}

	/**
	 * Get the analytics instance.
	 *
	 * @since    1.0.0
	 * @return   SLBP_Analytics    The analytics instance.
	 */
	public function get_analytics() {
		return $this->analytics;
	}
}