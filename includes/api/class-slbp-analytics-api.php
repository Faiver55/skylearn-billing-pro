<?php
/**
 * Analytics API endpoints for external data access.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/api
 */

/**
 * Analytics API endpoints for external data access.
 *
 * Provides RESTful API endpoints for accessing analytics data from external systems.
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/api
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Analytics_API {

	/**
	 * The analytics instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SLBP_Analytics    $analytics    The analytics instance.
	 */
	private $analytics;

	/**
	 * API namespace.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $namespace    API namespace.
	 */
	private $namespace = 'slbp/v1';

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
	 * Initialize API hooks.
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @since    1.0.0
	 */
	public function register_routes() {
		// Dashboard metrics endpoint
		register_rest_route( $this->namespace, '/analytics/metrics', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_dashboard_metrics' ),
			'permission_callback' => array( $this, 'check_analytics_permissions' ),
			'args'                => array(
				'date_range' => array(
					'description' => __( 'Date range filter', 'skylearn-billing-pro' ),
					'type'        => 'string',
					'enum'        => array( 'last_7_days', 'last_30_days', 'this_month', 'last_month', 'this_year', 'custom' ),
					'default'     => 'last_30_days',
				),
				'start_date' => array(
					'description' => __( 'Start date for custom range', 'skylearn-billing-pro' ),
					'type'        => 'string',
					'format'      => 'date',
				),
				'end_date' => array(
					'description' => __( 'End date for custom range', 'skylearn-billing-pro' ),
					'type'        => 'string',
					'format'      => 'date',
				),
			),
		) );

		// Revenue chart data endpoint
		register_rest_route( $this->namespace, '/analytics/revenue-chart', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_revenue_chart_data' ),
			'permission_callback' => array( $this, 'check_analytics_permissions' ),
			'args'                => array(
				'date_range' => array(
					'description' => __( 'Date range filter', 'skylearn-billing-pro' ),
					'type'        => 'string',
					'enum'        => array( 'last_7_days', 'last_30_days', 'this_month', 'last_month', 'this_year', 'custom' ),
					'default'     => 'last_30_days',
				),
				'grouping' => array(
					'description' => __( 'Data grouping', 'skylearn-billing-pro' ),
					'type'        => 'string',
					'enum'        => array( 'daily', 'weekly', 'monthly' ),
					'default'     => 'daily',
				),
			),
		) );

		// Subscription analytics endpoint
		register_rest_route( $this->namespace, '/analytics/subscriptions', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_subscription_analytics' ),
			'permission_callback' => array( $this, 'check_analytics_permissions' ),
		) );

		// Custom report generation endpoint
		register_rest_route( $this->namespace, '/analytics/reports/(?P<type>[a-zA-Z0-9_-]+)', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'generate_custom_report' ),
			'permission_callback' => array( $this, 'check_analytics_permissions' ),
			'args'                => array(
				'type' => array(
					'description' => __( 'Report type', 'skylearn-billing-pro' ),
					'type'        => 'string',
					'required'    => true,
				),
				'filters' => array(
					'description' => __( 'Report filters', 'skylearn-billing-pro' ),
					'type'        => 'object',
					'default'     => array(),
				),
				'format' => array(
					'description' => __( 'Export format', 'skylearn-billing-pro' ),
					'type'        => 'string',
					'enum'        => array( 'json', 'csv', 'pdf' ),
					'default'     => 'json',
				),
			),
		) );

		// Data export endpoint
		register_rest_route( $this->namespace, '/analytics/export', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'export_analytics_data' ),
			'permission_callback' => array( $this, 'check_analytics_permissions' ),
			'args'                => array(
				'type' => array(
					'description' => __( 'Export data type', 'skylearn-billing-pro' ),
					'type'        => 'string',
					'enum'        => array( 'revenue', 'subscriptions', 'courses', 'users' ),
					'required'    => true,
				),
				'format' => array(
					'description' => __( 'Export format', 'skylearn-billing-pro' ),
					'type'        => 'string',
					'enum'        => array( 'csv', 'pdf', 'xlsx' ),
					'default'     => 'csv',
				),
				'filters' => array(
					'description' => __( 'Export filters', 'skylearn-billing-pro' ),
					'type'        => 'object',
					'default'     => array(),
				),
			),
		) );

		// KPI configuration endpoint
		register_rest_route( $this->namespace, '/analytics/kpis', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_custom_kpis' ),
				'permission_callback' => array( $this, 'check_analytics_permissions' ),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_custom_kpi' ),
				'permission_callback' => array( $this, 'check_analytics_permissions' ),
				'args'                => array(
					'name' => array(
						'description' => __( 'KPI name', 'skylearn-billing-pro' ),
						'type'        => 'string',
						'required'    => true,
					),
					'description' => array(
						'description' => __( 'KPI description', 'skylearn-billing-pro' ),
						'type'        => 'string',
					),
					'calculation' => array(
						'description' => __( 'KPI calculation method', 'skylearn-billing-pro' ),
						'type'        => 'object',
						'required'    => true,
					),
					'threshold' => array(
						'description' => __( 'KPI threshold values', 'skylearn-billing-pro' ),
						'type'        => 'object',
					),
				),
			),
		) );

		// Individual KPI management
		register_rest_route( $this->namespace, '/analytics/kpis/(?P<id>\d+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_custom_kpi' ),
				'permission_callback' => array( $this, 'check_analytics_permissions' ),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_custom_kpi' ),
				'permission_callback' => array( $this, 'check_analytics_permissions' ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_custom_kpi' ),
				'permission_callback' => array( $this, 'check_analytics_permissions' ),
			),
		) );
	}

	/**
	 * Check analytics API permissions.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   bool                          Permission result.
	 */
	public function check_analytics_permissions( $request ) {
		// Allow authenticated users with manage_options capability
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		// Check for API key authentication
		$api_key = $request->get_header( 'X-SLBP-API-Key' );
		if ( $api_key && $this->validate_api_key( $api_key ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Validate API key.
	 *
	 * @since    1.0.0
	 * @param    string    $api_key    API key to validate.
	 * @return   bool                 Validation result.
	 */
	private function validate_api_key( $api_key ) {
		$stored_keys = get_option( 'slbp_api_keys', array() );
		
		foreach ( $stored_keys as $key_data ) {
			if ( hash_equals( $key_data['key'], $api_key ) && $key_data['active'] ) {
				// Log API usage
				$this->log_api_usage( $key_data['name'], $api_key );
				return true;
			}
		}

		return false;
	}

	/**
	 * Log API usage.
	 *
	 * @since    1.0.0
	 * @param    string    $key_name    API key name.
	 * @param    string    $api_key     API key.
	 */
	private function log_api_usage( $key_name, $api_key ) {
		// This would integrate with the audit logger
		if ( class_exists( 'SLBP_Audit_Logger' ) ) {
			$audit_logger = new SLBP_Audit_Logger();
			$audit_logger->log_event(
				'api',
				'analytics_api_access',
				0,
				array(
					'api_key_name' => $key_name,
					'endpoint' => $_SERVER['REQUEST_URI'] ?? '',
					'method' => $_SERVER['REQUEST_METHOD'] ?? '',
				),
				'info'
			);
		}
	}

	/**
	 * Get dashboard metrics via API.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   WP_REST_Response              Response object.
	 */
	public function get_dashboard_metrics( $request ) {
		$filters = array(
			'date_range' => $request->get_param( 'date_range' ),
			'start_date' => $request->get_param( 'start_date' ),
			'end_date'   => $request->get_param( 'end_date' ),
		);

		$metrics = $this->analytics->get_dashboard_metrics( $filters );
		
		return new WP_REST_Response( array(
			'success' => true,
			'data'    => $metrics,
			'filters' => $filters,
		), 200 );
	}

	/**
	 * Get revenue chart data via API.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   WP_REST_Response              Response object.
	 */
	public function get_revenue_chart_data( $request ) {
		$filters = array(
			'date_range' => $request->get_param( 'date_range' ),
			'grouping'   => $request->get_param( 'grouping' ),
		);

		$chart_data = $this->analytics->get_revenue_chart_data( $filters );
		
		return new WP_REST_Response( array(
			'success' => true,
			'data'    => $chart_data,
		), 200 );
	}

	/**
	 * Get subscription analytics via API.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   WP_REST_Response              Response object.
	 */
	public function get_subscription_analytics( $request ) {
		$analytics = $this->analytics->get_subscription_analytics();
		
		return new WP_REST_Response( array(
			'success' => true,
			'data'    => $analytics,
		), 200 );
	}

	/**
	 * Generate custom report via API.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   WP_REST_Response              Response object.
	 */
	public function generate_custom_report( $request ) {
		$report_type = $request->get_param( 'type' );
		$filters = $request->get_param( 'filters' );
		$format = $request->get_param( 'format' );

		if ( ! class_exists( 'SLBP_Advanced_Reports' ) ) {
			return new WP_REST_Response( array(
				'success' => false,
				'message' => __( 'Advanced reports not available.', 'skylearn-billing-pro' ),
			), 500 );
		}

		$reports = new SLBP_Advanced_Reports();
		$report = $reports->generate_report( $report_type, $filters, $format );

		if ( is_wp_error( $report ) ) {
			return new WP_REST_Response( array(
				'success' => false,
				'message' => $report->get_error_message(),
			), 400 );
		}

		return new WP_REST_Response( array(
			'success' => true,
			'data'    => $report,
		), 200 );
	}

	/**
	 * Export analytics data via API.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   WP_REST_Response              Response object.
	 */
	public function export_analytics_data( $request ) {
		$type = $request->get_param( 'type' );
		$format = $request->get_param( 'format' );
		$filters = $request->get_param( 'filters' );

		$export_result = $this->analytics->export_to_csv( $type, $filters );

		if ( is_wp_error( $export_result ) ) {
			return new WP_REST_Response( array(
				'success' => false,
				'message' => $export_result->get_error_message(),
			), 400 );
		}

		return new WP_REST_Response( array(
			'success'      => true,
			'download_url' => $export_result,
			'format'       => $format,
		), 200 );
	}

	/**
	 * Get custom KPIs.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   WP_REST_Response              Response object.
	 */
	public function get_custom_kpis( $request ) {
		$kpis = get_option( 'slbp_custom_kpis', array() );
		
		return new WP_REST_Response( array(
			'success' => true,
			'data'    => $kpis,
		), 200 );
	}

	/**
	 * Create custom KPI.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   WP_REST_Response              Response object.
	 */
	public function create_custom_kpi( $request ) {
		$kpi_data = array(
			'name'        => sanitize_text_field( $request->get_param( 'name' ) ),
			'description' => sanitize_textarea_field( $request->get_param( 'description' ) ),
			'calculation' => $request->get_param( 'calculation' ),
			'threshold'   => $request->get_param( 'threshold' ),
			'created_at'  => current_time( 'mysql' ),
			'created_by'  => get_current_user_id(),
			'active'      => true,
		);

		$kpis = get_option( 'slbp_custom_kpis', array() );
		$kpi_id = uniqid( 'kpi_' );
		$kpis[ $kpi_id ] = $kpi_data;
		
		update_option( 'slbp_custom_kpis', $kpis );

		return new WP_REST_Response( array(
			'success' => true,
			'kpi_id'  => $kpi_id,
			'data'    => $kpi_data,
		), 201 );
	}

	/**
	 * Get individual custom KPI.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   WP_REST_Response              Response object.
	 */
	public function get_custom_kpi( $request ) {
		$kpi_id = $request->get_param( 'id' );
		$kpis = get_option( 'slbp_custom_kpis', array() );

		if ( ! isset( $kpis[ $kpi_id ] ) ) {
			return new WP_REST_Response( array(
				'success' => false,
				'message' => __( 'KPI not found.', 'skylearn-billing-pro' ),
			), 404 );
		}

		return new WP_REST_Response( array(
			'success' => true,
			'data'    => $kpis[ $kpi_id ],
		), 200 );
	}

	/**
	 * Update custom KPI.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   WP_REST_Response              Response object.
	 */
	public function update_custom_kpi( $request ) {
		$kpi_id = $request->get_param( 'id' );
		$kpis = get_option( 'slbp_custom_kpis', array() );

		if ( ! isset( $kpis[ $kpi_id ] ) ) {
			return new WP_REST_Response( array(
				'success' => false,
				'message' => __( 'KPI not found.', 'skylearn-billing-pro' ),
			), 404 );
		}

		// Update KPI data
		$kpis[ $kpi_id ]['name'] = sanitize_text_field( $request->get_param( 'name' ) );
		$kpis[ $kpi_id ]['description'] = sanitize_textarea_field( $request->get_param( 'description' ) );
		$kpis[ $kpi_id ]['calculation'] = $request->get_param( 'calculation' );
		$kpis[ $kpi_id ]['threshold'] = $request->get_param( 'threshold' );
		$kpis[ $kpi_id ]['updated_at'] = current_time( 'mysql' );

		update_option( 'slbp_custom_kpis', $kpis );

		return new WP_REST_Response( array(
			'success' => true,
			'data'    => $kpis[ $kpi_id ],
		), 200 );
	}

	/**
	 * Delete custom KPI.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   WP_REST_Response              Response object.
	 */
	public function delete_custom_kpi( $request ) {
		$kpi_id = $request->get_param( 'id' );
		$kpis = get_option( 'slbp_custom_kpis', array() );

		if ( ! isset( $kpis[ $kpi_id ] ) ) {
			return new WP_REST_Response( array(
				'success' => false,
				'message' => __( 'KPI not found.', 'skylearn-billing-pro' ),
			), 404 );
		}

		unset( $kpis[ $kpi_id ] );
		update_option( 'slbp_custom_kpis', $kpis );

		return new WP_REST_Response( array(
			'success' => true,
			'message' => __( 'KPI deleted successfully.', 'skylearn-billing-pro' ),
		), 200 );
	}
}