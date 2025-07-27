<?php
/**
 * Developer API admin page template
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

// Get plugin instance
$plugin = SLBP_Plugin::get_instance();
$api_key_manager = $plugin->get_api_key_manager();

// Handle form submissions
if ( isset( $_POST['slbp_create_api_key'] ) && wp_verify_nonce( $_POST['slbp_api_nonce'], 'slbp_api_action' ) ) {
	$name = sanitize_text_field( $_POST['api_key_name'] );
	$permissions = isset( $_POST['api_permissions'] ) ? array_map( 'sanitize_text_field', $_POST['api_permissions'] ) : array();
	$rate_limit = (int) $_POST['rate_limit'];
	$expires_at = ! empty( $_POST['expires_at'] ) ? sanitize_text_field( $_POST['expires_at'] ) : null;

	$result = $api_key_manager->create_api_key( get_current_user_id(), $name, $permissions, array(
		'rate_limit' => $rate_limit,
		'expires_at' => $expires_at,
	) );

	if ( is_wp_error( $result ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
	} else {
		echo '<div class="notice notice-success"><p>' . sprintf(
			esc_html__( 'API key created successfully: %s', 'skylearn-billing-pro' ),
			'<code>' . esc_html( $result ) . '</code>'
		) . '</p></div>';
	}
}

if ( isset( $_POST['slbp_delete_api_key'] ) && wp_verify_nonce( $_POST['slbp_api_nonce'], 'slbp_api_action' ) ) {
	$key_id = (int) $_POST['key_id'];
	$result = $api_key_manager->delete_api_key( $key_id );

	if ( is_wp_error( $result ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
	} else {
		echo '<div class="notice notice-success"><p>' . esc_html__( 'API key deleted successfully.', 'skylearn-billing-pro' ) . '</p></div>';
	}
}

// Get existing API keys
$api_keys = $api_key_manager->get_user_api_keys( get_current_user_id() );
$permissions_desc = $api_key_manager->get_permission_descriptions();
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Developer API', 'skylearn-billing-pro' ); ?></h1>
	
	<div class="slbp-api-tabs">
		<nav class="nav-tab-wrapper">
			<a href="#api-keys" class="nav-tab nav-tab-active"><?php esc_html_e( 'API Keys', 'skylearn-billing-pro' ); ?></a>
			<a href="#documentation" class="nav-tab"><?php esc_html_e( 'Documentation', 'skylearn-billing-pro' ); ?></a>
			<a href="#webhooks" class="nav-tab"><?php esc_html_e( 'Webhooks', 'skylearn-billing-pro' ); ?></a>
			<a href="#usage" class="nav-tab"><?php esc_html_e( 'Usage Stats', 'skylearn-billing-pro' ); ?></a>
		</nav>

		<div id="api-keys" class="tab-content">
			<div class="slbp-api-grid">
				<!-- Create API Key Form -->
				<div class="slbp-card">
					<h2><?php esc_html_e( 'Create New API Key', 'skylearn-billing-pro' ); ?></h2>
					<form method="post" action="">
						<?php wp_nonce_field( 'slbp_api_action', 'slbp_api_nonce' ); ?>
						
						<table class="form-table">
							<tr>
								<th scope="row">
									<label for="api_key_name"><?php esc_html_e( 'Key Name', 'skylearn-billing-pro' ); ?></label>
								</th>
								<td>
									<input type="text" id="api_key_name" name="api_key_name" class="regular-text" required />
									<p class="description"><?php esc_html_e( 'A descriptive name for this API key', 'skylearn-billing-pro' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="api_permissions"><?php esc_html_e( 'Permissions', 'skylearn-billing-pro' ); ?></label>
								</th>
								<td>
									<?php foreach ( $permissions_desc as $permission => $description ) : ?>
										<label>
											<input type="checkbox" name="api_permissions[]" value="<?php echo esc_attr( $permission ); ?>" />
											<strong><?php echo esc_html( $permission ); ?></strong> - <?php echo esc_html( $description ); ?>
										</label><br />
									<?php endforeach; ?>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="rate_limit"><?php esc_html_e( 'Rate Limit', 'skylearn-billing-pro' ); ?></label>
								</th>
								<td>
									<input type="number" id="rate_limit" name="rate_limit" value="1000" min="1" max="10000" />
									<p class="description"><?php esc_html_e( 'Maximum requests per hour', 'skylearn-billing-pro' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="expires_at"><?php esc_html_e( 'Expires At', 'skylearn-billing-pro' ); ?></label>
								</th>
								<td>
									<input type="datetime-local" id="expires_at" name="expires_at" />
									<p class="description"><?php esc_html_e( 'Leave empty for no expiration', 'skylearn-billing-pro' ); ?></p>
								</td>
							</tr>
						</table>
						
						<p class="submit">
							<input type="submit" name="slbp_create_api_key" class="button-primary" value="<?php esc_attr_e( 'Create API Key', 'skylearn-billing-pro' ); ?>" />
						</p>
					</form>
				</div>

				<!-- Existing API Keys -->
				<div class="slbp-card">
					<h2><?php esc_html_e( 'Your API Keys', 'skylearn-billing-pro' ); ?></h2>
					
					<?php if ( empty( $api_keys ) ) : ?>
						<p><?php esc_html_e( 'No API keys found. Create your first API key to get started.', 'skylearn-billing-pro' ); ?></p>
					<?php else : ?>
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Name', 'skylearn-billing-pro' ); ?></th>
									<th><?php esc_html_e( 'Key', 'skylearn-billing-pro' ); ?></th>
									<th><?php esc_html_e( 'Permissions', 'skylearn-billing-pro' ); ?></th>
									<th><?php esc_html_e( 'Rate Limit', 'skylearn-billing-pro' ); ?></th>
									<th><?php esc_html_e( 'Status', 'skylearn-billing-pro' ); ?></th>
									<th><?php esc_html_e( 'Last Used', 'skylearn-billing-pro' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'skylearn-billing-pro' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $api_keys as $key ) : ?>
									<tr>
										<td><strong><?php echo esc_html( $key->name ); ?></strong></td>
										<td><code><?php echo esc_html( $key->masked_key ); ?></code></td>
										<td><?php echo esc_html( implode( ', ', $key->permissions ) ); ?></td>
										<td><?php echo esc_html( number_format( $key->rate_limit ) ); ?>/hour</td>
										<td>
											<?php if ( $key->is_active ) : ?>
												<span class="slbp-status-active"><?php esc_html_e( 'Active', 'skylearn-billing-pro' ); ?></span>
											<?php else : ?>
												<span class="slbp-status-inactive"><?php esc_html_e( 'Inactive', 'skylearn-billing-pro' ); ?></span>
											<?php endif; ?>
										</td>
										<td>
											<?php if ( $key->last_used_at ) : ?>
												<?php echo esc_html( human_time_diff( strtotime( $key->last_used_at ) ) ); ?> ago
											<?php else : ?>
												<?php esc_html_e( 'Never', 'skylearn-billing-pro' ); ?>
											<?php endif; ?>
										</td>
										<td>
											<form method="post" style="display: inline;">
												<?php wp_nonce_field( 'slbp_api_action', 'slbp_api_nonce' ); ?>
												<input type="hidden" name="key_id" value="<?php echo esc_attr( $key->id ); ?>" />
												<input type="submit" name="slbp_delete_api_key" class="button-link-delete" value="<?php esc_attr_e( 'Delete', 'skylearn-billing-pro' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this API key?', 'skylearn-billing-pro' ); ?>')" />
											</form>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<div id="documentation" class="tab-content" style="display: none;">
			<div class="slbp-card">
				<h2><?php esc_html_e( 'API Documentation', 'skylearn-billing-pro' ); ?></h2>
				
				<h3><?php esc_html_e( 'Authentication', 'skylearn-billing-pro' ); ?></h3>
				<p><?php esc_html_e( 'Include your API key in requests using one of these methods:', 'skylearn-billing-pro' ); ?></p>
				
				<h4><?php esc_html_e( 'Authorization Header', 'skylearn-billing-pro' ); ?></h4>
				<pre><code>Authorization: Bearer YOUR_API_KEY</code></pre>
				
				<h4><?php esc_html_e( 'X-API-Key Header', 'skylearn-billing-pro' ); ?></h4>
				<pre><code>X-API-Key: YOUR_API_KEY</code></pre>
				
				<h4><?php esc_html_e( 'Query Parameter', 'skylearn-billing-pro' ); ?></h4>
				<pre><code>?api_key=YOUR_API_KEY</code></pre>

				<h3><?php esc_html_e( 'Available Endpoints', 'skylearn-billing-pro' ); ?></h3>
				
				<div class="slbp-endpoint-list">
					<h4><?php esc_html_e( 'Billing', 'skylearn-billing-pro' ); ?></h4>
					<ul>
						<li><code>GET /wp-json/slbp/v1/billing/invoices</code> - <?php esc_html_e( 'Get invoices', 'skylearn-billing-pro' ); ?></li>
						<li><code>GET /wp-json/slbp/v1/billing/transactions</code> - <?php esc_html_e( 'Get transactions', 'skylearn-billing-pro' ); ?></li>
						<li><code>POST /wp-json/slbp/v1/billing/refunds</code> - <?php esc_html_e( 'Process refunds', 'skylearn-billing-pro' ); ?></li>
					</ul>

					<h4><?php esc_html_e( 'Subscriptions', 'skylearn-billing-pro' ); ?></h4>
					<ul>
						<li><code>GET /wp-json/slbp/v1/subscriptions</code> - <?php esc_html_e( 'Get subscriptions', 'skylearn-billing-pro' ); ?></li>
						<li><code>POST /wp-json/slbp/v1/subscriptions/{id}/cancel</code> - <?php esc_html_e( 'Cancel subscription', 'skylearn-billing-pro' ); ?></li>
					</ul>
				</div>

				<h3><?php esc_html_e( 'Example Requests', 'skylearn-billing-pro' ); ?></h3>
				
				<h4><?php esc_html_e( 'PHP (cURL)', 'skylearn-billing-pro' ); ?></h4>
				<pre><code>$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, '<?php echo esc_html( home_url( '/wp-json/slbp/v1/billing/invoices' ) ); ?>');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer YOUR_API_KEY',
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);</code></pre>

				<h4><?php esc_html_e( 'JavaScript (Fetch)', 'skylearn-billing-pro' ); ?></h4>
				<pre><code>fetch('<?php echo esc_html( home_url( '/wp-json/slbp/v1/billing/invoices' ) ); ?>', {
    headers: {
        'Authorization': 'Bearer YOUR_API_KEY',
        'Content-Type': 'application/json'
    }
})
.then(response => response.json())
.then(data => console.log(data));</code></pre>

				<h4><?php esc_html_e( 'Python (requests)', 'skylearn-billing-pro' ); ?></h4>
				<pre><code>import requests

headers = {
    'Authorization': 'Bearer YOUR_API_KEY',
    'Content-Type': 'application/json'
}

response = requests.get('<?php echo esc_html( home_url( '/wp-json/slbp/v1/billing/invoices' ) ); ?>', headers=headers)
data = response.json()</code></pre>
			</div>
		</div>

		<div id="webhooks" class="tab-content" style="display: none;">
			<div class="slbp-card">
				<h2><?php esc_html_e( 'Webhooks', 'skylearn-billing-pro' ); ?></h2>
				<p><?php esc_html_e( 'Webhooks functionality will be available in the full implementation.', 'skylearn-billing-pro' ); ?></p>
			</div>
		</div>

		<div id="usage" class="tab-content" style="display: none;">
			<div class="slbp-card">
				<h2><?php esc_html_e( 'API Usage Statistics', 'skylearn-billing-pro' ); ?></h2>
				<p><?php esc_html_e( 'Usage statistics will be available in the full implementation.', 'skylearn-billing-pro' ); ?></p>
			</div>
		</div>
	</div>
</div>

<style>
.slbp-api-tabs .nav-tab-wrapper {
	margin-bottom: 20px;
}

.slbp-api-tabs .tab-content {
	background: #fff;
	padding: 20px;
	border: 1px solid #ccd0d4;
	border-top: none;
}

.slbp-api-grid {
	display: grid;
	grid-template-columns: 1fr 2fr;
	gap: 20px;
}

.slbp-card {
	background: #fff;
	border: 1px solid #ccd0d4;
	padding: 20px;
	border-radius: 4px;
}

.slbp-card h2 {
	margin-top: 0;
}

.slbp-status-active {
	color: #00a32a;
	font-weight: bold;
}

.slbp-status-inactive {
	color: #d63638;
	font-weight: bold;
}

.slbp-endpoint-list ul {
	margin-left: 20px;
}

.slbp-endpoint-list code {
	background: #f0f0f1;
	padding: 2px 6px;
	border-radius: 3px;
}

pre {
	background: #f0f0f1;
	padding: 15px;
	border-radius: 4px;
	overflow-x: auto;
}

pre code {
	background: none;
	padding: 0;
}

@media (max-width: 768px) {
	.slbp-api-grid {
		grid-template-columns: 1fr;
	}
}
</style>

<script>
jQuery(document).ready(function($) {
	// Tab functionality
	$('.nav-tab').on('click', function(e) {
		e.preventDefault();
		
		// Remove active class from all tabs
		$('.nav-tab').removeClass('nav-tab-active');
		$('.tab-content').hide();
		
		// Add active class to clicked tab
		$(this).addClass('nav-tab-active');
		
		// Show corresponding content
		var target = $(this).attr('href');
		$(target).show();
	});
});
</script>