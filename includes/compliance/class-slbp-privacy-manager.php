<?php
/**
 * The privacy management functionality of the plugin.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/compliance
 */

/**
 * The privacy management functionality of the plugin.
 *
 * Provides user-facing privacy controls, cookie consent management,
 * and privacy policy integration for GDPR/CCPA compliance.
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/compliance
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Privacy_Manager {

	/**
	 * The audit logger instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SLBP_Audit_Logger    $audit_logger    The audit logger instance.
	 */
	private $audit_logger;

	/**
	 * Privacy settings.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $settings    Privacy settings.
	 */
	private $settings;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->audit_logger = new SLBP_Audit_Logger();
		$this->settings = get_option( 'slbp_privacy_settings', $this->get_default_settings() );
		$this->init_hooks();
	}

	/**
	 * Initialize privacy hooks.
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		// Frontend hooks
		add_action( 'wp_head', array( $this, 'output_privacy_meta' ) );
		add_action( 'wp_footer', array( $this, 'output_cookie_consent_banner' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_privacy_scripts' ) );

		// AJAX handlers
		add_action( 'wp_ajax_slbp_update_cookie_consent', array( $this, 'ajax_update_cookie_consent' ) );
		add_action( 'wp_ajax_nopriv_slbp_update_cookie_consent', array( $this, 'ajax_update_cookie_consent' ) );
		add_action( 'wp_ajax_slbp_request_data_export', array( $this, 'ajax_request_data_export' ) );
		add_action( 'wp_ajax_slbp_request_data_deletion', array( $this, 'ajax_request_data_deletion' ) );

		// Shortcodes
		add_shortcode( 'slbp_privacy_dashboard', array( $this, 'privacy_dashboard_shortcode' ) );
		add_shortcode( 'slbp_cookie_preferences', array( $this, 'cookie_preferences_shortcode' ) );
		add_shortcode( 'slbp_data_request', array( $this, 'data_request_shortcode' ) );

		// User profile hooks
		add_action( 'show_user_profile', array( $this, 'show_privacy_profile_fields' ) );
		add_action( 'edit_user_profile', array( $this, 'show_privacy_profile_fields' ) );
		add_action( 'personal_options_update', array( $this, 'save_privacy_profile_fields' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_privacy_profile_fields' ) );

		// Admin hooks
		add_action( 'admin_menu', array( $this, 'add_privacy_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_privacy_settings' ) );
	}

	/**
	 * Get default privacy settings.
	 *
	 * @since    1.0.0
	 * @return   array    Default privacy settings.
	 */
	private function get_default_settings() {
		return array(
			'cookie_consent_enabled' => true,
			'cookie_consent_style' => 'banner',
			'cookie_consent_position' => 'bottom',
			'consent_categories' => array(
				'necessary' => array(
					'enabled' => true,
					'required' => true,
					'name' => __( 'Necessary', 'skylearn-billing-pro' ),
					'description' => __( 'Essential cookies for website functionality', 'skylearn-billing-pro' ),
				),
				'analytics' => array(
					'enabled' => false,
					'required' => false,
					'name' => __( 'Analytics', 'skylearn-billing-pro' ),
					'description' => __( 'Cookies for website analytics and performance', 'skylearn-billing-pro' ),
				),
				'marketing' => array(
					'enabled' => false,
					'required' => false,
					'name' => __( 'Marketing', 'skylearn-billing-pro' ),
					'description' => __( 'Cookies for marketing and advertising', 'skylearn-billing-pro' ),
				),
				'preferences' => array(
					'enabled' => false,
					'required' => false,
					'name' => __( 'Preferences', 'skylearn-billing-pro' ),
					'description' => __( 'Cookies that remember your preferences', 'skylearn-billing-pro' ),
				),
			),
			'data_retention_period' => 365, // days
			'privacy_policy_page' => 0,
			'auto_delete_guest_data' => true,
			'anonymize_ip_addresses' => true,
			'show_privacy_dashboard' => true,
		);
	}

	/**
	 * Output privacy-related meta tags.
	 *
	 * @since    1.0.0
	 */
	public function output_privacy_meta() {
		if ( ! $this->settings['cookie_consent_enabled'] ) {
			return;
		}
		?>
		<meta name="cookie-consent" content="required">
		<meta name="privacy-policy" content="<?php echo esc_url( get_privacy_policy_url() ); ?>">
		<?php
	}

	/**
	 * Enqueue privacy-related scripts and styles.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_privacy_scripts() {
		if ( ! $this->settings['cookie_consent_enabled'] ) {
			return;
		}

		wp_enqueue_script(
			'slbp-privacy',
			SLBP_PLUGIN_URL . 'public/js/slbp-privacy.js',
			array( 'jquery' ),
			SLBP_VERSION,
			true
		);

		wp_enqueue_style(
			'slbp-privacy',
			SLBP_PLUGIN_URL . 'public/css/slbp-privacy.css',
			array(),
			SLBP_VERSION
		);

		wp_localize_script( 'slbp-privacy', 'slbp_privacy', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'slbp_privacy_nonce' ),
			'settings' => $this->settings,
			'user_consent' => $this->get_user_consent(),
			'strings' => array(
				'accept_all' => __( 'Accept All', 'skylearn-billing-pro' ),
				'reject_all' => __( 'Reject All', 'skylearn-billing-pro' ),
				'save_preferences' => __( 'Save Preferences', 'skylearn-billing-pro' ),
				'manage_preferences' => __( 'Manage Preferences', 'skylearn-billing-pro' ),
				'privacy_policy' => __( 'Privacy Policy', 'skylearn-billing-pro' ),
			),
		) );
	}

	/**
	 * Output the cookie consent banner.
	 *
	 * @since    1.0.0
	 */
	public function output_cookie_consent_banner() {
		if ( ! $this->settings['cookie_consent_enabled'] ) {
			return;
		}

		$user_consent = $this->get_user_consent();
		
		// Don't show banner if user has already made a choice
		if ( ! empty( $user_consent['timestamp'] ) ) {
			return;
		}

		$position_class = 'bottom' === $this->settings['cookie_consent_position'] ? 'slbp-cookie-banner-bottom' : 'slbp-cookie-banner-top';
		$style_class = 'modal' === $this->settings['cookie_consent_style'] ? 'slbp-cookie-modal' : 'slbp-cookie-banner';
		?>
		<div id="slbp-cookie-consent" class="<?php echo esc_attr( $style_class . ' ' . $position_class ); ?>" style="display: none;">
			<div class="slbp-cookie-content">
				<div class="slbp-cookie-message">
					<h3><?php esc_html_e( 'Cookie Consent', 'skylearn-billing-pro' ); ?></h3>
					<p>
						<?php 
						printf(
							esc_html__( 'We use cookies to enhance your experience. By continuing to visit this site you agree to our use of cookies. %s', 'skylearn-billing-pro' ),
							'<a href="' . esc_url( get_privacy_policy_url() ) . '" target="_blank">' . esc_html__( 'Learn more', 'skylearn-billing-pro' ) . '</a>'
						);
						?>
					</p>
				</div>
				<div class="slbp-cookie-actions">
					<button type="button" id="slbp-accept-all-cookies" class="slbp-btn slbp-btn-primary">
						<?php esc_html_e( 'Accept All', 'skylearn-billing-pro' ); ?>
					</button>
					<button type="button" id="slbp-reject-all-cookies" class="slbp-btn slbp-btn-secondary">
						<?php esc_html_e( 'Reject All', 'skylearn-billing-pro' ); ?>
					</button>
					<button type="button" id="slbp-manage-cookies" class="slbp-btn slbp-btn-link">
						<?php esc_html_e( 'Manage Preferences', 'skylearn-billing-pro' ); ?>
					</button>
				</div>
			</div>
		</div>

		<div id="slbp-cookie-preferences-modal" class="slbp-modal" style="display: none;">
			<div class="slbp-modal-content">
				<div class="slbp-modal-header">
					<h3><?php esc_html_e( 'Cookie Preferences', 'skylearn-billing-pro' ); ?></h3>
					<button type="button" class="slbp-modal-close">&times;</button>
				</div>
				<div class="slbp-modal-body">
					<form id="slbp-cookie-preferences-form">
						<?php foreach ( $this->settings['consent_categories'] as $category => $config ) : ?>
						<div class="slbp-consent-category">
							<div class="slbp-consent-header">
								<label class="slbp-consent-label">
									<input 
										type="checkbox" 
										name="consent[<?php echo esc_attr( $category ); ?>]" 
										value="1" 
										<?php echo $config['required'] ? 'checked disabled' : ''; ?>
									>
									<span class="slbp-consent-name"><?php echo esc_html( $config['name'] ); ?></span>
									<?php if ( $config['required'] ) : ?>
									<span class="slbp-required"><?php esc_html_e( '(Required)', 'skylearn-billing-pro' ); ?></span>
									<?php endif; ?>
								</label>
							</div>
							<p class="slbp-consent-description"><?php echo esc_html( $config['description'] ); ?></p>
						</div>
						<?php endforeach; ?>
					</form>
				</div>
				<div class="slbp-modal-footer">
					<button type="button" id="slbp-save-cookie-preferences" class="slbp-btn slbp-btn-primary">
						<?php esc_html_e( 'Save Preferences', 'skylearn-billing-pro' ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Privacy dashboard shortcode.
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Shortcode attributes.
	 * @return   string            Privacy dashboard HTML.
	 */
	public function privacy_dashboard_shortcode( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'Please log in to access your privacy dashboard.', 'skylearn-billing-pro' ) . '</p>';
		}

		$user_id = get_current_user_id();
		$consent = $this->get_user_consent( $user_id );
		$compliance_manager = new SLBP_Compliance_Manager();

		ob_start();
		?>
		<div class="slbp-privacy-dashboard">
			<h3><?php esc_html_e( 'Privacy Dashboard', 'skylearn-billing-pro' ); ?></h3>
			
			<div class="slbp-privacy-section">
				<h4><?php esc_html_e( 'Cookie Preferences', 'skylearn-billing-pro' ); ?></h4>
				<p><?php esc_html_e( 'Manage your cookie and tracking preferences.', 'skylearn-billing-pro' ); ?></p>
				<?php echo $this->cookie_preferences_shortcode( array() ); ?>
			</div>

			<div class="slbp-privacy-section">
				<h4><?php esc_html_e( 'Data Requests', 'skylearn-billing-pro' ); ?></h4>
				<p><?php esc_html_e( 'Request a copy of your data or request data deletion.', 'skylearn-billing-pro' ); ?></p>
				<?php echo $this->data_request_shortcode( array() ); ?>
			</div>

			<div class="slbp-privacy-section">
				<h4><?php esc_html_e( 'Data Summary', 'skylearn-billing-pro' ); ?></h4>
				<ul class="slbp-data-summary">
					<li><?php printf( esc_html__( 'Account created: %s', 'skylearn-billing-pro' ), get_userdata( $user_id )->user_registered ); ?></li>
					<li><?php printf( esc_html__( 'Last consent update: %s', 'skylearn-billing-pro' ), $consent['timestamp'] ?? esc_html__( 'Never', 'skylearn-billing-pro' ) ); ?></li>
					<li><a href="<?php echo esc_url( get_privacy_policy_url() ); ?>" target="_blank"><?php esc_html_e( 'Privacy Policy', 'skylearn-billing-pro' ); ?></a></li>
				</ul>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Cookie preferences shortcode.
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Shortcode attributes.
	 * @return   string            Cookie preferences form HTML.
	 */
	public function cookie_preferences_shortcode( $atts ) {
		$user_consent = $this->get_user_consent();

		ob_start();
		?>
		<form id="slbp-user-cookie-preferences" class="slbp-cookie-form">
			<?php wp_nonce_field( 'slbp_privacy_nonce', 'slbp_privacy_nonce' ); ?>
			<?php foreach ( $this->settings['consent_categories'] as $category => $config ) : ?>
			<div class="slbp-form-group">
				<label class="slbp-checkbox-label">
					<input 
						type="checkbox" 
						name="consent[<?php echo esc_attr( $category ); ?>]" 
						value="1" 
						<?php checked( ! empty( $user_consent[$category] ) ); ?>
						<?php echo $config['required'] ? 'disabled' : ''; ?>
					>
					<span class="slbp-checkbox-text">
						<strong><?php echo esc_html( $config['name'] ); ?></strong>
						<?php if ( $config['required'] ) : ?>
						<span class="slbp-required"><?php esc_html_e( '(Required)', 'skylearn-billing-pro' ); ?></span>
						<?php endif; ?>
						<br>
						<small><?php echo esc_html( $config['description'] ); ?></small>
					</span>
				</label>
			</div>
			<?php endforeach; ?>
			<button type="submit" class="slbp-btn slbp-btn-primary">
				<?php esc_html_e( 'Update Preferences', 'skylearn-billing-pro' ); ?>
			</button>
		</form>
		<?php
		return ob_get_clean();
	}

	/**
	 * Data request shortcode.
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Shortcode attributes.
	 * @return   string            Data request form HTML.
	 */
	public function data_request_shortcode( $atts ) {
		ob_start();
		?>
		<div class="slbp-data-requests">
			<div class="slbp-request-section">
				<h5><?php esc_html_e( 'Export Your Data', 'skylearn-billing-pro' ); ?></h5>
				<p><?php esc_html_e( 'Download a copy of all data we have about you.', 'skylearn-billing-pro' ); ?></p>
				<button type="button" id="slbp-request-export" class="slbp-btn slbp-btn-secondary">
					<?php esc_html_e( 'Request Data Export', 'skylearn-billing-pro' ); ?>
				</button>
			</div>
			
			<div class="slbp-request-section">
				<h5><?php esc_html_e( 'Delete Your Data', 'skylearn-billing-pro' ); ?></h5>
				<p><?php esc_html_e( 'Request deletion of your personal data (subject to legal requirements).', 'skylearn-billing-pro' ); ?></p>
				<button type="button" id="slbp-request-deletion" class="slbp-btn slbp-btn-danger">
					<?php esc_html_e( 'Request Data Deletion', 'skylearn-billing-pro' ); ?>
				</button>
			</div>
		</div>

		<div id="slbp-data-request-modal" class="slbp-modal" style="display: none;">
			<div class="slbp-modal-content">
				<div class="slbp-modal-header">
					<h3 id="slbp-data-request-title"></h3>
					<button type="button" class="slbp-modal-close">&times;</button>
				</div>
				<div class="slbp-modal-body">
					<form id="slbp-data-request-form">
						<?php wp_nonce_field( 'slbp_privacy_nonce', 'slbp_privacy_nonce' ); ?>
						<input type="hidden" id="slbp-request-type" name="request_type" value="">
						<div class="slbp-form-group">
							<label for="slbp-request-reason"><?php esc_html_e( 'Reason for request (optional):', 'skylearn-billing-pro' ); ?></label>
							<textarea id="slbp-request-reason" name="reason" rows="3" placeholder="<?php esc_attr_e( 'Please provide any additional details...', 'skylearn-billing-pro' ); ?>"></textarea>
						</div>
					</form>
				</div>
				<div class="slbp-modal-footer">
					<button type="button" id="slbp-submit-data-request" class="slbp-btn slbp-btn-primary">
						<?php esc_html_e( 'Submit Request', 'skylearn-billing-pro' ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * AJAX handler for updating cookie consent.
	 *
	 * @since    1.0.0
	 */
	public function ajax_update_cookie_consent() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'slbp_privacy_nonce' ) ) {
			wp_die( 'Security check failed.' );
		}

		$consent_data = isset( $_POST['consent'] ) ? array_map( 'sanitize_text_field', $_POST['consent'] ) : array();
		$user_id = get_current_user_id();

		// For non-logged-in users, store in a cookie
		if ( ! $user_id ) {
			$cookie_value = wp_json_encode( array(
				'consent' => $consent_data,
				'timestamp' => current_time( 'mysql' ),
			) );
			setcookie( 'slbp_cookie_consent', $cookie_value, time() + ( 365 * 24 * 60 * 60 ), '/' );
		} else {
			// For logged-in users, store in user meta
			$compliance_manager = new SLBP_Compliance_Manager();
			$compliance_manager->update_user_consent( $user_id, $consent_data );
		}

		// Log the consent update
		$this->audit_logger->log_event(
			'privacy',
			'cookie_consent_updated',
			$user_id,
			array(
				'consent_data' => $consent_data,
				'user_ip' => $this->get_user_ip(),
			),
			'info'
		);

		wp_send_json_success( array(
			'message' => __( 'Cookie preferences updated successfully.', 'skylearn-billing-pro' ),
		) );
	}

	/**
	 * Get user consent preferences.
	 *
	 * @since    1.0.0
	 * @param    int    $user_id    User ID (optional).
	 * @return   array              Consent preferences.
	 */
	public function get_user_consent( $user_id = null ) {
		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( $user_id ) {
			// Get consent from user meta
			$compliance_manager = new SLBP_Compliance_Manager();
			return $compliance_manager->get_user_consent( $user_id );
		} else {
			// Get consent from cookie for non-logged-in users
			if ( isset( $_COOKIE['slbp_cookie_consent'] ) ) {
				$cookie_data = json_decode( stripslashes( $_COOKIE['slbp_cookie_consent'] ), true );
				return $cookie_data['consent'] ?? array();
			}
		}

		return array();
	}

	/**
	 * Check if user has consented to a specific category.
	 *
	 * @since    1.0.0
	 * @param    string    $category    Consent category.
	 * @param    int       $user_id    User ID (optional).
	 * @return   bool                  Whether user has consented.
	 */
	public function has_consent( $category, $user_id = null ) {
		$consent = $this->get_user_consent( $user_id );
		
		// Always allow necessary cookies
		if ( 'necessary' === $category ) {
			return true;
		}

		return ! empty( $consent[$category] );
	}

	/**
	 * Show privacy profile fields.
	 *
	 * @since    1.0.0
	 * @param    WP_User    $user    User object.
	 */
	public function show_privacy_profile_fields( $user ) {
		if ( ! current_user_can( 'edit_user', $user->ID ) ) {
			return;
		}

		$consent = $this->get_user_consent( $user->ID );
		?>
		<h3><?php esc_html_e( 'Privacy Preferences', 'skylearn-billing-pro' ); ?></h3>
		<table class="form-table">
			<tr>
				<th><label><?php esc_html_e( 'Cookie Consent', 'skylearn-billing-pro' ); ?></label></th>
				<td>
					<?php if ( ! empty( $consent['timestamp'] ) ) : ?>
						<p><?php printf( esc_html__( 'Last updated: %s', 'skylearn-billing-pro' ), $consent['timestamp'] ); ?></p>
						<p><a href="#" onclick="window.open('<?php echo esc_url( add_query_arg( 'slbp_privacy_dashboard', '1', home_url() ) ); ?>', '_blank')"><?php esc_html_e( 'Manage Privacy Settings', 'skylearn-billing-pro' ); ?></a></p>
					<?php else : ?>
						<p><?php esc_html_e( 'No consent recorded yet.', 'skylearn-billing-pro' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save privacy profile fields.
	 *
	 * @since    1.0.0
	 * @param    int    $user_id    User ID.
	 */
	public function save_privacy_profile_fields( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		// Privacy settings are managed through the frontend privacy dashboard
		// This is just a placeholder for any admin-specific privacy settings
	}

	/**
	 * Add privacy admin menu.
	 *
	 * @since    1.0.0
	 */
	public function add_privacy_admin_menu() {
		add_submenu_page(
			'slbp-admin',
			__( 'Privacy Settings', 'skylearn-billing-pro' ),
			__( 'Privacy', 'skylearn-billing-pro' ),
			'manage_options',
			'slbp-privacy',
			array( $this, 'privacy_admin_page' )
		);
	}

	/**
	 * Privacy admin page.
	 *
	 * @since    1.0.0
	 */
	public function privacy_admin_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Privacy Settings', 'skylearn-billing-pro' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'slbp_privacy_settings' );
				do_settings_sections( 'slbp_privacy_settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register privacy settings.
	 *
	 * @since    1.0.0
	 */
	public function register_privacy_settings() {
		register_setting( 'slbp_privacy_settings', 'slbp_privacy_settings', array( $this, 'sanitize_privacy_settings' ) );

		add_settings_section(
			'slbp_cookie_consent',
			__( 'Cookie Consent', 'skylearn-billing-pro' ),
			array( $this, 'cookie_consent_section_callback' ),
			'slbp_privacy_settings'
		);

		add_settings_field(
			'cookie_consent_enabled',
			__( 'Enable Cookie Consent', 'skylearn-billing-pro' ),
			array( $this, 'checkbox_field_callback' ),
			'slbp_privacy_settings',
			'slbp_cookie_consent',
			array( 'field' => 'cookie_consent_enabled' )
		);

		add_settings_field(
			'cookie_consent_style',
			__( 'Consent Style', 'skylearn-billing-pro' ),
			array( $this, 'select_field_callback' ),
			'slbp_privacy_settings',
			'slbp_cookie_consent',
			array( 
				'field' => 'cookie_consent_style',
				'options' => array(
					'banner' => __( 'Banner', 'skylearn-billing-pro' ),
					'modal' => __( 'Modal', 'skylearn-billing-pro' ),
				),
			)
		);
	}

	/**
	 * Sanitize privacy settings.
	 *
	 * @since    1.0.0
	 * @param    array    $input    Raw input data.
	 * @return   array             Sanitized settings.
	 */
	public function sanitize_privacy_settings( $input ) {
		$sanitized = array();

		$sanitized['cookie_consent_enabled'] = ! empty( $input['cookie_consent_enabled'] );
		$sanitized['cookie_consent_style'] = in_array( $input['cookie_consent_style'], array( 'banner', 'modal' ), true ) ? $input['cookie_consent_style'] : 'banner';
		$sanitized['cookie_consent_position'] = in_array( $input['cookie_consent_position'], array( 'top', 'bottom' ), true ) ? $input['cookie_consent_position'] : 'bottom';

		return array_merge( $this->settings, $sanitized );
	}

	/**
	 * Cookie consent section callback.
	 *
	 * @since    1.0.0
	 */
	public function cookie_consent_section_callback() {
		echo '<p>' . esc_html__( 'Configure cookie consent banner and preferences.', 'skylearn-billing-pro' ) . '</p>';
	}

	/**
	 * Checkbox field callback.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Field arguments.
	 */
	public function checkbox_field_callback( $args ) {
		$field = $args['field'];
		$value = $this->settings[$field] ?? false;
		?>
		<input type="checkbox" name="slbp_privacy_settings[<?php echo esc_attr( $field ); ?>]" value="1" <?php checked( $value ); ?> />
		<?php
	}

	/**
	 * Select field callback.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Field arguments.
	 */
	public function select_field_callback( $args ) {
		$field = $args['field'];
		$options = $args['options'];
		$value = $this->settings[$field] ?? '';
		?>
		<select name="slbp_privacy_settings[<?php echo esc_attr( $field ); ?>]">
			<?php foreach ( $options as $option_value => $option_label ) : ?>
			<option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $value, $option_value ); ?>>
				<?php echo esc_html( $option_label ); ?>
			</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Get the user's IP address.
	 *
	 * @since    1.0.0
	 * @return   string    User IP address.
	 */
	private function get_user_ip() {
		$ip_keys = array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );

		foreach ( $ip_keys as $key ) {
			if ( array_key_exists( $key, $_SERVER ) === true ) {
				$ip = $_SERVER[ $key ];
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = explode( ',', $ip )[0];
				}
				$ip = trim( $ip );
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) !== false ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}
}