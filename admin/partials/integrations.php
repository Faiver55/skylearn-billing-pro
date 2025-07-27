<?php
/**
 * Integrations admin page template.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/admin/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Get integrations manager
$plugin = SLBP_Plugin::get_instance();
$integrations_manager = $plugin->resolve( 'integrations_manager' );

if ( ! $integrations_manager ) {
	echo '<div class="wrap">';
	echo '<h1>' . esc_html__( 'Integrations', 'skylearn-billing-pro' ) . '</h1>';
	echo '<div class="notice notice-error"><p>' . esc_html__( 'Integrations manager not initialized.', 'skylearn-billing-pro' ) . '</p></div>';
	echo '</div>';
	return;
}

$integrations = $integrations_manager->get_integrations();
$current_settings = get_option( 'slbp_integrations_settings', array() );

// Handle form submission
if ( isset( $_POST['submit'] ) && wp_verify_nonce( $_POST['slbp_integrations_nonce'], 'slbp_integrations_save' ) ) {
	$integration_id = sanitize_key( $_POST['integration_id'] );
	$settings = $_POST['integration_settings'][ $integration_id ] ?? array();
	
	if ( isset( $integrations[ $integration_id ] ) ) {
		// Sanitize settings based on integration config
		$integration_config = $integrations[ $integration_id ];
		$sanitized_settings = array();
		
		foreach ( $integration_config['settings'] as $setting_key => $setting_config ) {
			$value = $settings[ $setting_key ] ?? '';
			
			switch ( $setting_config['type'] ) {
				case 'text':
				case 'password':
					$sanitized_settings[ $setting_key ] = sanitize_text_field( $value );
					break;
				case 'url':
					$sanitized_settings[ $setting_key ] = esc_url_raw( $value );
					break;
				case 'email':
					$sanitized_settings[ $setting_key ] = sanitize_email( $value );
					break;
				case 'checkbox':
					$sanitized_settings[ $setting_key ] = ! empty( $value );
					break;
				case 'multiselect':
					$sanitized_settings[ $setting_key ] = is_array( $value ) ? array_map( 'sanitize_key', $value ) : array();
					break;
				default:
					$sanitized_settings[ $setting_key ] = sanitize_text_field( $value );
			}
		}
		
		// Add enabled status
		$sanitized_settings['enabled'] = ! empty( $settings['enabled'] );
		
		// Update settings
		$result = $integrations_manager->update_integration_settings( $integration_id, $sanitized_settings );
		
		if ( $result ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Integration settings saved successfully.', 'skylearn-billing-pro' ) . '</p></div>';
			$current_settings = get_option( 'slbp_integrations_settings', array() );
		} else {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Failed to save integration settings.', 'skylearn-billing-pro' ) . '</p></div>';
		}
	}
}

// Handle test connection
if ( isset( $_POST['test_connection'] ) && wp_verify_nonce( $_POST['slbp_integrations_nonce'], 'slbp_integrations_save' ) ) {
	$integration_id = sanitize_key( $_POST['integration_id'] );
	$test_result = $integrations_manager->test_integration( $integration_id );
	
	if ( is_wp_error( $test_result ) ) {
		echo '<div class="notice notice-error is-dismissible"><p>' . 
			 sprintf( esc_html__( 'Connection test failed: %s', 'skylearn-billing-pro' ), esc_html( $test_result->get_error_message() ) ) . 
			 '</p></div>';
	} else {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Connection test successful!', 'skylearn-billing-pro' ) . '</p></div>';
	}
}

$active_tab = $_GET['tab'] ?? 'overview';
?>

<div class="wrap">
	<h1><?php esc_html_e( 'SkyLearn Billing - Integrations', 'skylearn-billing-pro' ); ?></h1>
	
	<nav class="nav-tab-wrapper">
		<a href="?page=slbp-integrations&tab=overview" 
		   class="nav-tab <?php echo $active_tab === 'overview' ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Overview', 'skylearn-billing-pro' ); ?>
		</a>
		<?php foreach ( $integrations as $integration_id => $integration_config ) : ?>
			<a href="?page=slbp-integrations&tab=<?php echo esc_attr( $integration_id ); ?>" 
			   class="nav-tab <?php echo $active_tab === $integration_id ? 'nav-tab-active' : ''; ?>">
				<?php echo esc_html( $integration_config['name'] ); ?>
				<?php if ( ! empty( $current_settings[ $integration_id ]['enabled'] ) ) : ?>
					<span class="slbp-integration-status active" title="<?php esc_attr_e( 'Active', 'skylearn-billing-pro' ); ?>">●</span>
				<?php endif; ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<div class="tab-content">
		<?php if ( $active_tab === 'overview' ) : ?>
			<div class="slbp-integrations-overview">
				<h2><?php esc_html_e( 'Available Integrations', 'skylearn-billing-pro' ); ?></h2>
				<p><?php esc_html_e( 'Connect SkyLearn Billing Pro with your favorite tools and services to automate your workflow.', 'skylearn-billing-pro' ); ?></p>
				
				<div class="slbp-integrations-grid">
					<?php foreach ( $integrations as $integration_id => $integration_config ) : ?>
						<div class="slbp-integration-card">
							<div class="integration-header">
								<h3><?php echo esc_html( $integration_config['name'] ); ?></h3>
								<div class="integration-status">
									<?php if ( ! empty( $current_settings[ $integration_id ]['enabled'] ) ) : ?>
										<span class="status active"><?php esc_html_e( 'Active', 'skylearn-billing-pro' ); ?></span>
									<?php else : ?>
										<span class="status inactive"><?php esc_html_e( 'Inactive', 'skylearn-billing-pro' ); ?></span>
									<?php endif; ?>
								</div>
							</div>
							<p class="integration-description"><?php echo esc_html( $integration_config['description'] ); ?></p>
							<div class="integration-actions">
								<a href="?page=slbp-integrations&tab=<?php echo esc_attr( $integration_id ); ?>" class="button">
									<?php esc_html_e( 'Configure', 'skylearn-billing-pro' ); ?>
								</a>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="slbp-webhook-info">
					<h3><?php esc_html_e( 'Webhook Endpoints', 'skylearn-billing-pro' ); ?></h3>
					<p><?php esc_html_e( 'Use these endpoints for incoming webhooks from external services:', 'skylearn-billing-pro' ); ?></p>
					<div class="webhook-urls">
						<div class="webhook-url">
							<label><?php esc_html_e( 'Generic Webhook URL:', 'skylearn-billing-pro' ); ?></label>
							<code><?php echo esc_url( rest_url( 'slbp/v1/webhook' ) ); ?></code>
						</div>
						<?php foreach ( array_keys( $integrations ) as $integration_id ) : ?>
							<div class="webhook-url">
								<label><?php echo esc_html( ucfirst( $integration_id ) ); ?> <?php esc_html_e( 'Webhook URL:', 'skylearn-billing-pro' ); ?></label>
								<code><?php echo esc_url( rest_url( "slbp/v1/webhook/{$integration_id}" ) ); ?></code>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

		<?php else : ?>
			<?php 
			$integration_id = $active_tab;
			if ( isset( $integrations[ $integration_id ] ) ) :
				$integration_config = $integrations[ $integration_id ];
				$integration_settings = $current_settings[ $integration_id ] ?? array();
			?>
				<form method="post" action="">
					<?php wp_nonce_field( 'slbp_integrations_save', 'slbp_integrations_nonce' ); ?>
					<input type="hidden" name="integration_id" value="<?php echo esc_attr( $integration_id ); ?>">
					
					<div class="slbp-integration-settings">
						<h2><?php echo esc_html( $integration_config['name'] ); ?> <?php esc_html_e( 'Settings', 'skylearn-billing-pro' ); ?></h2>
						<p class="description"><?php echo esc_html( $integration_config['description'] ); ?></p>
						
						<table class="form-table">
							<tbody>
								<tr>
									<th scope="row">
										<label for="integration_enabled"><?php esc_html_e( 'Enable Integration', 'skylearn-billing-pro' ); ?></label>
									</th>
									<td>
										<input type="checkbox" 
											   name="integration_settings[<?php echo esc_attr( $integration_id ); ?>][enabled]" 
											   id="integration_enabled" 
											   value="1" 
											   <?php checked( ! empty( $integration_settings['enabled'] ) ); ?>>
										<p class="description"><?php esc_html_e( 'Enable this integration to start sending data.', 'skylearn-billing-pro' ); ?></p>
									</td>
								</tr>
								
								<?php foreach ( $integration_config['settings'] as $setting_key => $setting_config ) : ?>
									<tr>
										<th scope="row">
											<label for="setting_<?php echo esc_attr( $setting_key ); ?>">
												<?php echo esc_html( $setting_config['label'] ); ?>
											</label>
										</th>
										<td>
											<?php 
											$setting_name = "integration_settings[{$integration_id}][{$setting_key}]";
											$setting_value = $integration_settings[ $setting_key ] ?? $setting_config['default'] ?? '';
											$setting_id = "setting_{$setting_key}";
											
											switch ( $setting_config['type'] ) :
												case 'text':
												case 'email':
												case 'url':
											?>
													<input type="<?php echo esc_attr( $setting_config['type'] ); ?>" 
														   name="<?php echo esc_attr( $setting_name ); ?>" 
														   id="<?php echo esc_attr( $setting_id ); ?>" 
														   value="<?php echo esc_attr( $setting_value ); ?>" 
														   class="regular-text">
											<?php 
												break;
												case 'password':
											?>
													<input type="password" 
														   name="<?php echo esc_attr( $setting_name ); ?>" 
														   id="<?php echo esc_attr( $setting_id ); ?>" 
														   value="<?php echo esc_attr( $setting_value ); ?>" 
														   class="regular-text">
											<?php 
												break;
												case 'checkbox':
											?>
													<input type="checkbox" 
														   name="<?php echo esc_attr( $setting_name ); ?>" 
														   id="<?php echo esc_attr( $setting_id ); ?>" 
														   value="1" 
														   <?php checked( ! empty( $setting_value ) ); ?>>
											<?php 
												break;
												case 'multiselect':
													if ( ! empty( $setting_config['options'] ) ) :
											?>
														<div class="slbp-multiselect">
															<?php foreach ( $setting_config['options'] as $option_value => $option_label ) : ?>
																<label>
																	<input type="checkbox" 
																		   name="<?php echo esc_attr( $setting_name ); ?>[]" 
																		   value="<?php echo esc_attr( $option_value ); ?>" 
																		   <?php checked( in_array( $option_value, (array) $setting_value ) ); ?>>
																	<?php echo esc_html( $option_label ); ?>
																</label><br>
															<?php endforeach; ?>
														</div>
											<?php 
													endif;
												break;
											endswitch;
											
											if ( ! empty( $setting_config['description'] ) ) :
											?>
												<p class="description"><?php echo esc_html( $setting_config['description'] ); ?></p>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						
						<p class="submit">
							<?php submit_button( __( 'Save Settings', 'skylearn-billing-pro' ), 'primary', 'submit', false ); ?>
							<input type="submit" name="test_connection" value="<?php esc_attr_e( 'Test Connection', 'skylearn-billing-pro' ); ?>" class="button button-secondary">
						</p>
					</div>
				</form>
			<?php else : ?>
				<div class="notice notice-error">
					<p><?php esc_html_e( 'Integration not found.', 'skylearn-billing-pro' ); ?></p>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>

<style>
.slbp-integrations-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
	gap: 20px;
	margin: 20px 0;
}

.slbp-integration-card {
	border: 1px solid #c3c4c7;
	border-radius: 4px;
	padding: 20px;
	background: #fff;
}

.integration-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 10px;
}

.integration-header h3 {
	margin: 0;
	font-size: 16px;
}

.integration-status .status {
	padding: 4px 8px;
	border-radius: 3px;
	font-size: 11px;
	font-weight: 600;
	text-transform: uppercase;
}

.integration-status .status.active {
	background: #00a32a;
	color: white;
}

.integration-status .status.inactive {
	background: #ddd;
	color: #666;
}

.integration-description {
	color: #646970;
	margin-bottom: 15px;
}

.slbp-integration-status {
	color: #00a32a;
	font-size: 12px;
}

.slbp-webhook-info {
	margin-top: 30px;
	padding: 20px;
	background: #f6f7f7;
	border-left: 4px solid #0073aa;
}

.webhook-urls {
	margin-top: 15px;
}

.webhook-url {
	margin-bottom: 10px;
}

.webhook-url label {
	display: block;
	font-weight: 600;
	margin-bottom: 5px;
}

.webhook-url code {
	display: block;
	padding: 8px 12px;
	background: #fff;
	border: 1px solid #c3c4c7;
	border-radius: 3px;
	word-break: break-all;
}

.slbp-multiselect label {
	display: block;
	margin-bottom: 5px;
}
</style>