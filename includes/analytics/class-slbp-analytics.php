<?php
/**
 * The analytics functionality of the plugin.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/analytics
 */

/**
 * The analytics functionality of the plugin.
 *
 * Defines analytics data collection, processing, and reporting capabilities.
 * Integrates with billing, subscription, and LMS data for comprehensive insights.
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/analytics
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Analytics {

	/**
	 * Cache group for analytics data.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $cache_group    The cache group for analytics.
	 */
	private $cache_group = 'slbp_analytics';

	/**
	 * Cache duration in seconds (1 hour default).
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      int    $cache_duration    Cache duration in seconds.
	 */
	private $cache_duration = 3600;

	/**
	 * Initialize the class and set up hooks.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks for real-time analytics updates.
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		// Hook into payment gateway events for real-time updates
		add_action( 'slbp_payment_completed', array( $this, 'clear_revenue_cache' ) );
		add_action( 'slbp_payment_refunded', array( $this, 'clear_revenue_cache' ) );
		add_action( 'slbp_subscription_created', array( $this, 'clear_subscription_cache' ) );
		add_action( 'slbp_subscription_cancelled', array( $this, 'clear_subscription_cache' ) );
		add_action( 'slbp_user_enrolled', array( $this, 'clear_enrollment_cache' ) );
		add_action( 'slbp_course_completed', array( $this, 'clear_completion_cache' ) );

		// Clear all caches daily
		add_action( 'slbp_daily_cache_cleanup', array( $this, 'clear_all_caches' ) );
	}

	/**
	 * Get key metrics dashboard data.
	 *
	 * @since    1.0.0
	 * @param    array    $filters    Optional filters (date_range, course_id, product_id).
	 * @return   array                Array of key metrics.
	 */
	public function get_dashboard_metrics( $filters = array() ) {
		$cache_key = 'dashboard_metrics_' . md5( serialize( $filters ) );
		$cached_data = wp_cache_get( $cache_key, $this->cache_group );

		if ( false !== $cached_data ) {
			return $cached_data;
		}

		$metrics = array(
			'total_revenue'     => $this->get_total_revenue( $filters ),
			'mrr'              => $this->get_monthly_recurring_revenue( $filters ),
			'active_users'     => $this->get_active_users_count( $filters ),
			'churn_rate'       => $this->get_churn_rate( $filters ),
			'new_signups'      => $this->get_new_signups_count( $filters ),
			'course_completions' => $this->get_course_completions_count( $filters ),
			'refund_rate'      => $this->get_refund_rate( $filters ),
			'top_products'     => $this->get_top_products( $filters ),
		);

		wp_cache_set( $cache_key, $metrics, $this->cache_group, $this->cache_duration );

		return $metrics;
	}

	/**
	 * Get revenue data for charts.
	 *
	 * @since    1.0.0
	 * @param    array    $filters    Filters including date_range, grouping.
	 * @return   array                Revenue chart data.
	 */
	public function get_revenue_chart_data( $filters = array() ) {
		$cache_key = 'revenue_chart_' . md5( serialize( $filters ) );
		$cached_data = wp_cache_get( $cache_key, $this->cache_group );

		if ( false !== $cached_data ) {
			return $cached_data;
		}

		$date_range = $this->parse_date_range( $filters );
		$grouping = isset( $filters['grouping'] ) ? $filters['grouping'] : 'daily';

		// For demo purposes, generate sample data
		$chart_data = $this->generate_sample_revenue_data( $date_range, $grouping );

		wp_cache_set( $cache_key, $chart_data, $this->cache_group, $this->cache_duration );

		return $chart_data;
	}

	/**
	 * Get subscription analytics data.
	 *
	 * @since    1.0.0
	 * @param    array    $filters    Optional filters.
	 * @return   array                Subscription analytics data.
	 */
	public function get_subscription_analytics( $filters = array() ) {
		$cache_key = 'subscription_analytics_' . md5( serialize( $filters ) );
		$cached_data = wp_cache_get( $cache_key, $this->cache_group );

		if ( false !== $cached_data ) {
			return $cached_data;
		}

		$data = array(
			'active_subscriptions' => $this->get_active_subscriptions_count( $filters ),
			'new_subscriptions' => $this->get_new_subscriptions_count( $filters ),
			'cancelled_subscriptions' => $this->get_cancelled_subscriptions_count( $filters ),
			'subscription_churn' => $this->calculate_subscription_churn( $filters ),
			'mrr_breakdown' => $this->get_mrr_breakdown( $filters ),
		);

		wp_cache_set( $cache_key, $data, $this->cache_group, $this->cache_duration );

		return $data;
	}

	/**
	 * Export analytics data to CSV.
	 *
	 * @since    1.0.0
	 * @param    string    $type       Export type (revenue, subscriptions, courses, users).
	 * @param    array     $filters    Optional filters.
	 * @return   string                CSV file path or WP_Error on failure.
	 */
	public function export_to_csv( $type, $filters = array() ) {
		$data = $this->get_export_data( $type, $filters );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$upload_dir = wp_upload_dir();
		$filename = 'slbp-analytics-' . $type . '-' . date( 'Y-m-d-H-i-s' ) . '.csv';
		$file_path = $upload_dir['basedir'] . '/slbp-exports/' . $filename;

		// Create exports directory if it doesn't exist
		wp_mkdir_p( dirname( $file_path ) );

		$file_handle = fopen( $file_path, 'w' );

		if ( false === $file_handle ) {
			return new WP_Error( 'file_creation_failed', __( 'Failed to create export file.', 'skylearn-billing-pro' ) );
		}

		// Write CSV headers
		if ( ! empty( $data ) ) {
			fputcsv( $file_handle, array_keys( $data[0] ) );

			// Write data rows
			foreach ( $data as $row ) {
				fputcsv( $file_handle, $row );
			}
		}

		fclose( $file_handle );

		return $upload_dir['baseurl'] . '/slbp-exports/' . $filename;
	}

	/**
	 * Get total revenue.
	 *
	 * @since    1.0.0
	 * @param    array    $filters    Optional filters.
	 * @return   float                Total revenue amount.
	 */
	private function get_total_revenue( $filters = array() ) {
		// For demo purposes, return sample data
		return 15750.00;
	}

	/**
	 * Get monthly recurring revenue (MRR).
	 *
	 * @since    1.0.0
	 * @param    array    $filters    Optional filters.
	 * @return   float                Monthly recurring revenue.
	 */
	private function get_monthly_recurring_revenue( $filters = array() ) {
		// For demo purposes, return sample data
		return 4200.00;
	}

	/**
	 * Get active users count.
	 *
	 * @since    1.0.0
	 * @param    array    $filters    Optional filters.
	 * @return   int                  Active users count.
	 */
	private function get_active_users_count( $filters = array() ) {
		// For demo purposes, count all users
		$user_count = count_users();
		return $user_count['total_users'];
	}

	/**
	 * Get churn rate.
	 *
	 * @since    1.0.0
	 * @param    array    $filters    Optional filters.
	 * @return   float                Churn rate percentage.
	 */
	private function get_churn_rate( $filters = array() ) {
		// For demo purposes, return sample data
		return 3.2;
	}

	/**
	 * Get new signups count.
	 *
	 * @since    1.0.0
	 * @param    array    $filters    Optional filters.
	 * @return   int                  New signups count.
	 */
	private function get_new_signups_count( $filters = array() ) {
		$date_range = $this->parse_date_range( $filters );

		$args = array(
			'date_query' => array(
				array(
					'after' => $date_range['start'],
					'before' => $date_range['end'],
					'inclusive' => true,
				),
			),
			'count_total' => true,
		);

		$user_query = new WP_User_Query( $args );
		return $user_query->get_total();
	}

	/**
	 * Get course completions count.
	 *
	 * @since    1.0.0
	 * @param    array    $filters    Optional filters.
	 * @return   int                  Course completions count.
	 */
	private function get_course_completions_count( $filters = array() ) {
		// For demo purposes, return sample data
		return 89;
	}

	/**
	 * Get refund rate.
	 *
	 * @since    1.0.0
	 * @param    array    $filters    Optional filters.
	 * @return   float                Refund rate percentage.
	 */
	private function get_refund_rate( $filters = array() ) {
		// For demo purposes, return sample data
		return 1.8;
	}

	/**
	 * Get top products.
	 *
	 * @since    1.0.0
	 * @param    array    $filters    Optional filters.
	 * @param    int      $limit      Number of top products to return.
	 * @return   array                Top products data.
	 */
	private function get_top_products( $filters = array(), $limit = 5 ) {
		// For demo purposes, return sample data
		return array(
			(object) array(
				'product_id' => 1,
				'product_name' => 'Advanced WordPress Development',
				'total_revenue' => 5200.00,
				'total_sales' => 26,
			),
			(object) array(
				'product_id' => 2,
				'product_name' => 'LearnDash Mastery Course',
				'total_revenue' => 3800.00,
				'total_sales' => 19,
			),
			(object) array(
				'product_id' => 3,
				'product_name' => 'E-commerce with WooCommerce',
				'total_revenue' => 2900.00,
				'total_sales' => 15,
			),
		);
	}

	/**
	 * Generate sample revenue data for demonstration.
	 *
	 * @since    1.0.0
	 * @param    array    $date_range    Date range array.
	 * @param    string   $grouping      Grouping type.
	 * @return   array                   Chart data.
	 */
	private function generate_sample_revenue_data( $date_range, $grouping ) {
		$chart_data = array(
			'labels' => array(),
			'datasets' => array(
				array(
					'label' => __( 'Revenue', 'skylearn-billing-pro' ),
					'data' => array(),
					'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
					'borderColor' => 'rgba(54, 162, 235, 1)',
					'borderWidth' => 2,
					'fill' => true,
				),
			),
		);

		// Generate sample data for the last 30 days
		for ( $i = 29; $i >= 0; $i-- ) {
			$date = date( 'Y-m-d', strtotime( "-{$i} days" ) );
			$chart_data['labels'][] = date( 'M j', strtotime( $date ) );
			$chart_data['datasets'][0]['data'][] = rand( 100, 800 );
		}

		return $chart_data;
	}

	/**
	 * Parse date range from filters.
	 *
	 * @since    1.0.0
	 * @param    array    $filters    Filters array.
	 * @return   array                Start and end dates.
	 */
	private function parse_date_range( $filters = array() ) {
		$default_start = date( 'Y-m-01' ); // Start of current month
		$default_end = date( 'Y-m-d' );   // Today

		if ( isset( $filters['date_range'] ) ) {
			switch ( $filters['date_range'] ) {
				case 'last_7_days':
					$start = date( 'Y-m-d', strtotime( '-7 days' ) );
					$end = date( 'Y-m-d' );
					break;
				case 'last_30_days':
					$start = date( 'Y-m-d', strtotime( '-30 days' ) );
					$end = date( 'Y-m-d' );
					break;
				case 'this_month':
					$start = date( 'Y-m-01' );
					$end = date( 'Y-m-d' );
					break;
				case 'last_month':
					$start = date( 'Y-m-01', strtotime( 'first day of last month' ) );
					$end = date( 'Y-m-t', strtotime( 'last day of last month' ) );
					break;
				case 'this_year':
					$start = date( 'Y-01-01' );
					$end = date( 'Y-m-d' );
					break;
				case 'custom':
					$start = isset( $filters['start_date'] ) ? $filters['start_date'] : $default_start;
					$end = isset( $filters['end_date'] ) ? $filters['end_date'] : $default_end;
					break;
				default:
					$start = $default_start;
					$end = $default_end;
			}
		} else {
			$start = $default_start;
			$end = $default_end;
		}

		return array(
			'start' => $start,
			'end' => $end,
		);
	}

	/**
	 * Clear revenue-related caches.
	 *
	 * @since    1.0.0
	 */
	public function clear_revenue_cache() {
		wp_cache_delete_group( $this->cache_group );
	}

	/**
	 * Clear subscription-related caches.
	 *
	 * @since    1.0.0
	 */
	public function clear_subscription_cache() {
		wp_cache_delete_group( $this->cache_group );
	}

	/**
	 * Clear enrollment-related caches.
	 *
	 * @since    1.0.0
	 */
	public function clear_enrollment_cache() {
		wp_cache_delete_group( $this->cache_group );
	}

	/**
	 * Clear completion-related caches.
	 *
	 * @since    1.0.0
	 */
	public function clear_completion_cache() {
		wp_cache_delete_group( $this->cache_group );
	}

	/**
	 * Clear all analytics caches.
	 *
	 * @since    1.0.0
	 */
	public function clear_all_caches() {
		wp_cache_delete_group( $this->cache_group );
	}

	/**
	 * Helper methods for specific analytics calculations.
	 * These are placeholder implementations with sample data.
	 */

	private function get_active_subscriptions_count( $filters = array(), $period = 'current' ) {
		return 42;
	}

	private function get_new_subscriptions_count( $filters = array() ) {
		return 8;
	}

	private function get_cancelled_subscriptions_count( $filters = array() ) {
		return 3;
	}

	private function calculate_subscription_churn( $filters = array() ) {
		return 7.1;
	}

	private function get_mrr_breakdown( $filters = array() ) {
		return array(
			'basic_plan' => 1200.00,
			'pro_plan' => 2000.00,
			'enterprise_plan' => 1000.00,
		);
	}

	private function get_export_data( $type, $filters = array() ) {
		// For demo purposes, return sample export data
		switch ( $type ) {
			case 'revenue':
				return array(
					array(
						'Date' => '2024-01-01',
						'Revenue' => '150.00',
						'Transactions' => '3',
					),
					array(
						'Date' => '2024-01-02',
						'Revenue' => '200.00',
						'Transactions' => '4',
					),
				);
			default:
				return new WP_Error( 'invalid_export_type', __( 'Invalid export type.', 'skylearn-billing-pro' ) );
		}
	}
}