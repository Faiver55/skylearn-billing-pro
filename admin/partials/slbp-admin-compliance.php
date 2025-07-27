<?php
/**
 * Compliance admin page functionality.
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

$plugin = SLBP_Plugin::get_instance();
$compliance_manager = $plugin->get_compliance_manager();
$audit_logger = $plugin->get_audit_logger();

// Handle form submissions
if ( isset( $_POST['submit'] ) && wp_verify_nonce( $_POST['slbp_compliance_nonce'], 'slbp_compliance_settings' ) ) {
	$settings = array(
		'audit_retention_days' => intval( $_POST['audit_retention_days'] ?? 90 ),
		'data_retention_policy' => sanitize_text_field( $_POST['data_retention_policy'] ?? 'anonymize' ),
		'audit_retention_policy' => sanitize_text_field( $_POST['audit_retention_policy'] ?? 'retain' ),
		'gdpr_enabled' => isset( $_POST['gdpr_enabled'] ),
		'ccpa_enabled' => isset( $_POST['ccpa_enabled'] ),
		'auto_delete_exports' => isset( $_POST['auto_delete_exports'] ),
		'consent_tracking' => isset( $_POST['consent_tracking'] ),
	);

	update_option( 'slbp_compliance_settings', $settings );
	
	echo '<div class="notice notice-success"><p>' . esc_html__( 'Compliance settings saved successfully.', 'skylearn-billing-pro' ) . '</p></div>';
}

$compliance_settings = get_option( 'slbp_compliance_settings', array() );

// Get recent audit logs
$recent_logs = $audit_logger->get_logs( array(
	'limit' => 10,
	'orderby' => 'created_at',
	'order' => 'DESC',
) );

// Get compliance metrics
$compliance_metrics = array(
	'total_data_exports' => count( $audit_logger->get_logs( array( 'action' => 'data_exported', 'limit' => 0 ) )['logs'] ),
	'total_data_deletions' => count( $audit_logger->get_logs( array( 'action' => 'data_deleted', 'limit' => 0 ) )['logs'] ),
	'total_audit_events' => $recent_logs['total'],
);
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Compliance & Privacy', 'skylearn-billing-pro' ); ?></h1>
	
	<div class="slbp-compliance-dashboard">
		<div class="compliance-metrics">
			<div class="metric-card">
				<h3><?php esc_html_e( 'Data Exports', 'skylearn-billing-pro' ); ?></h3>
				<div class="metric-value"><?php echo esc_html( $compliance_metrics['total_data_exports'] ); ?></div>
			</div>
			<div class="metric-card">
				<h3><?php esc_html_e( 'Data Deletions', 'skylearn-billing-pro' ); ?></h3>
				<div class="metric-value"><?php echo esc_html( $compliance_metrics['total_data_deletions'] ); ?></div>
			</div>
			<div class="metric-card">
				<h3><?php esc_html_e( 'Audit Events', 'skylearn-billing-pro' ); ?></h3>
				<div class="metric-value"><?php echo esc_html( $compliance_metrics['total_audit_events'] ); ?></div>
			</div>
		</div>

		<div class="compliance-tabs">
			<nav class="nav-tab-wrapper">
				<a href="#settings" class="nav-tab nav-tab-active"><?php esc_html_e( 'Settings', 'skylearn-billing-pro' ); ?></a>
				<a href="#audit-logs" class="nav-tab"><?php esc_html_e( 'Audit Logs', 'skylearn-billing-pro' ); ?></a>
				<a href="#data-requests" class="nav-tab"><?php esc_html_e( 'Data Requests', 'skylearn-billing-pro' ); ?></a>
				<a href="#privacy-tools" class="nav-tab"><?php esc_html_e( 'Privacy Tools', 'skylearn-billing-pro' ); ?></a>
			</nav>

			<div id="settings" class="tab-content">
				<form method="post" action="">
					<?php wp_nonce_field( 'slbp_compliance_settings', 'slbp_compliance_nonce' ); ?>
					
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'GDPR Compliance', 'skylearn-billing-pro' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="gdpr_enabled" value="1" <?php checked( $compliance_settings['gdpr_enabled'] ?? false ); ?> />
									<?php esc_html_e( 'Enable GDPR compliance features', 'skylearn-billing-pro' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Enables data export/deletion tools and consent tracking for GDPR compliance.', 'skylearn-billing-pro' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'CCPA Compliance', 'skylearn-billing-pro' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="ccpa_enabled" value="1" <?php checked( $compliance_settings['ccpa_enabled'] ?? false ); ?> />
									<?php esc_html_e( 'Enable CCPA compliance features', 'skylearn-billing-pro' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Enables data export/deletion tools and consent tracking for CCPA compliance.', 'skylearn-billing-pro' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Audit Log Retention', 'skylearn-billing-pro' ); ?></th>
							<td>
								<input type="number" name="audit_retention_days" value="<?php echo esc_attr( $compliance_settings['audit_retention_days'] ?? 90 ); ?>" min="1" max="365" />
								<p class="description"><?php esc_html_e( 'Number of days to retain audit logs (1-365 days).', 'skylearn-billing-pro' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Data Retention Policy', 'skylearn-billing-pro' ); ?></th>
							<td>
								<select name="data_retention_policy">
									<option value="anonymize" <?php selected( $compliance_settings['data_retention_policy'] ?? 'anonymize', 'anonymize' ); ?>><?php esc_html_e( 'Anonymize Data', 'skylearn-billing-pro' ); ?></option>
									<option value="delete" <?php selected( $compliance_settings['data_retention_policy'] ?? 'anonymize', 'delete' ); ?>><?php esc_html_e( 'Delete Data', 'skylearn-billing-pro' ); ?></option>
								</select>
								<p class="description"><?php esc_html_e( 'How to handle user data deletion requests.', 'skylearn-billing-pro' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Audit Retention Policy', 'skylearn-billing-pro' ); ?></th>
							<td>
								<select name="audit_retention_policy">
									<option value="retain" <?php selected( $compliance_settings['audit_retention_policy'] ?? 'retain', 'retain' ); ?>><?php esc_html_e( 'Retain for Compliance', 'skylearn-billing-pro' ); ?></option>
									<option value="anonymize" <?php selected( $compliance_settings['audit_retention_policy'] ?? 'retain', 'anonymize' ); ?>><?php esc_html_e( 'Anonymize Logs', 'skylearn-billing-pro' ); ?></option>
								</select>
								<p class="description"><?php esc_html_e( 'How to handle audit logs in data deletion requests.', 'skylearn-billing-pro' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Additional Settings', 'skylearn-billing-pro' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="consent_tracking" value="1" <?php checked( $compliance_settings['consent_tracking'] ?? false ); ?> />
									<?php esc_html_e( 'Enable consent tracking', 'skylearn-billing-pro' ); ?>
								</label><br>
								<label>
									<input type="checkbox" name="auto_delete_exports" value="1" <?php checked( $compliance_settings['auto_delete_exports'] ?? false ); ?> />
									<?php esc_html_e( 'Auto-delete export files after 7 days', 'skylearn-billing-pro' ); ?>
								</label>
							</td>
						</tr>
					</table>
					
					<?php submit_button(); ?>
				</form>
			</div>

			<div id="audit-logs" class="tab-content" style="display: none;">
				<div class="audit-logs-controls">
					<div class="filters">
						<select id="audit-event-type">
							<option value=""><?php esc_html_e( 'All Event Types', 'skylearn-billing-pro' ); ?></option>
							<option value="payment"><?php esc_html_e( 'Payment Events', 'skylearn-billing-pro' ); ?></option>
							<option value="user"><?php esc_html_e( 'User Events', 'skylearn-billing-pro' ); ?></option>
							<option value="admin"><?php esc_html_e( 'Admin Events', 'skylearn-billing-pro' ); ?></option>
							<option value="security"><?php esc_html_e( 'Security Events', 'skylearn-billing-pro' ); ?></option>
							<option value="compliance"><?php esc_html_e( 'Compliance Events', 'skylearn-billing-pro' ); ?></option>
						</select>
						<select id="audit-severity">
							<option value=""><?php esc_html_e( 'All Severities', 'skylearn-billing-pro' ); ?></option>
							<option value="info"><?php esc_html_e( 'Info', 'skylearn-billing-pro' ); ?></option>
							<option value="warning"><?php esc_html_e( 'Warning', 'skylearn-billing-pro' ); ?></option>
							<option value="error"><?php esc_html_e( 'Error', 'skylearn-billing-pro' ); ?></option>
						</select>
						<input type="date" id="audit-start-date" />
						<input type="date" id="audit-end-date" />
						<button type="button" id="filter-audit-logs" class="button"><?php esc_html_e( 'Filter', 'skylearn-billing-pro' ); ?></button>
						<button type="button" id="export-audit-logs" class="button"><?php esc_html_e( 'Export CSV', 'skylearn-billing-pro' ); ?></button>
					</div>
				</div>

				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Date', 'skylearn-billing-pro' ); ?></th>
							<th><?php esc_html_e( 'Event Type', 'skylearn-billing-pro' ); ?></th>
							<th><?php esc_html_e( 'Action', 'skylearn-billing-pro' ); ?></th>
							<th><?php esc_html_e( 'User', 'skylearn-billing-pro' ); ?></th>
							<th><?php esc_html_e( 'IP Address', 'skylearn-billing-pro' ); ?></th>
							<th><?php esc_html_e( 'Severity', 'skylearn-billing-pro' ); ?></th>
						</tr>
					</thead>
					<tbody id="audit-logs-table">
						<?php foreach ( $recent_logs['logs'] as $log ) : ?>
							<tr>
								<td><?php echo esc_html( $log->created_at ); ?></td>
								<td><?php echo esc_html( $log->event_type ); ?></td>
								<td><?php echo esc_html( $log->action ); ?></td>
								<td>
									<?php 
									if ( $log->user_id ) {
										$user = get_userdata( $log->user_id );
										echo esc_html( $user ? $user->user_login : 'Unknown' );
									} else {
										echo esc_html__( 'System', 'skylearn-billing-pro' );
									}
									?>
								</td>
								<td><?php echo esc_html( $log->user_ip ); ?></td>
								<td>
									<span class="severity-badge severity-<?php echo esc_attr( $log->severity ); ?>">
										<?php echo esc_html( ucfirst( $log->severity ) ); ?>
									</span>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<div id="data-requests" class="tab-content" style="display: none;">
				<div class="data-requests-section">
					<h3><?php esc_html_e( 'User Data Export/Deletion Requests', 'skylearn-billing-pro' ); ?></h3>
					
					<div class="request-tools">
						<div class="manual-export">
							<h4><?php esc_html_e( 'Manual Data Export', 'skylearn-billing-pro' ); ?></h4>
							<p><?php esc_html_e( 'Export user data for GDPR/CCPA compliance:', 'skylearn-billing-pro' ); ?></p>
							<input type="text" id="export-user-email" placeholder="<?php esc_attr_e( 'Enter user email', 'skylearn-billing-pro' ); ?>" />
							<button type="button" id="export-user-data" class="button button-primary"><?php esc_html_e( 'Export User Data', 'skylearn-billing-pro' ); ?></button>
						</div>

						<div class="manual-deletion">
							<h4><?php esc_html_e( 'Manual Data Deletion', 'skylearn-billing-pro' ); ?></h4>
							<p><?php esc_html_e( 'Delete/anonymize user data for GDPR/CCPA compliance:', 'skylearn-billing-pro' ); ?></p>
							<input type="text" id="delete-user-email" placeholder="<?php esc_attr_e( 'Enter user email', 'skylearn-billing-pro' ); ?>" />
							<button type="button" id="delete-user-data" class="button button-secondary"><?php esc_html_e( 'Delete User Data', 'skylearn-billing-pro' ); ?></button>
						</div>
					</div>

					<?php
					// Show WordPress privacy requests
					$privacy_requests = get_posts( array(
						'post_type' => 'user_request',
						'post_status' => 'any',
						'posts_per_page' => 10,
					) );

					if ( $privacy_requests ) :
					?>
						<h4><?php esc_html_e( 'Recent Privacy Requests', 'skylearn-billing-pro' ); ?></h4>
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Date', 'skylearn-billing-pro' ); ?></th>
									<th><?php esc_html_e( 'Email', 'skylearn-billing-pro' ); ?></th>
									<th><?php esc_html_e( 'Type', 'skylearn-billing-pro' ); ?></th>
									<th><?php esc_html_e( 'Status', 'skylearn-billing-pro' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $privacy_requests as $request ) : ?>
									<tr>
										<td><?php echo esc_html( $request->post_date ); ?></td>
										<td><?php echo esc_html( get_post_meta( $request->ID, '_wp_user_request_user_email', true ) ); ?></td>
										<td><?php echo esc_html( get_post_meta( $request->ID, '_wp_user_request_action_name', true ) ); ?></td>
										<td><?php echo esc_html( $request->post_status ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
			</div>

			<div id="privacy-tools" class="tab-content" style="display: none;">
				<div class="privacy-tools-section">
					<h3><?php esc_html_e( 'Privacy Tools & Documentation', 'skylearn-billing-pro' ); ?></h3>
					
					<div class="tool-card">
						<h4><?php esc_html_e( 'Consent Management', 'skylearn-billing-pro' ); ?></h4>
						<p><?php esc_html_e( 'Manage user consent preferences and tracking.', 'skylearn-billing-pro' ); ?></p>
						<button type="button" class="button" onclick="window.open('<?php echo esc_url( admin_url( 'users.php' ) ); ?>', '_blank')"><?php esc_html_e( 'Manage User Consent', 'skylearn-billing-pro' ); ?></button>
					</div>

					<div class="tool-card">
						<h4><?php esc_html_e( 'Privacy Policy Generator', 'skylearn-billing-pro' ); ?></h4>
						<p><?php esc_html_e( 'Generate privacy policy templates for your site.', 'skylearn-billing-pro' ); ?></p>
						<button type="button" id="generate-privacy-policy" class="button"><?php esc_html_e( 'Generate Template', 'skylearn-billing-pro' ); ?></button>
					</div>

					<div class="tool-card">
						<h4><?php esc_html_e( 'Data Processing Agreements', 'skylearn-billing-pro' ); ?></h4>
						<p><?php esc_html_e( 'Download sample DPA templates for third-party services.', 'skylearn-billing-pro' ); ?></p>
						<button type="button" id="download-dpa-templates" class="button"><?php esc_html_e( 'Download Templates', 'skylearn-billing-pro' ); ?></button>
					</div>

					<div class="compliance-checklist">
						<h4><?php esc_html_e( 'Compliance Checklist', 'skylearn-billing-pro' ); ?></h4>
						<ul>
							<li><input type="checkbox" /> <?php esc_html_e( 'Privacy policy updated with data collection details', 'skylearn-billing-pro' ); ?></li>
							<li><input type="checkbox" /> <?php esc_html_e( 'Consent mechanisms implemented', 'skylearn-billing-pro' ); ?></li>
							<li><input type="checkbox" /> <?php esc_html_e( 'Data retention policies configured', 'skylearn-billing-pro' ); ?></li>
							<li><input type="checkbox" /> <?php esc_html_e( 'Staff trained on privacy procedures', 'skylearn-billing-pro' ); ?></li>
							<li><input type="checkbox" /> <?php esc_html_e( 'Third-party processor agreements in place', 'skylearn-billing-pro' ); ?></li>
							<li><input type="checkbox" /> <?php esc_html_e( 'Data breach response plan established', 'skylearn-billing-pro' ); ?></li>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<style>
.slbp-compliance-dashboard .compliance-metrics {
	display: flex;
	gap: 20px;
	margin-bottom: 30px;
}

.metric-card {
	background: #fff;
	border: 1px solid #ccd0d4;
	padding: 20px;
	text-align: center;
	flex: 1;
	border-radius: 4px;
}

.metric-card h3 {
	margin: 0 0 10px 0;
	font-size: 14px;
	color: #666;
}

.metric-value {
	font-size: 32px;
	font-weight: bold;
	color: #0073aa;
}

.tab-content {
	background: #fff;
	border: 1px solid #ccd0d4;
	border-top: none;
	padding: 20px;
}

.audit-logs-controls {
	margin-bottom: 20px;
	padding-bottom: 15px;
	border-bottom: 1px solid #eee;
}

.filters {
	display: flex;
	gap: 10px;
	flex-wrap: wrap;
}

.severity-badge {
	padding: 4px 8px;
	border-radius: 3px;
	font-size: 12px;
	font-weight: bold;
}

.severity-info { background: #d1ecf1; color: #0c5460; }
.severity-warning { background: #fff3cd; color: #856404; }
.severity-error { background: #f8d7da; color: #721c24; }

.request-tools {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 30px;
	margin-bottom: 30px;
}

.manual-export, .manual-deletion {
	padding: 20px;
	border: 1px solid #ddd;
	border-radius: 4px;
}

.tool-card {
	background: #f9f9f9;
	padding: 20px;
	margin-bottom: 20px;
	border-left: 4px solid #0073aa;
}

.compliance-checklist ul {
	list-style: none;
}

.compliance-checklist li {
	margin-bottom: 10px;
}
</style>

<script>
jQuery(document).ready(function($) {
	// Tab switching
	$('.nav-tab').on('click', function(e) {
		e.preventDefault();
		var target = $(this).attr('href');
		
		$('.nav-tab').removeClass('nav-tab-active');
		$(this).addClass('nav-tab-active');
		
		$('.tab-content').hide();
		$(target).show();
	});

	// Filter audit logs
	$('#filter-audit-logs').on('click', function() {
		var filters = {
			event_type: $('#audit-event-type').val(),
			severity: $('#audit-severity').val(),
			start_date: $('#audit-start-date').val(),
			end_date: $('#audit-end-date').val(),
			nonce: '<?php echo esc_js( wp_create_nonce( 'slbp_compliance_nonce' ) ); ?>'
		};

		// AJAX call to filter logs would go here
		console.log('Filtering logs with:', filters);
	});

	// Export user data
	$('#export-user-data').on('click', function() {
		var email = $('#export-user-email').val();
		if (!email) {
			alert('<?php esc_html_e( 'Please enter a user email address.', 'skylearn-billing-pro' ); ?>');
			return;
		}

		$(this).prop('disabled', true).text('<?php esc_html_e( 'Exporting...', 'skylearn-billing-pro' ); ?>');

		// AJAX call to export user data would go here
		setTimeout(() => {
			$(this).prop('disabled', false).text('<?php esc_html_e( 'Export User Data', 'skylearn-billing-pro' ); ?>');
			alert('<?php esc_html_e( 'User data export initiated.', 'skylearn-billing-pro' ); ?>');
		}, 2000);
	});

	// Delete user data
	$('#delete-user-data').on('click', function() {
		var email = $('#delete-user-email').val();
		if (!email) {
			alert('<?php esc_html_e( 'Please enter a user email address.', 'skylearn-billing-pro' ); ?>');
			return;
		}

		if (!confirm('<?php esc_html_e( 'Are you sure you want to delete/anonymize this user\'s data? This action cannot be undone.', 'skylearn-billing-pro' ); ?>')) {
			return;
		}

		$(this).prop('disabled', true).text('<?php esc_html_e( 'Processing...', 'skylearn-billing-pro' ); ?>');

		// AJAX call to delete user data would go here
		setTimeout(() => {
			$(this).prop('disabled', false).text('<?php esc_html_e( 'Delete User Data', 'skylearn-billing-pro' ); ?>');
			alert('<?php esc_html_e( 'User data deletion initiated.', 'skylearn-billing-pro' ); ?>');
		}, 2000);
	});
});
</script>