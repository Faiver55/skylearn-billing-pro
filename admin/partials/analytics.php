<?php
/**
 * Analytics dashboard admin partial.
 *
 * This file is used to markup the analytics dashboard page.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/admin/partials
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Initialize analytics admin
$analytics_admin = new SLBP_Analytics_Admin();
?>

<div class="wrap slbp-analytics-dashboard">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Analytics Dashboard', 'skylearn-billing-pro' ); ?></h1>
	
	<!-- Date Range Filter -->
	<div class="slbp-analytics-filters">
		<div class="slbp-filter-group">
			<label for="slbp-date-range"><?php esc_html_e( 'Date Range:', 'skylearn-billing-pro' ); ?></label>
			<select id="slbp-date-range" name="date_range">
				<option value="last_7_days"><?php esc_html_e( 'Last 7 Days', 'skylearn-billing-pro' ); ?></option>
				<option value="last_30_days" selected><?php esc_html_e( 'Last 30 Days', 'skylearn-billing-pro' ); ?></option>
				<option value="this_month"><?php esc_html_e( 'This Month', 'skylearn-billing-pro' ); ?></option>
				<option value="last_month"><?php esc_html_e( 'Last Month', 'skylearn-billing-pro' ); ?></option>
				<option value="this_year"><?php esc_html_e( 'This Year', 'skylearn-billing-pro' ); ?></option>
				<option value="custom"><?php esc_html_e( 'Custom Range', 'skylearn-billing-pro' ); ?></option>
			</select>
		</div>
		
		<div class="slbp-filter-group slbp-custom-date-range" style="display: none;">
			<label for="slbp-start-date"><?php esc_html_e( 'Start Date:', 'skylearn-billing-pro' ); ?></label>
			<input type="date" id="slbp-start-date" name="start_date">
			<label for="slbp-end-date"><?php esc_html_e( 'End Date:', 'skylearn-billing-pro' ); ?></label>
			<input type="date" id="slbp-end-date" name="end_date">
		</div>
		
		<div class="slbp-filter-group">
			<button type="button" id="slbp-refresh-analytics" class="button button-primary">
				<?php esc_html_e( 'Refresh Data', 'skylearn-billing-pro' ); ?>
			</button>
		</div>
		
		<div class="slbp-filter-group">
			<div class="slbp-export-dropdown">
				<button type="button" class="button" id="slbp-export-btn">
					<?php esc_html_e( 'Export Data', 'skylearn-billing-pro' ); ?>
					<span class="dashicons dashicons-arrow-down-alt2"></span>
				</button>
				<div class="slbp-export-menu">
					<a href="#" data-export="revenue"><?php esc_html_e( 'Export Revenue Data', 'skylearn-billing-pro' ); ?></a>
					<a href="#" data-export="subscriptions"><?php esc_html_e( 'Export Subscriptions', 'skylearn-billing-pro' ); ?></a>
					<a href="#" data-export="courses"><?php esc_html_e( 'Export Course Data', 'skylearn-billing-pro' ); ?></a>
					<a href="#" data-export="users"><?php esc_html_e( 'Export User Data', 'skylearn-billing-pro' ); ?></a>
				</div>
			</div>
		</div>
	</div>

	<!-- Key Metrics Cards -->
	<div class="slbp-metrics-grid">
		<div class="slbp-metric-card" id="total-revenue-card">
			<div class="slbp-metric-icon">
				<span class="dashicons dashicons-money-alt"></span>
			</div>
			<div class="slbp-metric-content">
				<h3><?php esc_html_e( 'Total Revenue', 'skylearn-billing-pro' ); ?></h3>
				<div class="slbp-metric-value" data-metric="total_revenue">
					<span class="slbp-loading-spinner"></span>
				</div>
				<div class="slbp-metric-change">
					<span class="slbp-change-indicator"></span>
				</div>
			</div>
		</div>

		<div class="slbp-metric-card" id="mrr-card">
			<div class="slbp-metric-icon">
				<span class="dashicons dashicons-update"></span>
			</div>
			<div class="slbp-metric-content">
				<h3><?php esc_html_e( 'Monthly Recurring Revenue', 'skylearn-billing-pro' ); ?></h3>
				<div class="slbp-metric-value" data-metric="mrr">
					<span class="slbp-loading-spinner"></span>
				</div>
				<div class="slbp-metric-change">
					<span class="slbp-change-indicator"></span>
				</div>
			</div>
		</div>

		<div class="slbp-metric-card" id="active-users-card">
			<div class="slbp-metric-icon">
				<span class="dashicons dashicons-admin-users"></span>
			</div>
			<div class="slbp-metric-content">
				<h3><?php esc_html_e( 'Active Users', 'skylearn-billing-pro' ); ?></h3>
				<div class="slbp-metric-value" data-metric="active_users">
					<span class="slbp-loading-spinner"></span>
				</div>
				<div class="slbp-metric-change">
					<span class="slbp-change-indicator"></span>
				</div>
			</div>
		</div>

		<div class="slbp-metric-card" id="churn-rate-card">
			<div class="slbp-metric-icon">
				<span class="dashicons dashicons-chart-line"></span>
			</div>
			<div class="slbp-metric-content">
				<h3><?php esc_html_e( 'Churn Rate', 'skylearn-billing-pro' ); ?></h3>
				<div class="slbp-metric-value" data-metric="churn_rate">
					<span class="slbp-loading-spinner"></span>
				</div>
				<div class="slbp-metric-change">
					<span class="slbp-change-indicator"></span>
				</div>
			</div>
		</div>

		<div class="slbp-metric-card" id="new-signups-card">
			<div class="slbp-metric-icon">
				<span class="dashicons dashicons-plus-alt"></span>
			</div>
			<div class="slbp-metric-content">
				<h3><?php esc_html_e( 'New Signups', 'skylearn-billing-pro' ); ?></h3>
				<div class="slbp-metric-value" data-metric="new_signups">
					<span class="slbp-loading-spinner"></span>
				</div>
				<div class="slbp-metric-change">
					<span class="slbp-change-indicator"></span>
				</div>
			</div>
		</div>

		<div class="slbp-metric-card" id="course-completions-card">
			<div class="slbp-metric-icon">
				<span class="dashicons dashicons-awards"></span>
			</div>
			<div class="slbp-metric-content">
				<h3><?php esc_html_e( 'Course Completions', 'skylearn-billing-pro' ); ?></h3>
				<div class="slbp-metric-value" data-metric="course_completions">
					<span class="slbp-loading-spinner"></span>
				</div>
				<div class="slbp-metric-change">
					<span class="slbp-change-indicator"></span>
				</div>
			</div>
		</div>

		<div class="slbp-metric-card" id="refund-rate-card">
			<div class="slbp-metric-icon">
				<span class="dashicons dashicons-undo"></span>
			</div>
			<div class="slbp-metric-content">
				<h3><?php esc_html_e( 'Refund Rate', 'skylearn-billing-pro' ); ?></h3>
				<div class="slbp-metric-value" data-metric="refund_rate">
					<span class="slbp-loading-spinner"></span>
				</div>
				<div class="slbp-metric-change">
					<span class="slbp-change-indicator"></span>
				</div>
			</div>
		</div>

		<div class="slbp-metric-card slbp-metric-card-wide" id="top-products-card">
			<div class="slbp-metric-content">
				<h3><?php esc_html_e( 'Top Products', 'skylearn-billing-pro' ); ?></h3>
				<div class="slbp-top-products-list" data-metric="top_products">
					<span class="slbp-loading-spinner"></span>
				</div>
			</div>
		</div>
	</div>

	<!-- Charts Section -->
	<div class="slbp-charts-section">
		<div class="slbp-chart-container">
			<div class="slbp-chart-header">
				<h3><?php esc_html_e( 'Revenue Overview', 'skylearn-billing-pro' ); ?></h3>
				<div class="slbp-chart-controls">
					<select id="slbp-chart-grouping">
						<option value="daily"><?php esc_html_e( 'Daily', 'skylearn-billing-pro' ); ?></option>
						<option value="weekly"><?php esc_html_e( 'Weekly', 'skylearn-billing-pro' ); ?></option>
						<option value="monthly"><?php esc_html_e( 'Monthly', 'skylearn-billing-pro' ); ?></option>
					</select>
				</div>
			</div>
			<div class="slbp-chart-wrapper">
				<canvas id="slbp-revenue-chart"></canvas>
				<div class="slbp-chart-loading">
					<span class="slbp-loading-spinner"></span>
					<p><?php esc_html_e( 'Loading chart data...', 'skylearn-billing-pro' ); ?></p>
				</div>
			</div>
		</div>

		<div class="slbp-chart-container">
			<div class="slbp-chart-header">
				<h3><?php esc_html_e( 'Subscription Analytics', 'skylearn-billing-pro' ); ?></h3>
			</div>
			<div class="slbp-chart-wrapper">
				<canvas id="slbp-subscription-chart"></canvas>
				<div class="slbp-chart-loading">
					<span class="slbp-loading-spinner"></span>
					<p><?php esc_html_e( 'Loading subscription data...', 'skylearn-billing-pro' ); ?></p>
				</div>
			</div>
		</div>
	</div>

	<!-- Quick Reports Section -->
	<div class="slbp-quick-reports">
		<h2><?php esc_html_e( 'Quick Reports', 'skylearn-billing-pro' ); ?></h2>
		<div class="slbp-reports-grid">
			<div class="slbp-report-card">
				<h4><?php esc_html_e( 'Revenue by Product', 'skylearn-billing-pro' ); ?></h4>
				<p><?php esc_html_e( 'Detailed breakdown of revenue by product and course.', 'skylearn-billing-pro' ); ?></p>
				<button type="button" class="button" data-report="revenue-by-product">
					<?php esc_html_e( 'View Report', 'skylearn-billing-pro' ); ?>
				</button>
			</div>
			
			<div class="slbp-report-card">
				<h4><?php esc_html_e( 'User Activity', 'skylearn-billing-pro' ); ?></h4>
				<p><?php esc_html_e( 'Analyze user engagement and course progress.', 'skylearn-billing-pro' ); ?></p>
				<button type="button" class="button" data-report="user-activity">
					<?php esc_html_e( 'View Report', 'skylearn-billing-pro' ); ?>
				</button>
			</div>
			
			<div class="slbp-report-card">
				<h4><?php esc_html_e( 'Subscription Trends', 'skylearn-billing-pro' ); ?></h4>
				<p><?php esc_html_e( 'Track subscription growth and churn patterns.', 'skylearn-billing-pro' ); ?></p>
				<button type="button" class="button" data-report="subscription-trends">
					<?php esc_html_e( 'View Report', 'skylearn-billing-pro' ); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- Hidden form for exporting -->
	<form id="slbp-export-form" method="post" style="display: none;">
		<?php wp_nonce_field( 'slbp_analytics_nonce', 'nonce' ); ?>
		<input type="hidden" name="action" value="slbp_export_analytics">
		<input type="hidden" name="export_type" id="export-type">
		<input type="hidden" name="date_range" id="export-date-range">
		<input type="hidden" name="start_date" id="export-start-date">
		<input type="hidden" name="end_date" id="export-end-date">
	</form>
</div>

<!-- JavaScript variables for AJAX -->
<script type="text/javascript">
	var slbpAnalytics = {
		ajaxUrl: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
		nonce: '<?php echo esc_js( wp_create_nonce( 'slbp_analytics_nonce' ) ); ?>',
		strings: {
			loadingError: '<?php echo esc_js( __( 'Error loading data. Please try again.', 'skylearn-billing-pro' ) ); ?>',
			exportSuccess: '<?php echo esc_js( __( 'Export completed successfully!', 'skylearn-billing-pro' ) ); ?>',
			exportError: '<?php echo esc_js( __( 'Export failed. Please try again.', 'skylearn-billing-pro' ) ); ?>'
		}
	};
</script>