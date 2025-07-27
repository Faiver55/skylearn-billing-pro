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
$webhook_manager = $plugin->get_webhook_manager();

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

// Handle webhook form submissions
if ( isset( $_POST['slbp_create_webhook'] ) && wp_verify_nonce( $_POST['slbp_webhook_nonce'], 'slbp_webhook_action' ) ) {
	$name = sanitize_text_field( $_POST['webhook_name'] );
	$url = esc_url_raw( $_POST['webhook_url'] );
	$events = isset( $_POST['webhook_events'] ) ? array_map( 'sanitize_text_field', $_POST['webhook_events'] ) : array();

	$result = $webhook_manager->create_webhook( get_current_user_id(), $name, $url, $events );

	if ( is_wp_error( $result ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
	} else {
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Webhook created successfully.', 'skylearn-billing-pro' ) . '</p></div>';
	}
}

if ( isset( $_POST['slbp_delete_webhook'] ) && wp_verify_nonce( $_POST['slbp_webhook_nonce'], 'slbp_webhook_action' ) ) {
	$webhook_id = (int) $_POST['webhook_id'];
	$result = $webhook_manager->delete_webhook( $webhook_id );

	if ( is_wp_error( $result ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
	} else {
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Webhook deleted successfully.', 'skylearn-billing-pro' ) . '</p></div>';
	}
}

if ( isset( $_POST['slbp_test_webhook'] ) && wp_verify_nonce( $_POST['slbp_webhook_nonce'], 'slbp_webhook_action' ) ) {
	$url = esc_url_raw( $_POST['test_url'] );
	$secret = sanitize_text_field( $_POST['test_secret'] );

	$result = $webhook_manager->test_webhook( $url, $secret );

	if ( is_wp_error( $result ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
	} else {
		$status_class = $result['success'] ? 'notice-success' : 'notice-error';
		echo '<div class="notice ' . $status_class . '"><p>' . sprintf(
			esc_html__( 'Webhook test completed. Response code: %d', 'skylearn-billing-pro' ),
			$result['response_code']
		) . '</p></div>';
	}
}

// Get existing data
$api_keys = $api_key_manager->get_user_api_keys( get_current_user_id() );
$permissions_desc = $api_key_manager->get_permission_descriptions();
$webhooks = $webhook_manager->get_user_webhooks( get_current_user_id() );
$events_desc = $webhook_manager->get_event_descriptions();
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
				
				<div class="slbp-docs-links">
					<p><strong><?php esc_html_e( 'Interactive Documentation:', 'skylearn-billing-pro' ); ?></strong></p>
					<ul>
						<li><a href="<?php echo esc_url( home_url( '/wp-json/slbp/v1/docs' ) ); ?>" target="_blank" class="button button-primary"><?php esc_html_e( 'Open Swagger UI', 'skylearn-billing-pro' ); ?></a></li>
						<li><a href="<?php echo esc_url( home_url( '/wp-json/slbp/v1/docs/openapi.json' ) ); ?>" target="_blank" class="button button-secondary"><?php esc_html_e( 'Download OpenAPI Spec', 'skylearn-billing-pro' ); ?></a></li>
					</ul>
				</div>
				
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
					<h4><?php esc_html_e( 'General', 'skylearn-billing-pro' ); ?></h4>
					<ul>
						<li><code>GET /wp-json/slbp/v1/status</code> - <?php esc_html_e( 'Get API status', 'skylearn-billing-pro' ); ?></li>
						<li><code>POST /wp-json/slbp/v1/auth</code> - <?php esc_html_e( 'Authenticate API key', 'skylearn-billing-pro' ); ?></li>
					</ul>
					
					<h4><?php esc_html_e( 'Billing', 'skylearn-billing-pro' ); ?></h4>
					<ul>
						<li><code>GET /wp-json/slbp/v1/billing/invoices</code> - <?php esc_html_e( 'Get invoices', 'skylearn-billing-pro' ); ?></li>
						<li><code>GET /wp-json/slbp/v1/billing/invoices/{id}</code> - <?php esc_html_e( 'Get specific invoice', 'skylearn-billing-pro' ); ?></li>
						<li><code>GET /wp-json/slbp/v1/billing/transactions</code> - <?php esc_html_e( 'Get transactions', 'skylearn-billing-pro' ); ?></li>
						<li><code>GET /wp-json/slbp/v1/billing/transactions/{id}</code> - <?php esc_html_e( 'Get specific transaction', 'skylearn-billing-pro' ); ?></li>
						<li><code>POST /wp-json/slbp/v1/billing/refunds</code> - <?php esc_html_e( 'Process refunds', 'skylearn-billing-pro' ); ?></li>
						<li><code>GET /wp-json/slbp/v1/billing/payment-methods</code> - <?php esc_html_e( 'Get payment methods', 'skylearn-billing-pro' ); ?></li>
					</ul>

					<h4><?php esc_html_e( 'Subscriptions', 'skylearn-billing-pro' ); ?></h4>
					<ul>
						<li><code>GET /wp-json/slbp/v1/subscriptions</code> - <?php esc_html_e( 'Get subscriptions', 'skylearn-billing-pro' ); ?></li>
						<li><code>GET /wp-json/slbp/v1/subscriptions/{id}</code> - <?php esc_html_e( 'Get specific subscription', 'skylearn-billing-pro' ); ?></li>
						<li><code>POST /wp-json/slbp/v1/subscriptions/{id}/cancel</code> - <?php esc_html_e( 'Cancel subscription', 'skylearn-billing-pro' ); ?></li>
					</ul>
					
					<h4><?php esc_html_e( 'Documentation', 'skylearn-billing-pro' ); ?></h4>
					<ul>
						<li><code>GET /wp-json/slbp/v1/docs</code> - <?php esc_html_e( 'Interactive Swagger UI', 'skylearn-billing-pro' ); ?></li>
						<li><code>GET /wp-json/slbp/v1/docs/openapi.json</code> - <?php esc_html_e( 'OpenAPI specification', 'skylearn-billing-pro' ); ?></li>
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
curl_close($ch);

$data = json_decode($response, true);</code></pre>

				<h4><?php esc_html_e( 'JavaScript (Fetch)', 'skylearn-billing-pro' ); ?></h4>
				<pre><code>fetch('<?php echo esc_html( home_url( '/wp-json/slbp/v1/billing/invoices' ) ); ?>', {
    headers: {
        'Authorization': 'Bearer YOUR_API_KEY',
        'Content-Type': 'application/json'
    }
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));</code></pre>

				<h4><?php esc_html_e( 'Python (requests)', 'skylearn-billing-pro' ); ?></h4>
				<pre><code>import requests

headers = {
    'Authorization': 'Bearer YOUR_API_KEY',
    'Content-Type': 'application/json'
}

response = requests.get('<?php echo esc_html( home_url( '/wp-json/slbp/v1/billing/invoices' ) ); ?>', headers=headers)
data = response.json()

if response.status_code == 200:
    print("Success:", data)
else:
    print("Error:", response.status_code, data)</code></pre>

				<h3><?php esc_html_e( 'Response Format', 'skylearn-billing-pro' ); ?></h3>
				<p><?php esc_html_e( 'All API responses use JSON format. Successful requests return HTTP status codes 200-299. Errors return 4xx or 5xx status codes with error details.', 'skylearn-billing-pro' ); ?></p>
				
				<h4><?php esc_html_e( 'Success Response Example', 'skylearn-billing-pro' ); ?></h4>
				<pre><code>{
    "id": "inv_123456",
    "user_id": 1,
    "amount": 29.99,
    "currency": "USD",
    "status": "paid",
    "gateway": "lemon_squeezy",
    "created_at": "2024-01-01T12:00:00Z"
}</code></pre>

				<h4><?php esc_html_e( 'Error Response Example', 'skylearn-billing-pro' ); ?></h4>
				<pre><code>{
    "code": "unauthorized",
    "message": "Authentication required",
    "data": {
        "status": 401
    }
}</code></pre>

				<h3><?php esc_html_e( 'Rate Limiting', 'skylearn-billing-pro' ); ?></h3>
				<p><?php esc_html_e( 'API requests are rate-limited based on your API key settings. Rate limit information is included in response headers:', 'skylearn-billing-pro' ); ?></p>
				<ul>
					<li><code>X-Rate-Limit-Remaining</code> - <?php esc_html_e( 'Number of requests remaining in current window', 'skylearn-billing-pro' ); ?></li>
				</ul>
			</div>
		</div>

		<div id="webhooks" class="tab-content" style="display: none;">
			<div class="slbp-api-grid">
				<!-- Create Webhook Form -->
				<div class="slbp-card">
					<h2><?php esc_html_e( 'Create New Webhook', 'skylearn-billing-pro' ); ?></h2>
					<form method="post" action="">
						<?php wp_nonce_field( 'slbp_webhook_action', 'slbp_webhook_nonce' ); ?>
						
						<table class="form-table">
							<tr>
								<th scope="row">
									<label for="webhook_name"><?php esc_html_e( 'Webhook Name', 'skylearn-billing-pro' ); ?></label>
								</th>
								<td>
									<input type="text" id="webhook_name" name="webhook_name" class="regular-text" required />
									<p class="description"><?php esc_html_e( 'A descriptive name for this webhook', 'skylearn-billing-pro' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="webhook_url"><?php esc_html_e( 'Webhook URL', 'skylearn-billing-pro' ); ?></label>
								</th>
								<td>
									<input type="url" id="webhook_url" name="webhook_url" class="regular-text" required />
									<p class="description"><?php esc_html_e( 'The URL to send webhook events to', 'skylearn-billing-pro' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="webhook_events"><?php esc_html_e( 'Events', 'skylearn-billing-pro' ); ?></label>
								</th>
								<td>
									<?php foreach ( $events_desc as $event => $description ) : ?>
										<label>
											<input type="checkbox" name="webhook_events[]" value="<?php echo esc_attr( $event ); ?>" />
											<strong><?php echo esc_html( $event ); ?></strong> - <?php echo esc_html( $description ); ?>
										</label><br />
									<?php endforeach; ?>
								</td>
							</tr>
						</table>
						
						<p class="submit">
							<input type="submit" name="slbp_create_webhook" class="button-primary" value="<?php esc_attr_e( 'Create Webhook', 'skylearn-billing-pro' ); ?>" />
						</p>
					</form>
				</div>

				<!-- Existing Webhooks -->
				<div class="slbp-card">
					<h2><?php esc_html_e( 'Your Webhooks', 'skylearn-billing-pro' ); ?></h2>
					
					<?php if ( empty( $webhooks ) ) : ?>
						<p><?php esc_html_e( 'No webhooks found. Create your first webhook to receive real-time event notifications.', 'skylearn-billing-pro' ); ?></p>
					<?php else : ?>
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Name', 'skylearn-billing-pro' ); ?></th>
									<th><?php esc_html_e( 'URL', 'skylearn-billing-pro' ); ?></th>
									<th><?php esc_html_e( 'Events', 'skylearn-billing-pro' ); ?></th>
									<th><?php esc_html_e( 'Status', 'skylearn-billing-pro' ); ?></th>
									<th><?php esc_html_e( 'Last Success', 'skylearn-billing-pro' ); ?></th>
									<th><?php esc_html_e( 'Failed Attempts', 'skylearn-billing-pro' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'skylearn-billing-pro' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $webhooks as $webhook ) : ?>
									<tr>
										<td><strong><?php echo esc_html( $webhook->name ); ?></strong></td>
										<td><code><?php echo esc_html( $webhook->url ); ?></code></td>
										<td><?php echo esc_html( implode( ', ', $webhook->events ) ); ?></td>
										<td>
											<?php if ( $webhook->is_active ) : ?>
												<span class="slbp-status-active"><?php esc_html_e( 'Active', 'skylearn-billing-pro' ); ?></span>
											<?php else : ?>
												<span class="slbp-status-inactive"><?php esc_html_e( 'Inactive', 'skylearn-billing-pro' ); ?></span>
											<?php endif; ?>
										</td>
										<td>
											<?php if ( $webhook->last_success_at ) : ?>
												<?php echo esc_html( human_time_diff( strtotime( $webhook->last_success_at ) ) ); ?> ago
											<?php else : ?>
												<?php esc_html_e( 'Never', 'skylearn-billing-pro' ); ?>
											<?php endif; ?>
										</td>
										<td>
											<?php if ( $webhook->failed_attempts > 0 ) : ?>
												<span class="slbp-failed-attempts"><?php echo esc_html( $webhook->failed_attempts ); ?></span>
											<?php else : ?>
												0
											<?php endif; ?>
										</td>
										<td>
											<form method="post" style="display: inline;">
												<?php wp_nonce_field( 'slbp_webhook_action', 'slbp_webhook_nonce' ); ?>
												<input type="hidden" name="webhook_id" value="<?php echo esc_attr( $webhook->id ); ?>" />
												<input type="submit" name="slbp_delete_webhook" class="button-link-delete" value="<?php esc_attr_e( 'Delete', 'skylearn-billing-pro' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this webhook?', 'skylearn-billing-pro' ); ?>')" />
											</form>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
			</div>

			<!-- Test Webhook -->
			<div class="slbp-card" style="margin-top: 20px;">
				<h2><?php esc_html_e( 'Test Webhook', 'skylearn-billing-pro' ); ?></h2>
				<p><?php esc_html_e( 'Test a webhook endpoint before creating it.', 'skylearn-billing-pro' ); ?></p>
				
				<form method="post" action="">
					<?php wp_nonce_field( 'slbp_webhook_action', 'slbp_webhook_nonce' ); ?>
					
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="test_url"><?php esc_html_e( 'Test URL', 'skylearn-billing-pro' ); ?></label>
							</th>
							<td>
								<input type="url" id="test_url" name="test_url" class="regular-text" required />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="test_secret"><?php esc_html_e( 'Test Secret', 'skylearn-billing-pro' ); ?></label>
							</th>
							<td>
								<input type="text" id="test_secret" name="test_secret" class="regular-text" value="test_secret" />
								<p class="description"><?php esc_html_e( 'Use any test secret for validation', 'skylearn-billing-pro' ); ?></p>
							</td>
						</tr>
					</table>
					
					<p class="submit">
						<input type="submit" name="slbp_test_webhook" class="button-secondary" value="<?php esc_attr_e( 'Test Webhook', 'skylearn-billing-pro' ); ?>" />
					</p>
				</form>
			</div>

			<!-- Webhook Information -->
			<div class="slbp-card" style="margin-top: 20px;">
				<h2><?php esc_html_e( 'Webhook Information', 'skylearn-billing-pro' ); ?></h2>
				
				<h3><?php esc_html_e( 'Security', 'skylearn-billing-pro' ); ?></h3>
				<p><?php esc_html_e( 'Each webhook includes a signature in the X-SLBP-Signature header that you can use to verify the request came from SkyLearn Billing Pro.', 'skylearn-billing-pro' ); ?></p>
				
				<h4><?php esc_html_e( 'Verify Signature (PHP)', 'skylearn-billing-pro' ); ?></h4>
				<pre><code>$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_SLBP_SIGNATURE'] ?? '';
$secret = 'your_webhook_secret';

$expected_signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (hash_equals($signature, $expected_signature)) {
    // Webhook is valid
    $data = json_decode($payload, true);
} else {
    // Invalid webhook
    http_response_code(401);
    exit('Unauthorized');
}</code></pre>

				<h3><?php esc_html_e( 'Headers', 'skylearn-billing-pro' ); ?></h3>
				<ul>
					<li><strong>X-SLBP-Event:</strong> <?php esc_html_e( 'The event type (e.g., payment_success)', 'skylearn-billing-pro' ); ?></li>
					<li><strong>X-SLBP-Signature:</strong> <?php esc_html_e( 'HMAC signature for verification', 'skylearn-billing-pro' ); ?></li>
					<li><strong>X-SLBP-Delivery:</strong> <?php esc_html_e( 'Unique delivery ID', 'skylearn-billing-pro' ); ?></li>
				</ul>

				<h3><?php esc_html_e( 'Retry Policy', 'skylearn-billing-pro' ); ?></h3>
				<p><?php esc_html_e( 'Failed webhooks are retried up to 3 times with exponential backoff (30s, 2m, 8m). Webhooks that fail 10 times are automatically disabled.', 'skylearn-billing-pro' ); ?></p>
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

.slbp-failed-attempts {
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