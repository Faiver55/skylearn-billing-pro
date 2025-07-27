<?php
/**
 * The setup wizard for guiding admins through initial configuration.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/onboarding
 */

/**
 * The setup wizard class.
 *
 * Handles the initial setup wizard to help admins configure the plugin.
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/onboarding
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Setup_Wizard {

	/**
	 * Setup steps configuration.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $steps    Setup steps configuration.
	 */
	private $steps;

	/**
	 * Current step.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string   $current_step   Current step ID.
	 */
	private $current_step;

	/**
	 * Initialize the setup wizard.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->init_steps();
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		// Add setup wizard to admin menu
		add_action( 'admin_menu', array( $this, 'add_setup_wizard_page' ) );
		
		// AJAX handlers
		add_action( 'wp_ajax_slbp_setup_wizard_save_step', array( $this, 'ajax_save_step' ) );
		add_action( 'wp_ajax_slbp_setup_wizard_skip_step', array( $this, 'ajax_skip_step' ) );
		add_action( 'wp_ajax_slbp_setup_wizard_test_connection', array( $this, 'ajax_test_connection' ) );
		add_action( 'wp_ajax_slbp_dismiss_setup_notice', array( $this, 'ajax_dismiss_setup_notice' ) );
		
		// Enqueue assets
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_wizard_assets' ) );
		
		// Show setup notice if not completed
		add_action( 'admin_notices', array( $this, 'show_setup_notice' ) );
		
		// Redirect to setup wizard after activation
		add_action( 'admin_init', array( $this, 'maybe_redirect_to_setup' ) );
	}

	/**
	 * Initialize setup steps.
	 *
	 * @since    1.0.0
	 */
	private function init_steps() {
		$this->steps = array(
			'welcome' => array(
				'title'       => __( 'Welcome', 'skylearn-billing-pro' ),
				'description' => __( 'Welcome to SkyLearn Billing Pro setup wizard', 'skylearn-billing-pro' ),
				'content'     => array( $this, 'render_welcome_step' ),
			),
			'payment_gateway' => array(
				'title'       => __( 'Payment Gateway', 'skylearn-billing-pro' ),
				'description' => __( 'Configure your payment gateway settings', 'skylearn-billing-pro' ),
				'content'     => array( $this, 'render_payment_gateway_step' ),
			),
			'lms_integration' => array(
				'title'       => __( 'LMS Integration', 'skylearn-billing-pro' ),
				'description' => __( 'Set up LearnDash integration', 'skylearn-billing-pro' ),
				'content'     => array( $this, 'render_lms_integration_step' ),
			),
			'notifications' => array(
				'title'       => __( 'Notifications', 'skylearn-billing-pro' ),
				'description' => __( 'Configure email notifications', 'skylearn-billing-pro' ),
				'content'     => array( $this, 'render_notifications_step' ),
			),
			'integrations' => array(
				'title'       => __( 'Integrations', 'skylearn-billing-pro' ),
				'description' => __( 'Set up third-party integrations', 'skylearn-billing-pro' ),
				'content'     => array( $this, 'render_integrations_step' ),
			),
			'complete' => array(
				'title'       => __( 'Complete', 'skylearn-billing-pro' ),
				'description' => __( 'Setup completed successfully', 'skylearn-billing-pro' ),
				'content'     => array( $this, 'render_complete_step' ),
			),
		);

		/**
		 * Allow customization of setup steps.
		 *
		 * @since 1.0.0
		 *
		 * @param array $steps Setup steps configuration.
		 */
		$this->steps = apply_filters( 'slbp_setup_wizard_steps', $this->steps );
	}

	/**
	 * Add setup wizard admin page.
	 *
	 * @since    1.0.0
	 */
	public function add_setup_wizard_page() {
		add_submenu_page(
			null, // Hide from menu
			__( 'SkyLearn Billing Setup', 'skylearn-billing-pro' ),
			__( 'Setup Wizard', 'skylearn-billing-pro' ),
			'manage_options',
			'slbp-setup-wizard',
			array( $this, 'render_setup_wizard' )
		);
	}

	/**
	 * Enqueue wizard assets.
	 *
	 * @since    1.0.0
	 * @param    string $hook Current admin page hook.
	 */
	public function enqueue_wizard_assets( $hook ) {
		if ( $hook !== 'admin_page_slbp-setup-wizard' ) {
			return;
		}

		wp_enqueue_style( 
			'slbp-setup-wizard', 
			SLBP_PLUGIN_URL . 'admin/css/setup-wizard.css', 
			array(), 
			SLBP_VERSION 
		);

		wp_enqueue_script( 
			'slbp-setup-wizard', 
			SLBP_PLUGIN_URL . 'admin/js/setup-wizard.js', 
			array( 'jquery' ), 
			SLBP_VERSION, 
			true 
		);

		wp_localize_script( 'slbp-setup-wizard', 'slbp_setup_wizard', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'slbp_setup_wizard_nonce' ),
			'strings'  => array(
				'saving'        => __( 'Saving...', 'skylearn-billing-pro' ),
				'testing'       => __( 'Testing connection...', 'skylearn-billing-pro' ),
				'success'       => __( 'Success!', 'skylearn-billing-pro' ),
				'error'         => __( 'Error occurred', 'skylearn-billing-pro' ),
				'confirm_skip'  => __( 'Are you sure you want to skip this step?', 'skylearn-billing-pro' ),
			),
		) );
	}

	/**
	 * Show setup notice if setup is not completed.
	 *
	 * @since    1.0.0
	 */
	public function show_setup_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$setup_completed = get_option( 'slbp_setup_completed', false );
		$notice_dismissed = get_option( 'slbp_setup_notice_dismissed', false );

		if ( ! $setup_completed && ! $notice_dismissed ) {
			$setup_url = admin_url( 'admin.php?page=slbp-setup-wizard' );
			?>
			<div class="notice notice-info is-dismissible slbp-setup-notice">
				<p>
					<strong><?php esc_html_e( 'SkyLearn Billing Pro', 'skylearn-billing-pro' ); ?></strong> - 
					<?php esc_html_e( 'Complete the setup wizard to get started.', 'skylearn-billing-pro' ); ?>
				</p>
				<p>
					<a href="<?php echo esc_url( $setup_url ); ?>" class="button button-primary">
						<?php esc_html_e( 'Run Setup Wizard', 'skylearn-billing-pro' ); ?>
					</a>
					<button type="button" class="button button-secondary slbp-dismiss-setup-notice">
						<?php esc_html_e( 'Dismiss', 'skylearn-billing-pro' ); ?>
					</button>
				</p>
			</div>
			<script>
			jQuery(document).ready(function($) {
				$('.slbp-dismiss-setup-notice').on('click', function() {
					$.post(ajaxurl, {
						action: 'slbp_dismiss_setup_notice',
						nonce: '<?php echo wp_create_nonce( 'slbp_dismiss_setup_notice' ); ?>'
					});
					$('.slbp-setup-notice').fadeOut();
				});
			});
			</script>
			<?php
		}
	}

	/**
	 * Maybe redirect to setup wizard after activation.
	 *
	 * @since    1.0.0
	 */
	public function maybe_redirect_to_setup() {
		if ( get_transient( 'slbp_setup_wizard_redirect' ) ) {
			delete_transient( 'slbp_setup_wizard_redirect' );
			
			if ( ! wp_doing_ajax() && current_user_can( 'manage_options' ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=slbp-setup-wizard' ) );
				exit;
			}
		}
	}

	/**
	 * Render the setup wizard.
	 *
	 * @since    1.0.0
	 */
	public function render_setup_wizard() {
		$this->current_step = $_GET['step'] ?? 'welcome';
		
		if ( ! isset( $this->steps[ $this->current_step ] ) ) {
			$this->current_step = 'welcome';
		}

		?>
		<div class="slbp-setup-wizard">
			<div class="wizard-header">
				<h1><?php esc_html_e( 'SkyLearn Billing Pro Setup', 'skylearn-billing-pro' ); ?></h1>
				<?php $this->render_progress_bar(); ?>
			</div>

			<div class="wizard-content">
				<div class="wizard-step" id="step-<?php echo esc_attr( $this->current_step ); ?>">
					<div class="step-header">
						<h2><?php echo esc_html( $this->steps[ $this->current_step ]['title'] ); ?></h2>
						<p class="step-description"><?php echo esc_html( $this->steps[ $this->current_step ]['description'] ); ?></p>
					</div>
					
					<div class="step-content">
						<?php call_user_func( $this->steps[ $this->current_step ]['content'] ); ?>
					</div>
				</div>
			</div>

			<div class="wizard-footer">
				<?php $this->render_navigation(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render progress bar.
	 *
	 * @since    1.0.0
	 */
	private function render_progress_bar() {
		$step_keys = array_keys( $this->steps );
		$current_index = array_search( $this->current_step, $step_keys );
		$total_steps = count( $step_keys );
		$progress_percentage = ( $current_index / ( $total_steps - 1 ) ) * 100;

		?>
		<div class="wizard-progress">
			<div class="progress-bar">
				<div class="progress-fill" style="width: <?php echo esc_attr( $progress_percentage ); ?>%"></div>
			</div>
			<div class="progress-steps">
				<?php foreach ( $step_keys as $index => $step_key ) : ?>
					<div class="progress-step <?php echo $index <= $current_index ? 'completed' : ''; ?> <?php echo $step_key === $this->current_step ? 'current' : ''; ?>">
						<span class="step-number"><?php echo esc_html( $index + 1 ); ?></span>
						<span class="step-title"><?php echo esc_html( $this->steps[ $step_key ]['title'] ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render navigation buttons.
	 *
	 * @since    1.0.0
	 */
	private function render_navigation() {
		$step_keys = array_keys( $this->steps );
		$current_index = array_search( $this->current_step, $step_keys );
		$prev_step = $current_index > 0 ? $step_keys[ $current_index - 1 ] : null;
		$next_step = $current_index < count( $step_keys ) - 1 ? $step_keys[ $current_index + 1 ] : null;

		?>
		<div class="wizard-navigation">
			<div class="nav-left">
				<?php if ( $prev_step ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'step', $prev_step ) ); ?>" class="button button-secondary">
						<?php esc_html_e( '← Previous', 'skylearn-billing-pro' ); ?>
					</a>
				<?php endif; ?>
			</div>
			
			<div class="nav-right">
				<?php if ( $this->current_step !== 'complete' ) : ?>
					<button type="button" class="button button-secondary skip-step" data-step="<?php echo esc_attr( $this->current_step ); ?>">
						<?php esc_html_e( 'Skip', 'skylearn-billing-pro' ); ?>
					</button>
				<?php endif; ?>
				
				<?php if ( $next_step ) : ?>
					<button type="button" class="button button-primary save-and-continue" data-current-step="<?php echo esc_attr( $this->current_step ); ?>" data-next-step="<?php echo esc_attr( $next_step ); ?>">
						<?php echo $this->current_step === 'complete' ? esc_html__( 'Finish', 'skylearn-billing-pro' ) : esc_html__( 'Save & Continue →', 'skylearn-billing-pro' ); ?>
					</button>
				<?php else : ?>
					<button type="button" class="button button-primary finish-setup">
						<?php esc_html_e( 'Finish Setup', 'skylearn-billing-pro' ); ?>
					</button>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render welcome step.
	 *
	 * @since    1.0.0
	 */
	public function render_welcome_step() {
		?>
		<div class="welcome-content">
			<div class="welcome-icon">
				<span class="dashicons dashicons-admin-plugins"></span>
			</div>
			
			<h3><?php esc_html_e( 'Welcome to SkyLearn Billing Pro!', 'skylearn-billing-pro' ); ?></h3>
			
			<p class="lead">
				<?php esc_html_e( 'This wizard will help you set up your billing system in just a few simple steps.', 'skylearn-billing-pro' ); ?>
			</p>
			
			<div class="features-list">
				<h4><?php esc_html_e( 'What you\'ll configure:', 'skylearn-billing-pro' ); ?></h4>
				<ul>
					<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Payment gateway integration', 'skylearn-billing-pro' ); ?></li>
					<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'LearnDash course mapping', 'skylearn-billing-pro' ); ?></li>
					<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Email notification settings', 'skylearn-billing-pro' ); ?></li>
					<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Third-party integrations', 'skylearn-billing-pro' ); ?></li>
				</ul>
			</div>
			
			<div class="setup-time-estimate">
				<p>
					<span class="dashicons dashicons-clock"></span>
					<?php esc_html_e( 'Estimated setup time: 5-10 minutes', 'skylearn-billing-pro' ); ?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render payment gateway step.
	 *
	 * @since    1.0.0
	 */
	public function render_payment_gateway_step() {
		$payment_settings = get_option( 'slbp_payment_settings', array() );
		?>
		<form id="payment-gateway-form" class="wizard-form">
			<div class="form-section">
				<h4><?php esc_html_e( 'Choose Payment Gateway', 'skylearn-billing-pro' ); ?></h4>
				
				<div class="gateway-options">
					<label class="gateway-option">
						<input type="radio" name="gateway" value="lemon_squeezy" <?php checked( 'lemon_squeezy', $payment_settings['default_gateway'] ?? '' ); ?>>
						<div class="gateway-card">
							<div class="gateway-logo">🍋</div>
							<div class="gateway-info">
								<h5><?php esc_html_e( 'Lemon Squeezy', 'skylearn-billing-pro' ); ?></h5>
								<p><?php esc_html_e( 'Digital product payments with global tax compliance', 'skylearn-billing-pro' ); ?></p>
							</div>
						</div>
					</label>
				</div>
			</div>

			<div class="form-section lemon-squeezy-settings" style="display: none;">
				<h4><?php esc_html_e( 'Lemon Squeezy Settings', 'skylearn-billing-pro' ); ?></h4>
				
				<div class="form-group">
					<label for="lemon_squeezy_api_key"><?php esc_html_e( 'API Key', 'skylearn-billing-pro' ); ?></label>
					<input type="password" id="lemon_squeezy_api_key" name="lemon_squeezy_api_key" 
						   value="<?php echo esc_attr( $payment_settings['lemon_squeezy_api_key'] ?? '' ); ?>" 
						   class="regular-text">
					<p class="description">
						<?php printf( 
							esc_html__( 'Get your API key from %s', 'skylearn-billing-pro' ),
							'<a href="https://app.lemonsqueezy.com/settings/api" target="_blank">Lemon Squeezy Settings</a>'
						); ?>
					</p>
				</div>
				
				<div class="form-group">
					<label for="lemon_squeezy_store_id"><?php esc_html_e( 'Store ID', 'skylearn-billing-pro' ); ?></label>
					<input type="text" id="lemon_squeezy_store_id" name="lemon_squeezy_store_id" 
						   value="<?php echo esc_attr( $payment_settings['lemon_squeezy_store_id'] ?? '' ); ?>" 
						   class="regular-text">
				</div>
				
				<div class="form-group">
					<label>
						<input type="checkbox" name="lemon_squeezy_test_mode" value="1" 
							   <?php checked( ! empty( $payment_settings['lemon_squeezy_test_mode'] ) ); ?>>
						<?php esc_html_e( 'Enable test mode', 'skylearn-billing-pro' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Use test mode while setting up your integration', 'skylearn-billing-pro' ); ?></p>
				</div>
				
				<div class="form-actions">
					<button type="button" class="button test-connection" data-gateway="lemon_squeezy">
						<?php esc_html_e( 'Test Connection', 'skylearn-billing-pro' ); ?>
					</button>
					<div class="connection-status"></div>
				</div>
			</div>
		</form>
		<?php
	}

	/**
	 * Render LMS integration step.
	 *
	 * @since    1.0.0
	 */
	public function render_lms_integration_step() {
		$lms_settings = get_option( 'slbp_lms_settings', array() );
		$learndash_active = is_plugin_active( 'sfwd-lms/sfwd_lms.php' );
		?>
		<form id="lms-integration-form" class="wizard-form">
			<div class="form-section">
				<h4><?php esc_html_e( 'LearnDash Integration', 'skylearn-billing-pro' ); ?></h4>
				
				<?php if ( $learndash_active ) : ?>
					<div class="integration-status success">
						<span class="dashicons dashicons-yes-alt"></span>
						<?php esc_html_e( 'LearnDash is active and ready to integrate', 'skylearn-billing-pro' ); ?>
					</div>
					
					<div class="form-group">
						<label>
							<input type="checkbox" name="learndash_enabled" value="1" 
								   <?php checked( $lms_settings['learndash_enabled'] ?? true ); ?>>
							<?php esc_html_e( 'Enable LearnDash integration', 'skylearn-billing-pro' ); ?>
						</label>
					</div>
					
					<div class="form-group">
						<label>
							<input type="checkbox" name="learndash_auto_enroll" value="1" 
								   <?php checked( $lms_settings['learndash_auto_enroll'] ?? true ); ?>>
							<?php esc_html_e( 'Automatically enroll users after successful payment', 'skylearn-billing-pro' ); ?>
						</label>
					</div>
					
					<div class="form-group">
						<label for="learndash_access_duration"><?php esc_html_e( 'Default access duration (days)', 'skylearn-billing-pro' ); ?></label>
						<input type="number" id="learndash_access_duration" name="learndash_access_duration" 
							   value="<?php echo esc_attr( $lms_settings['learndash_access_duration'] ?? 0 ); ?>" 
							   class="small-text">
						<p class="description"><?php esc_html_e( 'Set to 0 for lifetime access', 'skylearn-billing-pro' ); ?></p>
					</div>
				<?php else : ?>
					<div class="integration-status warning">
						<span class="dashicons dashicons-warning"></span>
						<?php esc_html_e( 'LearnDash is not installed or activated', 'skylearn-billing-pro' ); ?>
					</div>
					
					<p><?php esc_html_e( 'To use course enrollment features, please install and activate LearnDash.', 'skylearn-billing-pro' ); ?></p>
					
					<div class="form-actions">
						<a href="<?php echo admin_url( 'plugin-install.php?s=learndash&tab=search&type=term' ); ?>" 
						   class="button button-secondary" target="_blank">
							<?php esc_html_e( 'Find LearnDash Plugin', 'skylearn-billing-pro' ); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</form>
		<?php
	}

	/**
	 * Render notifications step.
	 *
	 * @since    1.0.0
	 */
	public function render_notifications_step() {
		$notification_settings = get_option( 'slbp_notification_settings', array() );
		?>
		<form id="notifications-form" class="wizard-form">
			<div class="form-section">
				<h4><?php esc_html_e( 'Email Notification Settings', 'skylearn-billing-pro' ); ?></h4>
				
				<div class="form-group">
					<label for="from_email"><?php esc_html_e( 'From Email', 'skylearn-billing-pro' ); ?></label>
					<input type="email" id="from_email" name="from_email" 
						   value="<?php echo esc_attr( $notification_settings['from_email'] ?? get_option( 'admin_email' ) ); ?>" 
						   class="regular-text">
				</div>
				
				<div class="form-group">
					<label for="from_name"><?php esc_html_e( 'From Name', 'skylearn-billing-pro' ); ?></label>
					<input type="text" id="from_name" name="from_name" 
						   value="<?php echo esc_attr( $notification_settings['from_name'] ?? get_bloginfo( 'name' ) ); ?>" 
						   class="regular-text">
				</div>
				
				<h4><?php esc_html_e( 'Notification Types', 'skylearn-billing-pro' ); ?></h4>
				
				<div class="notification-types">
					<label>
						<input type="checkbox" name="notifications[payment_success]" value="1" 
							   <?php checked( $notification_settings['notifications']['payment_success'] ?? true ); ?>>
						<?php esc_html_e( 'Payment success notifications', 'skylearn-billing-pro' ); ?>
					</label>
					
					<label>
						<input type="checkbox" name="notifications[payment_failed]" value="1" 
							   <?php checked( $notification_settings['notifications']['payment_failed'] ?? true ); ?>>
						<?php esc_html_e( 'Payment failure notifications', 'skylearn-billing-pro' ); ?>
					</label>
					
					<label>
						<input type="checkbox" name="notifications[subscription_created]" value="1" 
							   <?php checked( $notification_settings['notifications']['subscription_created'] ?? true ); ?>>
						<?php esc_html_e( 'New subscription notifications', 'skylearn-billing-pro' ); ?>
					</label>
					
					<label>
						<input type="checkbox" name="notifications[enrollment_created]" value="1" 
							   <?php checked( $notification_settings['notifications']['enrollment_created'] ?? true ); ?>>
						<?php esc_html_e( 'Course enrollment notifications', 'skylearn-billing-pro' ); ?>
					</label>
				</div>
			</div>
		</form>
		<?php
	}

	/**
	 * Render integrations step.
	 *
	 * @since    1.0.0
	 */
	public function render_integrations_step() {
		$integration_settings = get_option( 'slbp_integrations_settings', array() );
		?>
		<form id="integrations-form" class="wizard-form">
			<div class="form-section">
				<h4><?php esc_html_e( 'Third-Party Integrations', 'skylearn-billing-pro' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Connect with external services to automate your workflow. You can configure these later if needed.', 'skylearn-billing-pro' ); ?></p>
				
				<div class="integration-options">
					<div class="integration-option">
						<div class="integration-header">
							<label>
								<input type="checkbox" name="mailchimp_enabled" value="1" 
									   <?php checked( ! empty( $integration_settings['mailchimp']['enabled'] ) ); ?>>
								<strong><?php esc_html_e( 'Mailchimp', 'skylearn-billing-pro' ); ?></strong>
							</label>
							<p><?php esc_html_e( 'Automatically add customers to your mailing list', 'skylearn-billing-pro' ); ?></p>
						</div>
						
						<div class="integration-settings mailchimp-settings" style="display: none;">
							<div class="form-group">
								<label for="mailchimp_api_key"><?php esc_html_e( 'API Key', 'skylearn-billing-pro' ); ?></label>
								<input type="password" id="mailchimp_api_key" name="mailchimp_api_key" 
									   value="<?php echo esc_attr( $integration_settings['mailchimp']['api_key'] ?? '' ); ?>" 
									   class="regular-text">
							</div>
							
							<div class="form-group">
								<label for="mailchimp_list_id"><?php esc_html_e( 'List ID', 'skylearn-billing-pro' ); ?></label>
								<input type="text" id="mailchimp_list_id" name="mailchimp_list_id" 
									   value="<?php echo esc_attr( $integration_settings['mailchimp']['list_id'] ?? '' ); ?>" 
									   class="regular-text">
							</div>
						</div>
					</div>
					
					<div class="integration-option">
						<div class="integration-header">
							<label>
								<input type="checkbox" name="zapier_enabled" value="1" 
									   <?php checked( ! empty( $integration_settings['zapier']['enabled'] ) ); ?>>
								<strong><?php esc_html_e( 'Zapier', 'skylearn-billing-pro' ); ?></strong>
							</label>
							<p><?php esc_html_e( 'Connect to 1000+ apps for automation', 'skylearn-billing-pro' ); ?></p>
						</div>
						
						<div class="integration-settings zapier-settings" style="display: none;">
							<div class="form-group">
								<label for="zapier_webhook_url"><?php esc_html_e( 'Webhook URL', 'skylearn-billing-pro' ); ?></label>
								<input type="url" id="zapier_webhook_url" name="zapier_webhook_url" 
									   value="<?php echo esc_attr( $integration_settings['zapier']['webhook_url'] ?? '' ); ?>" 
									   class="regular-text">
								<p class="description"><?php esc_html_e( 'Get this from your Zapier webhook trigger', 'skylearn-billing-pro' ); ?></p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</form>
		<?php
	}

	/**
	 * Render complete step.
	 *
	 * @since    1.0.0
	 */
	public function render_complete_step() {
		?>
		<div class="complete-content">
			<div class="success-icon">
				<span class="dashicons dashicons-yes-alt"></span>
			</div>
			
			<h3><?php esc_html_e( 'Setup Complete!', 'skylearn-billing-pro' ); ?></h3>
			
			<p class="lead">
				<?php esc_html_e( 'Congratulations! SkyLearn Billing Pro has been successfully configured.', 'skylearn-billing-pro' ); ?>
			</p>
			
			<div class="next-steps">
				<h4><?php esc_html_e( 'What\'s next?', 'skylearn-billing-pro' ); ?></h4>
				<ul>
					<li>
						<a href="<?php echo admin_url( 'admin.php?page=skylearn-billing-pro' ); ?>">
							<?php esc_html_e( 'Visit your dashboard', 'skylearn-billing-pro' ); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo admin_url( 'admin.php?page=slbp-settings' ); ?>">
							<?php esc_html_e( 'Fine-tune your settings', 'skylearn-billing-pro' ); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo admin_url( 'admin.php?page=slbp-help' ); ?>">
							<?php esc_html_e( 'Read the documentation', 'skylearn-billing-pro' ); ?>
						</a>
					</li>
				</ul>
			</div>
			
			<div class="support-info">
				<h4><?php esc_html_e( 'Need help?', 'skylearn-billing-pro' ); ?></h4>
				<p>
					<?php printf( 
						esc_html__( 'Contact our support team at %s', 'skylearn-billing-pro' ),
						'<a href="mailto:contact@skyianllc.com">contact@skyianllc.com</a>'
					); ?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX handler to save step data.
	 *
	 * @since    1.0.0
	 */
	public function ajax_save_step() {
		check_ajax_referer( 'slbp_setup_wizard_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', 'skylearn-billing-pro' ) );
		}

		$step = sanitize_key( $_POST['step'] ?? '' );
		$data = $_POST['data'] ?? array();

		$result = $this->save_step_data( $step, $data );

		if ( $result ) {
			wp_send_json_success( __( 'Step saved successfully.', 'skylearn-billing-pro' ) );
		} else {
			wp_send_json_error( __( 'Failed to save step data.', 'skylearn-billing-pro' ) );
		}
	}

	/**
	 * AJAX handler to skip step.
	 *
	 * @since    1.0.0
	 */
	public function ajax_skip_step() {
		check_ajax_referer( 'slbp_setup_wizard_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', 'skylearn-billing-pro' ) );
		}

		$step = sanitize_key( $_POST['step'] ?? '' );
		
		// Mark step as skipped
		$skipped_steps = get_option( 'slbp_setup_skipped_steps', array() );
		$skipped_steps[] = $step;
		update_option( 'slbp_setup_skipped_steps', array_unique( $skipped_steps ) );

		wp_send_json_success( __( 'Step skipped.', 'skylearn-billing-pro' ) );
	}

	/**
	 * AJAX handler to test connection.
	 *
	 * @since    1.0.0
	 */
	public function ajax_test_connection() {
		check_ajax_referer( 'slbp_setup_wizard_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', 'skylearn-billing-pro' ) );
		}

		$gateway = sanitize_key( $_POST['gateway'] ?? '' );
		$settings = $_POST['settings'] ?? array();

		// Test connection based on gateway
		$result = $this->test_gateway_connection( $gateway, $settings );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		} else {
			wp_send_json_success( __( 'Connection successful!', 'skylearn-billing-pro' ) );
		}
	}

	/**
	 * Save step data.
	 *
	 * @since    1.0.0
	 * @param    string $step The step ID.
	 * @param    array  $data The step data.
	 * @return   bool         True if saved successfully.
	 */
	private function save_step_data( $step, $data ) {
		switch ( $step ) {
			case 'payment_gateway':
				return $this->save_payment_gateway_data( $data );
			case 'lms_integration':
				return $this->save_lms_integration_data( $data );
			case 'notifications':
				return $this->save_notifications_data( $data );
			case 'integrations':
				return $this->save_integrations_data( $data );
			case 'complete':
				return $this->complete_setup();
			default:
				return true;
		}
	}

	/**
	 * Save payment gateway data.
	 *
	 * @since    1.0.0
	 * @param    array $data Gateway data.
	 * @return   bool        True if saved successfully.
	 */
	private function save_payment_gateway_data( $data ) {
		$payment_settings = get_option( 'slbp_payment_settings', array() );

		if ( ! empty( $data['gateway'] ) ) {
			$payment_settings['default_gateway'] = sanitize_key( $data['gateway'] );
			
			if ( $data['gateway'] === 'lemon_squeezy' ) {
				$payment_settings['lemon_squeezy_api_key'] = sanitize_text_field( $data['lemon_squeezy_api_key'] ?? '' );
				$payment_settings['lemon_squeezy_store_id'] = sanitize_text_field( $data['lemon_squeezy_store_id'] ?? '' );
				$payment_settings['lemon_squeezy_test_mode'] = ! empty( $data['lemon_squeezy_test_mode'] );
			}
		}

		return update_option( 'slbp_payment_settings', $payment_settings );
	}

	/**
	 * Save LMS integration data.
	 *
	 * @since    1.0.0
	 * @param    array $data LMS data.
	 * @return   bool        True if saved successfully.
	 */
	private function save_lms_integration_data( $data ) {
		$lms_settings = array(
			'learndash_enabled'        => ! empty( $data['learndash_enabled'] ),
			'learndash_auto_enroll'    => ! empty( $data['learndash_auto_enroll'] ),
			'learndash_access_duration' => intval( $data['learndash_access_duration'] ?? 0 ),
		);

		return update_option( 'slbp_lms_settings', $lms_settings );
	}

	/**
	 * Save notifications data.
	 *
	 * @since    1.0.0
	 * @param    array $data Notifications data.
	 * @return   bool        True if saved successfully.
	 */
	private function save_notifications_data( $data ) {
		$notification_settings = array(
			'from_email'      => sanitize_email( $data['from_email'] ?? '' ),
			'from_name'       => sanitize_text_field( $data['from_name'] ?? '' ),
			'notifications'   => array(),
		);

		if ( ! empty( $data['notifications'] ) ) {
			foreach ( $data['notifications'] as $type => $enabled ) {
				$notification_settings['notifications'][ sanitize_key( $type ) ] = ! empty( $enabled );
			}
		}

		return update_option( 'slbp_notification_settings', $notification_settings );
	}

	/**
	 * Save integrations data.
	 *
	 * @since    1.0.0
	 * @param    array $data Integrations data.
	 * @return   bool        True if saved successfully.
	 */
	private function save_integrations_data( $data ) {
		$integration_settings = get_option( 'slbp_integrations_settings', array() );

		// Mailchimp
		$integration_settings['mailchimp'] = array(
			'enabled' => ! empty( $data['mailchimp_enabled'] ),
			'api_key' => sanitize_text_field( $data['mailchimp_api_key'] ?? '' ),
			'list_id' => sanitize_text_field( $data['mailchimp_list_id'] ?? '' ),
		);

		// Zapier
		$integration_settings['zapier'] = array(
			'enabled'     => ! empty( $data['zapier_enabled'] ),
			'webhook_url' => esc_url_raw( $data['zapier_webhook_url'] ?? '' ),
			'events'      => array( 'payment_success', 'subscription_created' ),
		);

		return update_option( 'slbp_integrations_settings', $integration_settings );
	}

	/**
	 * Complete the setup process.
	 *
	 * @since    1.0.0
	 * @return   bool True if completed successfully.
	 */
	private function complete_setup() {
		update_option( 'slbp_setup_completed', true );
		update_option( 'slbp_setup_completed_date', current_time( 'mysql' ) );
		
		// Clear any setup-related transients
		delete_transient( 'slbp_setup_wizard_redirect' );
		
		return true;
	}

	/**
	 * Test gateway connection.
	 *
	 * @since    1.0.0
	 * @param    string $gateway  Gateway ID.
	 * @param    array  $settings Gateway settings.
	 * @return   bool|WP_Error    True if successful, WP_Error on failure.
	 */
	private function test_gateway_connection( $gateway, $settings ) {
		if ( $gateway === 'lemon_squeezy' ) {
			$api_key = sanitize_text_field( $settings['api_key'] ?? '' );
			
			if ( empty( $api_key ) ) {
				return new WP_Error( 'no_api_key', __( 'API key is required.', 'skylearn-billing-pro' ) );
			}

			// Simple API test
			$response = wp_remote_get( 'https://api.lemonsqueezy.com/v1/ping', array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Accept'        => 'application/vnd.api+json',
				),
				'timeout' => 15,
			) );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$response_code = wp_remote_retrieve_response_code( $response );
			
			if ( $response_code === 200 ) {
				return true;
			} else {
				return new WP_Error( 'connection_failed', __( 'Connection test failed.', 'skylearn-billing-pro' ) );
			}
		}

		return new WP_Error( 'unsupported_gateway', __( 'Unsupported gateway.', 'skylearn-billing-pro' ) );
	}

	/**
	 * AJAX handler to dismiss setup notice.
	 *
	 * @since    1.0.0
	 */
	public function ajax_dismiss_setup_notice() {
		check_ajax_referer( 'slbp_dismiss_setup_notice', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', 'skylearn-billing-pro' ) );
		}

		update_option( 'slbp_setup_notice_dismissed', true );
		wp_send_json_success();
	}
}