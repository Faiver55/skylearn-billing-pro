<?php
/**
 * In-App Help System
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/training
 */

/**
 * In-App Help System Class
 *
 * Provides context-sensitive help throughout the admin interface.
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/training
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_In_App_Help {

	/**
	 * Help content for different pages and contexts.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $help_content    Contextual help content.
	 */
	private $help_content;

	/**
	 * Initialize the in-app help system.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->init_help_content();
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_help_assets' ) );
		add_action( 'wp_ajax_slbp_get_help_content', array( $this, 'ajax_get_help_content' ) );
		add_action( 'admin_footer', array( $this, 'render_help_modals' ) );
		add_filter( 'admin_body_class', array( $this, 'add_admin_body_class' ) );
	}

	/**
	 * Initialize help content for different contexts.
	 *
	 * @since    1.0.0
	 */
	private function init_help_content() {
		$this->help_content = array(
			'dashboard' => array(
				'page_title' => __( 'Dashboard Help', 'skylearn-billing-pro' ),
				'sections' => array(
					'overview' => array(
						'title' => __( 'Dashboard Overview', 'skylearn-billing-pro' ),
						'content' => __( 'The dashboard provides a quick overview of your billing system performance, including revenue metrics, active subscriptions, and recent activity.', 'skylearn-billing-pro' ),
					),
					'stats_cards' => array(
						'title' => __( 'Statistics Cards', 'skylearn-billing-pro' ),
						'content' => __( 'The colored cards at the top show key metrics: Total Revenue, Active Students, Subscriptions, and Course Enrollments. Click any card to view detailed information.', 'skylearn-billing-pro' ),
					),
					'recent_activity' => array(
						'title' => __( 'Recent Activity', 'skylearn-billing-pro' ),
						'content' => __( 'This section shows the latest transactions, enrollments, and system events. Use this to monitor real-time activity in your billing system.', 'skylearn-billing-pro' ),
					),
				),
			),
			'settings' => array(
				'page_title' => __( 'Settings Help', 'skylearn-billing-pro' ),
				'sections' => array(
					'payment_gateways' => array(
						'title' => __( 'Payment Gateway Configuration', 'skylearn-billing-pro' ),
						'content' => __( 'Configure your payment processors here. For Lemon Squeezy, you\'ll need your API key and Store ID from your Lemon Squeezy dashboard.', 'skylearn-billing-pro' ),
					),
					'lms_integration' => array(
						'title' => __( 'LMS Integration Settings', 'skylearn-billing-pro' ),
						'content' => __( 'Enable LearnDash integration to automatically enroll students in courses upon successful payment. Make sure LearnDash is installed and activated.', 'skylearn-billing-pro' ),
					),
					'product_mapping' => array(
						'title' => __( 'Product to Course Mapping', 'skylearn-billing-pro' ),
						'content' => __( 'Create connections between your payment products and LearnDash courses. Each product can grant access to one or more courses.', 'skylearn-billing-pro' ),
					),
				),
			),
			'analytics' => array(
				'page_title' => __( 'Analytics Help', 'skylearn-billing-pro' ),
				'sections' => array(
					'revenue_charts' => array(
						'title' => __( 'Revenue Analytics', 'skylearn-billing-pro' ),
						'content' => __( 'Track your revenue over time with interactive charts. Filter by date range, payment method, or course to get detailed insights.', 'skylearn-billing-pro' ),
					),
					'subscription_metrics' => array(
						'title' => __( 'Subscription Metrics', 'skylearn-billing-pro' ),
						'content' => __( 'Monitor subscription health with metrics like churn rate, lifetime value, and renewal rates. Use this data to optimize your pricing strategy.', 'skylearn-billing-pro' ),
					),
				),
			),
			'integrations' => array(
				'page_title' => __( 'Integrations Help', 'skylearn-billing-pro' ),
				'sections' => array(
					'webhooks' => array(
						'title' => __( 'Webhook Configuration', 'skylearn-billing-pro' ),
						'content' => __( 'Webhooks allow real-time communication between your payment processor and WordPress. Set up webhook URLs in your payment gateway dashboard.', 'skylearn-billing-pro' ),
					),
					'api_keys' => array(
						'title' => __( 'API Key Management', 'skylearn-billing-pro' ),
						'content' => __( 'Generate and manage API keys for external integrations. Keep your keys secure and regenerate them regularly for security.', 'skylearn-billing-pro' ),
					),
				),
			),
		);
	}

	/**
	 * Enqueue help system assets.
	 *
	 * @since    1.0.0
	 * @param    string    $hook    The current admin page hook.
	 */
	public function enqueue_help_assets( $hook ) {
		// Only load on SkyLearn Billing Pro pages
		if ( strpos( $hook, 'skylearn-billing' ) === false && strpos( $hook, 'slbp-' ) === false ) {
			return;
		}

		wp_enqueue_script(
			'slbp-in-app-help',
			SLBP_PLUGIN_URL . 'admin/js/in-app-help.js',
			array( 'jquery' ),
			SLBP_VERSION,
			true
		);

		wp_enqueue_style(
			'slbp-in-app-help',
			SLBP_PLUGIN_URL . 'admin/css/in-app-help.css',
			array(),
			SLBP_VERSION
		);

		wp_localize_script(
			'slbp-in-app-help',
			'slbpHelpAjax',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'slbp_help_nonce' ),
				'strings' => array(
					'loading' => __( 'Loading help content...', 'skylearn-billing-pro' ),
					'error' => __( 'Error loading help content. Please try again.', 'skylearn-billing-pro' ),
					'close' => __( 'Close', 'skylearn-billing-pro' ),
				),
			)
		);
	}

	/**
	 * AJAX handler for getting help content.
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_help_content() {
		check_ajax_referer( 'slbp_help_nonce', 'nonce' );

		$context = sanitize_text_field( $_POST['context'] ?? '' );
		$section = sanitize_text_field( $_POST['section'] ?? '' );

		if ( empty( $context ) ) {
			wp_send_json_error( __( 'Invalid context provided.', 'skylearn-billing-pro' ) );
		}

		$help_data = $this->get_help_content( $context, $section );

		if ( $help_data ) {
			wp_send_json_success( $help_data );
		} else {
			wp_send_json_error( __( 'Help content not found.', 'skylearn-billing-pro' ) );
		}
	}

	/**
	 * Get help content for a specific context.
	 *
	 * @since    1.0.0
	 * @param    string    $context    Help context.
	 * @param    string    $section    Specific section (optional).
	 * @return   array|null           Help content or null if not found.
	 */
	public function get_help_content( $context, $section = null ) {
		if ( ! isset( $this->help_content[ $context ] ) ) {
			return null;
		}

		$content = $this->help_content[ $context ];

		if ( $section && isset( $content['sections'][ $section ] ) ) {
			return array(
				'title' => $content['sections'][ $section ]['title'],
				'content' => $content['sections'][ $section ]['content'],
			);
		}

		return $content;
	}

	/**
	 * Render help button for a specific context.
	 *
	 * @since    1.0.0
	 * @param    string    $context    Help context.
	 * @param    string    $section    Specific section (optional).
	 * @param    array     $args       Additional arguments.
	 * @return   string               Help button HTML.
	 */
	public function render_help_button( $context, $section = null, $args = array() ) {
		$defaults = array(
			'text' => __( 'Help', 'skylearn-billing-pro' ),
			'icon' => 'editor-help',
			'class' => 'slbp-help-button',
			'position' => 'inline', // inline, floating, tooltip
		);

		$args = wp_parse_args( $args, $defaults );

		$data_attrs = array(
			'data-context="' . esc_attr( $context ) . '"',
		);

		if ( $section ) {
			$data_attrs[] = 'data-section="' . esc_attr( $section ) . '"';
		}

		$button_html = sprintf(
			'<button type="button" class="%s" %s title="%s">
				<span class="dashicons dashicons-%s" aria-hidden="true"></span>
				<span class="slbp-help-text">%s</span>
			</button>',
			esc_attr( $args['class'] ),
			implode( ' ', $data_attrs ),
			esc_attr( $args['text'] ),
			esc_attr( $args['icon'] ),
			esc_html( $args['text'] )
		);

		return $button_html;
	}

	/**
	 * Render help tooltip for form fields.
	 *
	 * @since    1.0.0
	 * @param    string    $content    Tooltip content.
	 * @param    array     $args       Additional arguments.
	 * @return   string               Tooltip HTML.
	 */
	public function render_help_tooltip( $content, $args = array() ) {
		$defaults = array(
			'icon' => 'editor-help',
			'position' => 'top',
		);

		$args = wp_parse_args( $args, $defaults );

		$tooltip_html = sprintf(
			'<span class="slbp-help-tooltip" data-tooltip="%s" data-position="%s">
				<span class="dashicons dashicons-%s" aria-hidden="true"></span>
			</span>',
			esc_attr( $content ),
			esc_attr( $args['position'] ),
			esc_attr( $args['icon'] )
		);

		return $tooltip_html;
	}

	/**
	 * Render help modals in admin footer.
	 *
	 * @since    1.0.0
	 */
	public function render_help_modals() {
		// Only render on SkyLearn Billing Pro pages
		$screen = get_current_screen();
		if ( ! $screen || ( strpos( $screen->id, 'skylearn-billing' ) === false && strpos( $screen->id, 'slbp-' ) === false ) ) {
			return;
		}

		?>
		<div id="slbp-help-modal" class="slbp-modal" style="display: none;">
			<div class="slbp-modal-overlay"></div>
			<div class="slbp-modal-content">
				<div class="slbp-modal-header">
					<h2 id="slbp-help-modal-title"><?php esc_html_e( 'Help', 'skylearn-billing-pro' ); ?></h2>
					<button type="button" class="slbp-modal-close" aria-label="<?php esc_attr_e( 'Close', 'skylearn-billing-pro' ); ?>">
						<span class="dashicons dashicons-no-alt"></span>
					</button>
				</div>
				<div class="slbp-modal-body">
					<div id="slbp-help-modal-content">
						<div class="slbp-loading">
							<span class="dashicons dashicons-update-alt"></span>
							<?php esc_html_e( 'Loading help content...', 'skylearn-billing-pro' ); ?>
						</div>
					</div>
				</div>
				<div class="slbp-modal-footer">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=slbp-help' ) ); ?>" class="button">
						<?php esc_html_e( 'View Full Documentation', 'skylearn-billing-pro' ); ?>
					</a>
					<button type="button" class="button button-primary slbp-modal-close">
						<?php esc_html_e( 'Close', 'skylearn-billing-pro' ); ?>
					</button>
				</div>
			</div>
		</div>

		<div id="slbp-feedback-modal" class="slbp-modal" style="display: none;">
			<div class="slbp-modal-overlay"></div>
			<div class="slbp-modal-content">
				<div class="slbp-modal-header">
					<h2><?php esc_html_e( 'Help Feedback', 'skylearn-billing-pro' ); ?></h2>
					<button type="button" class="slbp-modal-close" aria-label="<?php esc_attr_e( 'Close', 'skylearn-billing-pro' ); ?>">
						<span class="dashicons dashicons-no-alt"></span>
					</button>
				</div>
				<div class="slbp-modal-body">
					<form id="slbp-feedback-form">
						<p><?php esc_html_e( 'Was this help content useful? Let us know how we can improve:', 'skylearn-billing-pro' ); ?></p>
						
						<div class="slbp-form-field">
							<label for="slbp-feedback-rating"><?php esc_html_e( 'Rating:', 'skylearn-billing-pro' ); ?></label>
							<select id="slbp-feedback-rating" name="rating" required>
								<option value=""><?php esc_html_e( 'Select rating...', 'skylearn-billing-pro' ); ?></option>
								<option value="5"><?php esc_html_e( '5 - Very helpful', 'skylearn-billing-pro' ); ?></option>
								<option value="4"><?php esc_html_e( '4 - Helpful', 'skylearn-billing-pro' ); ?></option>
								<option value="3"><?php esc_html_e( '3 - Somewhat helpful', 'skylearn-billing-pro' ); ?></option>
								<option value="2"><?php esc_html_e( '2 - Not very helpful', 'skylearn-billing-pro' ); ?></option>
								<option value="1"><?php esc_html_e( '1 - Not helpful at all', 'skylearn-billing-pro' ); ?></option>
							</select>
						</div>
						
						<div class="slbp-form-field">
							<label for="slbp-feedback-comments"><?php esc_html_e( 'Comments (optional):', 'skylearn-billing-pro' ); ?></label>
							<textarea id="slbp-feedback-comments" name="comments" rows="4" placeholder="<?php esc_attr_e( 'How can we improve this help content?', 'skylearn-billing-pro' ); ?>"></textarea>
						</div>
						
						<div class="slbp-form-field">
							<label>
								<input type="checkbox" name="contact_me" value="1">
								<?php esc_html_e( 'Contact me about this feedback', 'skylearn-billing-pro' ); ?>
							</label>
						</div>
					</form>
				</div>
				<div class="slbp-modal-footer">
					<button type="button" class="button slbp-modal-close">
						<?php esc_html_e( 'Cancel', 'skylearn-billing-pro' ); ?>
					</button>
					<button type="submit" form="slbp-feedback-form" class="button button-primary">
						<?php esc_html_e( 'Submit Feedback', 'skylearn-billing-pro' ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Add body class for help system styling.
	 *
	 * @since    1.0.0
	 * @param    string    $classes    Existing body classes.
	 * @return   string               Modified body classes.
	 */
	public function add_admin_body_class( $classes ) {
		$screen = get_current_screen();
		if ( $screen && ( strpos( $screen->id, 'skylearn-billing' ) !== false || strpos( $screen->id, 'slbp-' ) !== false ) ) {
			$classes .= ' slbp-admin-page';
		}
		return $classes;
	}

	/**
	 * Get contextual help for current page.
	 *
	 * @since    1.0.0
	 * @return   string    Current page context for help.
	 */
	public function get_current_context() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return '';
		}

		// Map screen IDs to help contexts
		$context_map = array(
			'toplevel_page_skylearn-billing-pro' => 'dashboard',
			'skylearn-billing_page_slbp-settings' => 'settings',
			'skylearn-billing_page_slbp-analytics' => 'analytics',
			'skylearn-billing_page_slbp-integrations' => 'integrations',
		);

		return $context_map[ $screen->id ] ?? '';
	}
}