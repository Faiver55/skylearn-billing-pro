<?php
/**
 * Provide a admin area view for the plugin dashboard
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/admin/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access is forbidden.' );
}

// Get current user
$current_user = wp_get_current_user();

// Get some basic stats (placeholder data for now)
$stats = array(
	'total_revenue'     => '$0.00',
	'active_students'   => '0',
	'total_subscriptions' => '0',
	'courses_enrolled'  => '0',
);

// Get recent activity (placeholder for now)
$recent_activities = array();

// Check plugin setup status
$setup_status = array(
	'payment_gateway' => false,
	'lms_integration' => false,
	'product_mapping' => false,
	'email_settings'  => false,
);

// Check if Lemon Squeezy is configured
$payment_settings = get_option( 'slbp_payment_settings', array() );
$setup_status['payment_gateway'] = ! empty( $payment_settings['lemon_squeezy_api_key'] ) && ! empty( $payment_settings['lemon_squeezy_store_id'] );

// Check if LearnDash is enabled
$lms_settings = get_option( 'slbp_lms_settings', array() );
$setup_status['lms_integration'] = ! empty( $lms_settings['learndash_enabled'] ) && class_exists( 'SFWD_LMS' );

// Check if product mappings exist
$product_settings = get_option( 'slbp_product_settings', array() );
$setup_status['product_mapping'] = ! empty( $product_settings['product_mappings'] ) && is_array( $product_settings['product_mappings'] );

// Check if email settings are configured
$email_settings = get_option( 'slbp_email_settings', array() );
$setup_status['email_settings'] = ! empty( $email_settings['email_notifications_enabled'] );

$setup_complete = array_filter( $setup_status );
$setup_progress = count( $setup_complete ) / count( $setup_status ) * 100;
?>

<div class="wrap slbp-dashboard">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<!-- Welcome Section -->
	<div class="slbp-welcome-section">
		<div class="slbp-card slbp-welcome-card">
			<div class="slbp-welcome-content">
				<h2><?php printf( esc_html__( 'Welcome back, %s!', 'skylearn-billing-pro' ), esc_html( $current_user->display_name ) ); ?></h2>
				<p><?php esc_html_e( 'Manage your SkyLearn Billing Pro settings and monitor your revenue performance.', 'skylearn-billing-pro' ); ?></p>
				
				<!-- Setup Progress -->
				<div class="slbp-setup-progress">
					<h3><?php esc_html_e( 'Setup Progress', 'skylearn-billing-pro' ); ?></h3>
					<div class="slbp-progress-bar">
						<div class="slbp-progress-fill" style="width: <?php echo esc_attr( $setup_progress ); ?>%"></div>
					</div>
					<span class="slbp-progress-text"><?php echo esc_html( round( $setup_progress ) ); ?>% <?php esc_html_e( 'Complete', 'skylearn-billing-pro' ); ?></span>
					
					<div class="slbp-setup-checklist">
						<div class="slbp-setup-item <?php echo $setup_status['payment_gateway'] ? 'completed' : 'pending'; ?>">
							<span class="slbp-setup-icon"><?php echo $setup_status['payment_gateway'] ? '✓' : '○'; ?></span>
							<span><?php esc_html_e( 'Payment Gateway Configuration', 'skylearn-billing-pro' ); ?></span>
							<?php if ( ! $setup_status['payment_gateway'] ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=slbp-settings&tab=payment' ) ); ?>" class="slbp-setup-link"><?php esc_html_e( 'Configure', 'skylearn-billing-pro' ); ?></a>
							<?php endif; ?>
						</div>
						
						<div class="slbp-setup-item <?php echo $setup_status['lms_integration'] ? 'completed' : 'pending'; ?>">
							<span class="slbp-setup-icon"><?php echo $setup_status['lms_integration'] ? '✓' : '○'; ?></span>
							<span><?php esc_html_e( 'LMS Integration', 'skylearn-billing-pro' ); ?></span>
							<?php if ( ! $setup_status['lms_integration'] ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=slbp-settings&tab=lms' ) ); ?>" class="slbp-setup-link"><?php esc_html_e( 'Configure', 'skylearn-billing-pro' ); ?></a>
							<?php endif; ?>
						</div>
						
						<div class="slbp-setup-item <?php echo $setup_status['product_mapping'] ? 'completed' : 'pending'; ?>">
							<span class="slbp-setup-icon"><?php echo $setup_status['product_mapping'] ? '✓' : '○'; ?></span>
							<span><?php esc_html_e( 'Product Mapping', 'skylearn-billing-pro' ); ?></span>
							<?php if ( ! $setup_status['product_mapping'] ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=slbp-settings&tab=products' ) ); ?>" class="slbp-setup-link"><?php esc_html_e( 'Configure', 'skylearn-billing-pro' ); ?></a>
							<?php endif; ?>
						</div>
						
						<div class="slbp-setup-item <?php echo $setup_status['email_settings'] ? 'completed' : 'pending'; ?>">
							<span class="slbp-setup-icon"><?php echo $setup_status['email_settings'] ? '✓' : '○'; ?></span>
							<span><?php esc_html_e( 'Email Settings', 'skylearn-billing-pro' ); ?></span>
							<?php if ( ! $setup_status['email_settings'] ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=slbp-settings&tab=email' ) ); ?>" class="slbp-setup-link"><?php esc_html_e( 'Configure', 'skylearn-billing-pro' ); ?></a>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Stats Cards -->
	<div class="slbp-stats-grid">
		<div class="slbp-card slbp-stat-card">
			<div class="slbp-stat-icon slbp-stat-revenue">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.67v-1.93c-1.71-.36-3.16-1.46-3.27-3.4h1.96c.1 1.05.82 1.87 2.65 1.87 1.96 0 2.4-.98 2.4-1.59 0-.83-.44-1.61-2.67-2.14-2.48-.6-4.18-1.62-4.18-3.67 0-1.72 1.39-2.84 3.11-3.21V4h2.67v1.95c1.86.45 2.79 1.86 2.85 3.39H14.3c-.05-1.11-.64-1.87-2.22-1.87-1.5 0-2.4.68-2.4 1.64 0 .84.65 1.39 2.67 1.91s4.18 1.39 4.18 3.91c-.01 1.83-1.38 2.83-3.12 3.16z" fill="currentColor"/>
				</svg>
			</div>
			<div class="slbp-stat-content">
				<div class="slbp-stat-value"><?php echo esc_html( $stats['total_revenue'] ); ?></div>
				<div class="slbp-stat-label"><?php esc_html_e( 'Total Revenue', 'skylearn-billing-pro' ); ?></div>
			</div>
		</div>

		<div class="slbp-card slbp-stat-card">
			<div class="slbp-stat-icon slbp-stat-students">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M16 4c0-1.11.89-2 2-2s2 .89 2 2-.89 2-2 2-2-.89-2-2zm4 18v-6h2.5l-2.54-7.63A1.5 1.5 0 0 0 18.54 8H17c-.8 0-1.54.37-2.01 1.01L13 12l2.5 3.5.9-1.4 1.1 1.4v4h3zM12.5 11.5c.83 0 1.5-.67 1.5-1.5s-.67-1.5-1.5-1.5S11 9.17 11 10s.67 1.5 1.5 1.5zm1.5 1h-3c-.8 0-1.54.37-2.01 1.01L7 16l2.5 3.5.9-1.4 1.1 1.4v4h3v-6h2.5l-2.54-7.63z" fill="currentColor"/>
				</svg>
			</div>
			<div class="slbp-stat-content">
				<div class="slbp-stat-value"><?php echo esc_html( $stats['active_students'] ); ?></div>
				<div class="slbp-stat-label"><?php esc_html_e( 'Active Students', 'skylearn-billing-pro' ); ?></div>
			</div>
		</div>

		<div class="slbp-card slbp-stat-card">
			<div class="slbp-stat-icon slbp-stat-subscriptions">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" fill="currentColor"/>
				</svg>
			</div>
			<div class="slbp-stat-content">
				<div class="slbp-stat-value"><?php echo esc_html( $stats['total_subscriptions'] ); ?></div>
				<div class="slbp-stat-label"><?php esc_html_e( 'Active Subscriptions', 'skylearn-billing-pro' ); ?></div>
			</div>
		</div>

		<div class="slbp-card slbp-stat-card">
			<div class="slbp-stat-icon slbp-stat-courses">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z" fill="currentColor"/>
				</svg>
			</div>
			<div class="slbp-stat-content">
				<div class="slbp-stat-value"><?php echo esc_html( $stats['courses_enrolled'] ); ?></div>
				<div class="slbp-stat-label"><?php esc_html_e( 'Course Enrollments', 'skylearn-billing-pro' ); ?></div>
			</div>
		</div>
	</div>

	<!-- Main Content Grid -->
	<div class="slbp-main-grid">
		<!-- Recent Activity -->
		<div class="slbp-card slbp-activity-card">
			<h3><?php esc_html_e( 'Recent Activity', 'skylearn-billing-pro' ); ?></h3>
			
			<?php if ( empty( $recent_activities ) ) : ?>
				<div class="slbp-empty-state">
					<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="currentColor" opacity="0.3"/>
					</svg>
					<p><?php esc_html_e( 'No recent activity to display.', 'skylearn-billing-pro' ); ?></p>
					<p><?php esc_html_e( 'Activity will appear here once you start processing payments.', 'skylearn-billing-pro' ); ?></p>
				</div>
			<?php else : ?>
				<div class="slbp-activity-list">
					<?php foreach ( $recent_activities as $activity ) : ?>
						<div class="slbp-activity-item">
							<div class="slbp-activity-icon"><?php echo esc_html( $activity['icon'] ); ?></div>
							<div class="slbp-activity-content">
								<div class="slbp-activity-title"><?php echo esc_html( $activity['title'] ); ?></div>
								<div class="slbp-activity-meta"><?php echo esc_html( $activity['time'] ); ?></div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<!-- System Health -->
		<div class="slbp-card slbp-health-card">
			<h3><?php esc_html_e( 'System Health', 'skylearn-billing-pro' ); ?></h3>
			
			<div class="slbp-health-check">
				<div class="slbp-health-item">
					<span class="slbp-health-icon slbp-health-success">✓</span>
					<span><?php esc_html_e( 'Plugin Active', 'skylearn-billing-pro' ); ?></span>
				</div>
				
				<div class="slbp-health-item">
					<span class="slbp-health-icon <?php echo class_exists( 'SFWD_LMS' ) ? 'slbp-health-success' : 'slbp-health-warning'; ?>">
						<?php echo class_exists( 'SFWD_LMS' ) ? '✓' : '⚠'; ?>
					</span>
					<span>
						<?php esc_html_e( 'LearnDash LMS', 'skylearn-billing-pro' ); ?>
						<?php if ( ! class_exists( 'SFWD_LMS' ) ) : ?>
							<small><?php esc_html_e( '(Not Detected)', 'skylearn-billing-pro' ); ?></small>
						<?php endif; ?>
					</span>
				</div>
				
				<div class="slbp-health-item">
					<span class="slbp-health-icon <?php echo $setup_status['payment_gateway'] ? 'slbp-health-success' : 'slbp-health-warning'; ?>">
						<?php echo $setup_status['payment_gateway'] ? '✓' : '⚠'; ?>
					</span>
					<span>
						<?php esc_html_e( 'Payment Gateway', 'skylearn-billing-pro' ); ?>
						<?php if ( ! $setup_status['payment_gateway'] ) : ?>
							<small><?php esc_html_e( '(Not Configured)', 'skylearn-billing-pro' ); ?></small>
						<?php endif; ?>
					</span>
				</div>
				
				<div class="slbp-health-item">
					<span class="slbp-health-icon slbp-health-success">✓</span>
					<span><?php esc_html_e( 'Database Connection', 'skylearn-billing-pro' ); ?></span>
				</div>
			</div>
		</div>
	</div>

	<!-- Quick Actions -->
	<div class="slbp-quick-actions">
		<h3><?php esc_html_e( 'Quick Actions', 'skylearn-billing-pro' ); ?></h3>
		<div class="slbp-actions-grid">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=slbp-settings' ) ); ?>" class="slbp-action-button">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.07-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61 l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41 h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.35C8.66,5.59,8.12,5.92,7.63,6.29L5.24,5.33c-0.22-0.08-0.47,0-0.59,0.22L2.74,8.87 C2.62,9.08,2.66,9.34,2.86,9.48l2.03,1.58C4.84,11.36,4.82,11.69,4.82,12s0.02,0.64,0.07,0.94l-2.03,1.58 c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54 c0.05,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.44-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96 c0.22,0.08,0.47,0,0.59-0.22l1.92-3.32c0.12-0.22,0.07-0.47-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6 s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z" fill="currentColor"/>
				</svg>
				<?php esc_html_e( 'Configure Settings', 'skylearn-billing-pro' ); ?>
			</a>
			
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=slbp-analytics' ) ); ?>" class="slbp-action-button">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M16,6l2.29,2.29-4.88,4.88-4-4L2,16.59L3.41,18l6-6,4,4,6.3-6.29L22,12V6H16z" fill="currentColor"/>
				</svg>
				<?php esc_html_e( 'View Analytics', 'skylearn-billing-pro' ); ?>
			</a>
			
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=slbp-help' ) ); ?>" class="slbp-action-button">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M11,18h2v-2h-2v2zM12,2C6.48,2,2,6.48,2,12s4.48,10,10,10s10-4.48,10-10S17.52,2,12,2zM13,16h-2v-6h2v6zM13,8h-2V6h2V8z" fill="currentColor"/>
				</svg>
				<?php esc_html_e( 'Get Help', 'skylearn-billing-pro' ); ?>
			</a>
		</div>
	</div>
</div>