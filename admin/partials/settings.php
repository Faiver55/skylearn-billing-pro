<?php
/**
 * Provide a admin area view for the plugin settings
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

// Get current tab
$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';

// Define tabs
$tabs = array(
	'general'  => esc_html__( 'General', 'skylearn-billing-pro' ),
	'payment'  => esc_html__( 'Payment Gateways', 'skylearn-billing-pro' ),
	'lms'      => esc_html__( 'LMS Integration', 'skylearn-billing-pro' ),
	'products' => esc_html__( 'Product Mapping', 'skylearn-billing-pro' ),
	'email'    => esc_html__( 'Email Settings', 'skylearn-billing-pro' ),
	'advanced' => esc_html__( 'Advanced', 'skylearn-billing-pro' ),
);

// Get settings groups
$settings_groups = array(
	'general'  => 'slbp_general_settings',
	'payment'  => 'slbp_payment_settings',
	'lms'      => 'slbp_lms_settings',
	'products' => 'slbp_product_settings',
	'email'    => 'slbp_email_settings',
	'advanced' => 'slbp_advanced_settings',
);

$current_group = isset( $settings_groups[ $current_tab ] ) ? $settings_groups[ $current_tab ] : 'slbp_general_settings';
?>

<div class="wrap slbp-settings">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<!-- Tab Navigation -->
	<nav class="slbp-tab-nav">
		<?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=slbp-settings&tab=' . $tab_key ) ); ?>" 
			   class="slbp-tab-link <?php echo ( $current_tab === $tab_key ) ? 'active' : ''; ?>">
				<?php echo esc_html( $tab_label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<!-- Settings Form -->
	<div class="slbp-tab-content">
		<form method="post" action="options.php" class="slbp-settings-form">
			<?php
			settings_fields( $current_group );
			do_settings_sections( $current_group );
			?>

			<div class="slbp-card">
				<?php if ( $current_tab === 'general' ) : ?>
					<h2><?php esc_html_e( 'General Settings', 'skylearn-billing-pro' ); ?></h2>
					<p class="slbp-tab-description"><?php esc_html_e( 'Configure basic plugin settings and preferences.', 'skylearn-billing-pro' ); ?></p>
					
					<?php $options = get_option( 'slbp_general_settings', array() ); ?>
					
					<table class="form-table slbp-form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable Plugin', 'skylearn-billing-pro' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="slbp_general_settings[plugin_enabled]" value="1" <?php checked( isset( $options['plugin_enabled'] ) ? $options['plugin_enabled'] : 0, 1 ); ?> />
									<?php esc_html_e( 'Enable SkyLearn Billing Pro functionality', 'skylearn-billing-pro' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Disable this to temporarily turn off all plugin functionality.', 'skylearn-billing-pro' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Debug Mode', 'skylearn-billing-pro' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="slbp_general_settings[debug_mode]" value="1" <?php checked( isset( $options['debug_mode'] ) ? $options['debug_mode'] : 0, 1 ); ?> />
									<?php esc_html_e( 'Enable debug mode', 'skylearn-billing-pro' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Enable detailed logging for troubleshooting. Should be disabled in production.', 'skylearn-billing-pro' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Log Level', 'skylearn-billing-pro' ); ?></th>
							<td>
								<select name="slbp_general_settings[log_level]">
									<option value="error" <?php selected( isset( $options['log_level'] ) ? $options['log_level'] : '', 'error' ); ?>><?php esc_html_e( 'Error', 'skylearn-billing-pro' ); ?></option>
									<option value="warning" <?php selected( isset( $options['log_level'] ) ? $options['log_level'] : '', 'warning' ); ?>><?php esc_html_e( 'Warning', 'skylearn-billing-pro' ); ?></option>
									<option value="info" <?php selected( isset( $options['log_level'] ) ? $options['log_level'] : '', 'info' ); ?>><?php esc_html_e( 'Info', 'skylearn-billing-pro' ); ?></option>
									<option value="debug" <?php selected( isset( $options['log_level'] ) ? $options['log_level'] : '', 'debug' ); ?>><?php esc_html_e( 'Debug', 'skylearn-billing-pro' ); ?></option>
								</select>
								<p class="description"><?php esc_html_e( 'Set the minimum level for log entries.', 'skylearn-billing-pro' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Auto Enrollment', 'skylearn-billing-pro' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="slbp_general_settings[auto_enrollment]" value="1" <?php checked( isset( $options['auto_enrollment'] ) ? $options['auto_enrollment'] : 0, 1 ); ?> />
									<?php esc_html_e( 'Automatically enroll students upon successful payment', 'skylearn-billing-pro' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'When enabled, students will be automatically enrolled in courses after payment.', 'skylearn-billing-pro' ); ?></p>
							</td>
						</tr>
					</table>

				<?php elseif ( $current_tab === 'payment' ) : ?>
					<h2><?php esc_html_e( 'Payment Gateway Settings', 'skylearn-billing-pro' ); ?></h2>
					<p class="slbp-tab-description"><?php esc_html_e( 'Configure your payment gateway settings and API credentials.', 'skylearn-billing-pro' ); ?></p>
					
					<?php $options = get_option( 'slbp_payment_settings', array() ); ?>
					
					<table class="form-table slbp-form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Lemon Squeezy API Key', 'skylearn-billing-pro' ); ?></th>
							<td>
								<input type="password" name="slbp_payment_settings[lemon_squeezy_api_key]" value="<?php echo esc_attr( isset( $options['lemon_squeezy_api_key'] ) ? $options['lemon_squeezy_api_key'] : '' ); ?>" class="regular-text" />
								<p class="description"><?php esc_html_e( 'Your Lemon Squeezy API key. You can find this in your Lemon Squeezy dashboard.', 'skylearn-billing-pro' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Lemon Squeezy Store ID', 'skylearn-billing-pro' ); ?></th>
							<td>
								<input type="text" name="slbp_payment_settings[lemon_squeezy_store_id]" value="<?php echo esc_attr( isset( $options['lemon_squeezy_store_id'] ) ? $options['lemon_squeezy_store_id'] : '' ); ?>" class="regular-text" />
								<p class="description"><?php esc_html_e( 'Your Lemon Squeezy store ID.', 'skylearn-billing-pro' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Test Mode', 'skylearn-billing-pro' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="slbp_payment_settings[lemon_squeezy_test_mode]" value="1" <?php checked( isset( $options['lemon_squeezy_test_mode'] ) ? $options['lemon_squeezy_test_mode'] : 0, 1 ); ?> />
									<?php esc_html_e( 'Enable test mode for Lemon Squeezy integration', 'skylearn-billing-pro' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Enable this for testing. Disable for live transactions.', 'skylearn-billing-pro' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Webhook Secret', 'skylearn-billing-pro' ); ?></th>
							<td>
								<input type="password" name="slbp_payment_settings[webhook_secret]" value="<?php echo esc_attr( isset( $options['webhook_secret'] ) ? $options['webhook_secret'] : '' ); ?>" class="regular-text" />
								<p class="description"><?php esc_html_e( 'Secret key for webhook verification. Generate a secure random string.', 'skylearn-billing-pro' ); ?></p>
							</td>
						</tr>
					</table>

					<div class="slbp-connection-test">
						<h3><?php esc_html_e( 'Connection Test', 'skylearn-billing-pro' ); ?></h3>
						<p><?php esc_html_e( 'Test your Lemon Squeezy API connection:', 'skylearn-billing-pro' ); ?></p>
						<button type="button" class="button button-secondary slbp-test-connection" data-gateway="lemon_squeezy">
							<?php esc_html_e( 'Test Connection', 'skylearn-billing-pro' ); ?>
						</button>
						<div class="slbp-test-result"></div>
					</div>

					<div class="slbp-webhook-info">
						<h3><?php esc_html_e( 'Webhook Configuration', 'skylearn-billing-pro' ); ?></h3>
						<p><?php esc_html_e( 'Configure this webhook URL in your Lemon Squeezy dashboard:', 'skylearn-billing-pro' ); ?></p>
						
						<?php if ( class_exists( 'SLBP_Lemon_Squeezy_Webhook' ) ) : ?>
							<?php $webhook_url = SLBP_Lemon_Squeezy_Webhook::get_webhook_url(); ?>
							<table class="form-table">
								<tr>
									<th scope="row"><?php esc_html_e( 'Webhook URL', 'skylearn-billing-pro' ); ?></th>
									<td>
										<input type="text" value="<?php echo esc_attr( $webhook_url ); ?>" class="regular-text" readonly />
										<button type="button" class="button button-secondary slbp-copy-webhook-url" data-url="<?php echo esc_attr( $webhook_url ); ?>">
											<?php esc_html_e( 'Copy URL', 'skylearn-billing-pro' ); ?>
										</button>
										<p class="description">
											<?php esc_html_e( 'Add this URL to your Lemon Squeezy webhook settings.', 'skylearn-billing-pro' ); ?>
										</p>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'Webhook Secret', 'skylearn-billing-pro' ); ?></th>
									<td>
										<?php if ( ! empty( $options['webhook_secret'] ) ) : ?>
											<span class="slbp-status-success">✓ <?php esc_html_e( 'Webhook secret is configured', 'skylearn-billing-pro' ); ?></span>
										<?php else : ?>
											<span class="slbp-status-warning">⚠ <?php esc_html_e( 'Webhook secret not set. Generate a secure random string above.', 'skylearn-billing-pro' ); ?></span>
										<?php endif; ?>
									</td>
								</tr>
							</table>

							<details>
								<summary><?php esc_html_e( 'Webhook Setup Instructions', 'skylearn-billing-pro' ); ?></summary>
								<ol style="margin-left: 20px;">
									<li><?php esc_html_e( 'Log in to your Lemon Squeezy dashboard', 'skylearn-billing-pro' ); ?></li>
									<li><?php esc_html_e( 'Navigate to Settings → Webhooks', 'skylearn-billing-pro' ); ?></li>
									<li><?php esc_html_e( 'Click "Add webhook endpoint"', 'skylearn-billing-pro' ); ?></li>
									<li><?php esc_html_e( 'Enter the webhook URL from above', 'skylearn-billing-pro' ); ?></li>
									<li><?php esc_html_e( 'Select all subscription and order events', 'skylearn-billing-pro' ); ?></li>
									<li><?php esc_html_e( 'Set the webhook secret (same as configured above)', 'skylearn-billing-pro' ); ?></li>
									<li><?php esc_html_e( 'Save the webhook configuration', 'skylearn-billing-pro' ); ?></li>
								</ol>
							</details>
						<?php endif; ?>

				<?php elseif ( $current_tab === 'lms' ) : ?>
					<h2><?php esc_html_e( 'LMS Integration Settings', 'skylearn-billing-pro' ); ?></h2>
					<p class="slbp-tab-description"><?php esc_html_e( 'Configure integration with your Learning Management System.', 'skylearn-billing-pro' ); ?></p>
					
					<?php $options = get_option( 'slbp_lms_settings', array() ); ?>
					
					<table class="form-table slbp-form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'LearnDash Integration', 'skylearn-billing-pro' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="slbp_lms_settings[learndash_enabled]" value="1" <?php checked( isset( $options['learndash_enabled'] ) ? $options['learndash_enabled'] : 0, 1 ); ?> />
									<?php esc_html_e( 'Enable LearnDash integration', 'skylearn-billing-pro' ); ?>
								</label>
								<p class="description">
									<?php if ( class_exists( 'SFWD_LMS' ) ) : ?>
										<span class="slbp-status-success">✓ <?php esc_html_e( 'LearnDash is installed and active', 'skylearn-billing-pro' ); ?></span>
									<?php else : ?>
										<span class="slbp-status-warning">⚠ <?php esc_html_e( 'LearnDash is not detected. Please install and activate LearnDash.', 'skylearn-billing-pro' ); ?></span>
									<?php endif; ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Auto Group Assignment', 'skylearn-billing-pro' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="slbp_lms_settings[auto_group_assignment]" value="1" <?php checked( isset( $options['auto_group_assignment'] ) ? $options['auto_group_assignment'] : 0, 1 ); ?> />
									<?php esc_html_e( 'Automatically assign students to groups', 'skylearn-billing-pro' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'When enabled, students will be automatically added to course groups.', 'skylearn-billing-pro' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Course Access Type', 'skylearn-billing-pro' ); ?></th>
							<td>
								<select name="slbp_lms_settings[course_access_type]">
									<option value="open" <?php selected( isset( $options['course_access_type'] ) ? $options['course_access_type'] : '', 'open' ); ?>><?php esc_html_e( 'Open', 'skylearn-billing-pro' ); ?></option>
									<option value="free" <?php selected( isset( $options['course_access_type'] ) ? $options['course_access_type'] : '', 'free' ); ?>><?php esc_html_e( 'Free', 'skylearn-billing-pro' ); ?></option>
									<option value="paynow" <?php selected( isset( $options['course_access_type'] ) ? $options['course_access_type'] : '', 'paynow' ); ?>><?php esc_html_e( 'Buy Now', 'skylearn-billing-pro' ); ?></option>
									<option value="subscribe" <?php selected( isset( $options['course_access_type'] ) ? $options['course_access_type'] : '', 'subscribe' ); ?>><?php esc_html_e( 'Recurring', 'skylearn-billing-pro' ); ?></option>
									<option value="closed" <?php selected( isset( $options['course_access_type'] ) ? $options['course_access_type'] : '', 'closed' ); ?>><?php esc_html_e( 'Closed', 'skylearn-billing-pro' ); ?></option>
								</select>
								<p class="description"><?php esc_html_e( 'Default access type for new course enrollments.', 'skylearn-billing-pro' ); ?></p>
							</td>
						</tr>
					</table>

				<?php elseif ( $current_tab === 'products' ) : ?>
					<h2><?php esc_html_e( 'Product Mapping Settings', 'skylearn-billing-pro' ); ?></h2>
					<p class="slbp-tab-description"><?php esc_html_e( 'Map your payment gateway products to LearnDash courses.', 'skylearn-billing-pro' ); ?></p>
					
					<?php 
					$options = get_option( 'slbp_product_settings', array() );
					$mappings = isset( $options['product_mappings'] ) ? $options['product_mappings'] : array();
					
					if ( empty( $mappings ) ) {
						$mappings = array( array( 'product_id' => '', 'product_name' => '', 'course_id' => '' ) );
					}
					?>
					
					<div class="slbp-product-mappings-section">
						<h3><?php esc_html_e( 'Product to Course Mappings', 'skylearn-billing-pro' ); ?></h3>
						<p><?php esc_html_e( 'Configure which courses students should be enrolled in when they purchase specific products.', 'skylearn-billing-pro' ); ?></p>
						
						<div id="slbp-product-mappings" class="slbp-mappings-container">
							<?php foreach ( $mappings as $index => $mapping ) : ?>
								<div class="slbp-mapping-row">
									<input type="text" 
										   name="slbp_product_settings[product_mappings][<?php echo esc_attr( $index ); ?>][product_id]" 
										   value="<?php echo esc_attr( $mapping['product_id'] ?? '' ); ?>" 
										   placeholder="<?php esc_attr_e( 'Product ID', 'skylearn-billing-pro' ); ?>" 
										   class="regular-text" />
									
									<input type="text" 
										   name="slbp_product_settings[product_mappings][<?php echo esc_attr( $index ); ?>][product_name]" 
										   value="<?php echo esc_attr( $mapping['product_name'] ?? '' ); ?>" 
										   placeholder="<?php esc_attr_e( 'Product Name', 'skylearn-billing-pro' ); ?>" 
										   class="regular-text" />
									
									<select name="slbp_product_settings[product_mappings][<?php echo esc_attr( $index ); ?>][course_id]">
										<option value=""><?php esc_html_e( 'Select Course', 'skylearn-billing-pro' ); ?></option>
										<?php
										$courses = get_posts( array(
											'post_type'      => 'sfwd-courses',
											'post_status'    => 'publish',
											'posts_per_page' => -1,
											'orderby'        => 'title',
											'order'          => 'ASC',
										) );
										
										foreach ( $courses as $course ) :
											$selected = selected( $mapping['course_id'] ?? '', $course->ID, false );
											?>
											<option value="<?php echo esc_attr( $course->ID ); ?>" <?php echo $selected; ?>>
												<?php echo esc_html( $course->post_title ); ?>
											</option>
										<?php endforeach; ?>
									</select>
									
									<button type="button" class="button slbp-remove-mapping">
										<?php esc_html_e( 'Remove', 'skylearn-billing-pro' ); ?>
									</button>
								</div>
							<?php endforeach; ?>
						</div>
						
						<button type="button" class="button slbp-add-mapping">
							<?php esc_html_e( 'Add Product Mapping', 'skylearn-billing-pro' ); ?>
						</button>
					</div>

				<?php elseif ( $current_tab === 'email' ) : ?>
					<h2><?php esc_html_e( 'Email Settings', 'skylearn-billing-pro' ); ?></h2>
					<p class="slbp-tab-description"><?php esc_html_e( 'Configure email notifications and SMTP settings.', 'skylearn-billing-pro' ); ?></p>
					
					<?php $options = get_option( 'slbp_email_settings', array() ); ?>
					
					<table class="form-table slbp-form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Email Notifications', 'skylearn-billing-pro' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="slbp_email_settings[email_notifications_enabled]" value="1" <?php checked( isset( $options['email_notifications_enabled'] ) ? $options['email_notifications_enabled'] : 0, 1 ); ?> />
									<?php esc_html_e( 'Enable email notifications', 'skylearn-billing-pro' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Send email notifications for payment events.', 'skylearn-billing-pro' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Admin Notifications', 'skylearn-billing-pro' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="slbp_email_settings[admin_email_notifications]" value="1" <?php checked( isset( $options['admin_email_notifications'] ) ? $options['admin_email_notifications'] : 0, 1 ); ?> />
									<?php esc_html_e( 'Send notifications to administrators', 'skylearn-billing-pro' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Notify site administrators of payment events.', 'skylearn-billing-pro' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Customer Notifications', 'skylearn-billing-pro' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="slbp_email_settings[customer_email_notifications]" value="1" <?php checked( isset( $options['customer_email_notifications'] ) ? $options['customer_email_notifications'] : 0, 1 ); ?> />
									<?php esc_html_e( 'Send notifications to customers', 'skylearn-billing-pro' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Send confirmation emails to customers.', 'skylearn-billing-pro' ); ?></p>
							</td>
						</tr>
					</table>

					<h3><?php esc_html_e( 'SMTP Settings', 'skylearn-billing-pro' ); ?></h3>
					<table class="form-table slbp-form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable SMTP', 'skylearn-billing-pro' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="slbp_email_settings[smtp_enabled]" value="1" <?php checked( isset( $options['smtp_enabled'] ) ? $options['smtp_enabled'] : 0, 1 ); ?> />
									<?php esc_html_e( 'Use SMTP for sending emails', 'skylearn-billing-pro' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Use SMTP instead of the default WordPress mail function.', 'skylearn-billing-pro' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'SMTP Host', 'skylearn-billing-pro' ); ?></th>
							<td>
								<input type="text" name="slbp_email_settings[smtp_host]" value="<?php echo esc_attr( isset( $options['smtp_host'] ) ? $options['smtp_host'] : '' ); ?>" class="regular-text" />
								<p class="description"><?php esc_html_e( 'Your SMTP server hostname.', 'skylearn-billing-pro' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'SMTP Port', 'skylearn-billing-pro' ); ?></th>
							<td>
								<input type="number" name="slbp_email_settings[smtp_port]" value="<?php echo esc_attr( isset( $options['smtp_port'] ) ? $options['smtp_port'] : '587' ); ?>" class="small-text" />
								<p class="description"><?php esc_html_e( 'Usually 587 for TLS or 465 for SSL.', 'skylearn-billing-pro' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'SMTP Username', 'skylearn-billing-pro' ); ?></th>
							<td>
								<input type="text" name="slbp_email_settings[smtp_username]" value="<?php echo esc_attr( isset( $options['smtp_username'] ) ? $options['smtp_username'] : '' ); ?>" class="regular-text" />
								<p class="description"><?php esc_html_e( 'Your SMTP username (usually your email address).', 'skylearn-billing-pro' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'SMTP Password', 'skylearn-billing-pro' ); ?></th>
							<td>
								<input type="password" name="slbp_email_settings[smtp_password]" value="<?php echo esc_attr( isset( $options['smtp_password'] ) ? $options['smtp_password'] : '' ); ?>" class="regular-text" />
								<p class="description"><?php esc_html_e( 'Your SMTP password.', 'skylearn-billing-pro' ); ?></p>
							</td>
						</tr>
					</table>

				<?php elseif ( $current_tab === 'advanced' ) : ?>
					<h2><?php esc_html_e( 'Advanced Settings', 'skylearn-billing-pro' ); ?></h2>
					<p class="slbp-tab-description"><?php esc_html_e( 'Advanced configuration options for developers and system administrators.', 'skylearn-billing-pro' ); ?></p>
					
					<?php $options = get_option( 'slbp_advanced_settings', array() ); ?>
					
					<table class="form-table slbp-form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Webhook URL', 'skylearn-billing-pro' ); ?></th>
							<td>
								<?php $webhook_url = site_url( '/wp-json/slbp/v1/webhook' ); ?>
								<input type="text" value="<?php echo esc_attr( $webhook_url ); ?>" class="regular-text" readonly />
								<p class="description"><?php esc_html_e( 'This is your webhook URL for payment gateway notifications. Copy this to your payment gateway settings.', 'skylearn-billing-pro' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Webhook Timeout', 'skylearn-billing-pro' ); ?></th>
							<td>
								<input type="number" name="slbp_advanced_settings[webhook_timeout]" value="<?php echo esc_attr( isset( $options['webhook_timeout'] ) ? $options['webhook_timeout'] : '30' ); ?>" class="small-text" />
								<?php esc_html_e( 'seconds', 'skylearn-billing-pro' ); ?>
								<p class="description"><?php esc_html_e( 'Maximum time to wait for webhook processing.', 'skylearn-billing-pro' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable Caching', 'skylearn-billing-pro' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="slbp_advanced_settings[cache_enabled]" value="1" <?php checked( isset( $options['cache_enabled'] ) ? $options['cache_enabled'] : 0, 1 ); ?> />
									<?php esc_html_e( 'Enable caching for improved performance', 'skylearn-billing-pro' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Cache API responses and database queries.', 'skylearn-billing-pro' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Cache Duration', 'skylearn-billing-pro' ); ?></th>
							<td>
								<input type="number" name="slbp_advanced_settings[cache_duration]" value="<?php echo esc_attr( isset( $options['cache_duration'] ) ? $options['cache_duration'] : '60' ); ?>" class="small-text" />
								<?php esc_html_e( 'minutes', 'skylearn-billing-pro' ); ?>
								<p class="description"><?php esc_html_e( 'How long to cache data before refreshing.', 'skylearn-billing-pro' ); ?></p>
							</td>
						</tr>
					</table>

				<?php endif; ?>
			</div>

			<?php submit_button( esc_html__( 'Save Settings', 'skylearn-billing-pro' ), 'primary', 'submit', true, array( 'class' => 'slbp-save-button' ) ); ?>
		</form>
	</div>
</div>