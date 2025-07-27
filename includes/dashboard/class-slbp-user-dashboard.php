<?php
/**
 * The user dashboard manager for front-end user interfaces.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/dashboard
 */

/**
 * The user dashboard manager class.
 *
 * Handles front-end user dashboard functionality including subscriptions,
 * invoices, payment history, course enrollments, and preferences.
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/dashboard
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_User_Dashboard {

	/**
	 * Current user ID.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      int    $user_id    Current user ID.
	 */
	private $user_id;

	/**
	 * Initialize the user dashboard.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->user_id = get_current_user_id();
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		// Shortcode for user dashboard
		add_shortcode( 'slbp_user_dashboard', array( $this, 'render_dashboard_shortcode' ) );
		
		// AJAX handlers for dashboard
		add_action( 'wp_ajax_slbp_get_user_subscriptions', array( $this, 'ajax_get_user_subscriptions' ) );
		add_action( 'wp_ajax_slbp_get_user_transactions', array( $this, 'ajax_get_user_transactions' ) );
		add_action( 'wp_ajax_slbp_get_user_enrollments', array( $this, 'ajax_get_user_enrollments' ) );
		add_action( 'wp_ajax_slbp_download_invoice', array( $this, 'ajax_download_invoice' ) );
		add_action( 'wp_ajax_slbp_cancel_subscription', array( $this, 'ajax_cancel_subscription' ) );
		
		// Enqueue scripts and styles
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_dashboard_assets' ) );
		
		// Add dashboard page to user account
		add_action( 'init', array( $this, 'add_dashboard_endpoint' ) );
		add_filter( 'query_vars', array( $this, 'add_dashboard_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'handle_dashboard_template' ) );
	}

	/**
	 * Add dashboard endpoint for user account.
	 *
	 * @since    1.0.0
	 */
	public function add_dashboard_endpoint() {
		add_rewrite_endpoint( 'billing-dashboard', EP_PAGES );
	}

	/**
	 * Add dashboard query vars.
	 *
	 * @since    1.0.0
	 * @param    array $vars Existing query vars.
	 * @return   array       Modified query vars.
	 */
	public function add_dashboard_query_vars( $vars ) {
		$vars[] = 'billing-dashboard';
		return $vars;
	}

	/**
	 * Handle dashboard template.
	 *
	 * @since    1.0.0
	 */
	public function handle_dashboard_template() {
		global $wp_query;
		
		if ( isset( $wp_query->query_vars['billing-dashboard'] ) ) {
			if ( ! is_user_logged_in() ) {
				wp_redirect( wp_login_url( get_permalink() ) );
				exit;
			}
			
			// Load dashboard template
			$this->load_dashboard_template();
			exit;
		}
	}

	/**
	 * Load dashboard template.
	 *
	 * @since    1.0.0
	 */
	private function load_dashboard_template() {
		// Try to load custom template first
		$template = locate_template( array( 'slbp-user-dashboard.php' ) );
		
		if ( ! $template ) {
			$template = SLBP_PLUGIN_PATH . 'public/partials/user-dashboard.php';
		}
		
		include $template;
	}

	/**
	 * Enqueue dashboard assets.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_dashboard_assets() {
		// Only enqueue on pages that need dashboard
		if ( is_user_logged_in() && ( is_page() || has_shortcode( get_post()->post_content ?? '', 'slbp_user_dashboard' ) ) ) {
			wp_enqueue_style( 
				'slbp-user-dashboard', 
				SLBP_PLUGIN_URL . 'public/css/user-dashboard.css', 
				array(), 
				SLBP_VERSION 
			);

			wp_enqueue_script( 
				'slbp-user-dashboard', 
				SLBP_PLUGIN_URL . 'public/js/user-dashboard.js', 
				array( 'jquery' ), 
				SLBP_VERSION, 
				true 
			);

			wp_localize_script( 'slbp-user-dashboard', 'slbp_dashboard', array(
				'ajax_url'    => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'slbp_dashboard_nonce' ),
				'current_user_id' => $this->user_id,
				'strings'     => array(
					'loading'         => __( 'Loading...', 'skylearn-billing-pro' ),
					'error'           => __( 'An error occurred', 'skylearn-billing-pro' ),
					'confirm_cancel'  => __( 'Are you sure you want to cancel this subscription?', 'skylearn-billing-pro' ),
					'download_error'  => __( 'Failed to download invoice', 'skylearn-billing-pro' ),
				),
			) );
		}
	}

	/**
	 * Render dashboard shortcode.
	 *
	 * @since    1.0.0
	 * @param    array $atts Shortcode attributes.
	 * @return   string      Dashboard HTML.
	 */
	public function render_dashboard_shortcode( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '<p>' . sprintf( 
				__( 'Please <a href="%s">log in</a> to view your billing dashboard.', 'skylearn-billing-pro' ),
				wp_login_url( get_permalink() )
			) . '</p>';
		}

		$atts = shortcode_atts( array(
			'default_tab' => 'overview',
			'show_tabs'   => 'overview,subscriptions,transactions,courses,preferences',
		), $atts );

		ob_start();
		$this->render_dashboard( $atts );
		return ob_get_clean();
	}

	/**
	 * Render the user dashboard.
	 *
	 * @since    1.0.0
	 * @param    array $args Dashboard arguments.
	 */
	public function render_dashboard( $args = array() ) {
		$default_args = array(
			'default_tab' => 'overview',
			'show_tabs'   => 'overview,subscriptions,transactions,courses,preferences',
		);
		
		$args = wp_parse_args( $args, $default_args );
		$available_tabs = explode( ',', $args['show_tabs'] );
		$current_tab = $_GET['tab'] ?? $args['default_tab'];
		
		if ( ! in_array( $current_tab, $available_tabs ) ) {
			$current_tab = $args['default_tab'];
		}

		$user = get_user_by( 'id', $this->user_id );
		?>
		<div class="slbp-user-dashboard">
			<div class="dashboard-header">
				<h2><?php esc_html_e( 'My Billing Dashboard', 'skylearn-billing-pro' ); ?></h2>
				<p class="dashboard-welcome">
					<?php printf( esc_html__( 'Welcome back, %s!', 'skylearn-billing-pro' ), esc_html( $user->display_name ) ); ?>
				</p>
			</div>

			<nav class="dashboard-nav">
				<?php foreach ( $available_tabs as $tab_id ) : ?>
					<?php $tab_config = $this->get_tab_config( $tab_id ); ?>
					<?php if ( $tab_config ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'tab', $tab_id ) ); ?>" 
						   class="nav-tab <?php echo $current_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
							<span class="tab-icon"><?php echo $tab_config['icon']; ?></span>
							<?php echo esc_html( $tab_config['title'] ); ?>
						</a>
					<?php endif; ?>
				<?php endforeach; ?>
			</nav>

			<div class="dashboard-content">
				<?php
				switch ( $current_tab ) {
					case 'subscriptions':
						$this->render_subscriptions_tab();
						break;
					case 'transactions':
						$this->render_transactions_tab();
						break;
					case 'courses':
						$this->render_courses_tab();
						break;
					case 'preferences':
						$this->render_preferences_tab();
						break;
					default:
						$this->render_overview_tab();
						break;
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Get tab configuration.
	 *
	 * @since    1.0.0
	 * @param    string $tab_id Tab ID.
	 * @return   array|false    Tab configuration or false.
	 */
	private function get_tab_config( $tab_id ) {
		$tabs = array(
			'overview' => array(
				'title' => __( 'Overview', 'skylearn-billing-pro' ),
				'icon'  => '📊',
			),
			'subscriptions' => array(
				'title' => __( 'Subscriptions', 'skylearn-billing-pro' ),
				'icon'  => '🔄',
			),
			'transactions' => array(
				'title' => __( 'Transactions', 'skylearn-billing-pro' ),
				'icon'  => '💳',
			),
			'courses' => array(
				'title' => __( 'My Courses', 'skylearn-billing-pro' ),
				'icon'  => '📚',
			),
			'preferences' => array(
				'title' => __( 'Preferences', 'skylearn-billing-pro' ),
				'icon'  => '⚙️',
			),
		);

		return $tabs[ $tab_id ] ?? false;
	}

	/**
	 * Render overview tab.
	 *
	 * @since    1.0.0
	 */
	private function render_overview_tab() {
		$stats = $this->get_user_stats();
		?>
		<div class="dashboard-overview">
			<div class="stats-grid">
				<div class="stat-card">
					<div class="stat-number"><?php echo esc_html( $stats['active_subscriptions'] ); ?></div>
					<div class="stat-label"><?php esc_html_e( 'Active Subscriptions', 'skylearn-billing-pro' ); ?></div>
				</div>
				<div class="stat-card">
					<div class="stat-number"><?php echo esc_html( $stats['total_courses'] ); ?></div>
					<div class="stat-label"><?php esc_html_e( 'Enrolled Courses', 'skylearn-billing-pro' ); ?></div>
				</div>
				<div class="stat-card">
					<div class="stat-number"><?php echo esc_html( $stats['total_spent'] ); ?></div>
					<div class="stat-label"><?php esc_html_e( 'Total Spent', 'skylearn-billing-pro' ); ?></div>
				</div>
				<div class="stat-card">
					<div class="stat-number"><?php echo esc_html( $stats['last_payment_date'] ); ?></div>
					<div class="stat-label"><?php esc_html_e( 'Last Payment', 'skylearn-billing-pro' ); ?></div>
				</div>
			</div>

			<div class="recent-activity">
				<h3><?php esc_html_e( 'Recent Activity', 'skylearn-billing-pro' ); ?></h3>
				<div class="activity-list" id="recent-activity-list">
					<?php $this->render_recent_activity(); ?>
				</div>
			</div>

			<div class="quick-actions">
				<h3><?php esc_html_e( 'Quick Actions', 'skylearn-billing-pro' ); ?></h3>
				<div class="action-buttons">
					<a href="<?php echo esc_url( add_query_arg( 'tab', 'subscriptions' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Manage Subscriptions', 'skylearn-billing-pro' ); ?>
					</a>
					<a href="<?php echo esc_url( add_query_arg( 'tab', 'courses' ) ); ?>" class="button">
						<?php esc_html_e( 'View My Courses', 'skylearn-billing-pro' ); ?>
					</a>
					<a href="<?php echo esc_url( add_query_arg( 'tab', 'transactions' ) ); ?>" class="button">
						<?php esc_html_e( 'Download Invoices', 'skylearn-billing-pro' ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render subscriptions tab.
	 *
	 * @since    1.0.0
	 */
	private function render_subscriptions_tab() {
		?>
		<div class="dashboard-subscriptions">
			<div class="section-header">
				<h3><?php esc_html_e( 'My Subscriptions', 'skylearn-billing-pro' ); ?></h3>
				<p class="section-description"><?php esc_html_e( 'Manage your active subscriptions and view renewal dates.', 'skylearn-billing-pro' ); ?></p>
			</div>
			
			<div class="subscriptions-list" id="subscriptions-list">
				<div class="loading-placeholder"><?php esc_html_e( 'Loading subscriptions...', 'skylearn-billing-pro' ); ?></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render transactions tab.
	 *
	 * @since    1.0.0
	 */
	private function render_transactions_tab() {
		?>
		<div class="dashboard-transactions">
			<div class="section-header">
				<h3><?php esc_html_e( 'Payment History', 'skylearn-billing-pro' ); ?></h3>
				<p class="section-description"><?php esc_html_e( 'View your payment history and download receipts.', 'skylearn-billing-pro' ); ?></p>
			</div>
			
			<div class="transactions-filters">
				<select id="transaction-status-filter">
					<option value=""><?php esc_html_e( 'All Statuses', 'skylearn-billing-pro' ); ?></option>
					<option value="completed"><?php esc_html_e( 'Completed', 'skylearn-billing-pro' ); ?></option>
					<option value="pending"><?php esc_html_e( 'Pending', 'skylearn-billing-pro' ); ?></option>
					<option value="failed"><?php esc_html_e( 'Failed', 'skylearn-billing-pro' ); ?></option>
					<option value="refunded"><?php esc_html_e( 'Refunded', 'skylearn-billing-pro' ); ?></option>
				</select>
				
				<input type="date" id="transaction-date-from" placeholder="<?php esc_attr_e( 'From Date', 'skylearn-billing-pro' ); ?>">
				<input type="date" id="transaction-date-to" placeholder="<?php esc_attr_e( 'To Date', 'skylearn-billing-pro' ); ?>">
				
				<button type="button" id="apply-transaction-filters" class="button">
					<?php esc_html_e( 'Apply Filters', 'skylearn-billing-pro' ); ?>
				</button>
			</div>
			
			<div class="transactions-list" id="transactions-list">
				<div class="loading-placeholder"><?php esc_html_e( 'Loading transactions...', 'skylearn-billing-pro' ); ?></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render courses tab.
	 *
	 * @since    1.0.0
	 */
	private function render_courses_tab() {
		?>
		<div class="dashboard-courses">
			<div class="section-header">
				<h3><?php esc_html_e( 'My Courses', 'skylearn-billing-pro' ); ?></h3>
				<p class="section-description"><?php esc_html_e( 'Access your enrolled courses and track your progress.', 'skylearn-billing-pro' ); ?></p>
			</div>
			
			<div class="courses-list" id="courses-list">
				<div class="loading-placeholder"><?php esc_html_e( 'Loading courses...', 'skylearn-billing-pro' ); ?></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render preferences tab.
	 *
	 * @since    1.0.0
	 */
	private function render_preferences_tab() {
		$notification_manager = SLBP_Plugin::get_instance()->resolve( 'notification_manager' );
		$user_preferences = $notification_manager ? $notification_manager->get_user_notification_preferences( $this->user_id ) : array();
		?>
		<div class="dashboard-preferences">
			<div class="section-header">
				<h3><?php esc_html_e( 'Account Preferences', 'skylearn-billing-pro' ); ?></h3>
				<p class="section-description"><?php esc_html_e( 'Manage your notification preferences and account settings.', 'skylearn-billing-pro' ); ?></p>
			</div>
			
			<form id="preferences-form" class="preferences-form">
				<?php wp_nonce_field( 'slbp_dashboard_nonce', 'slbp_nonce' ); ?>
				
				<div class="preferences-section">
					<h4><?php esc_html_e( 'Email Notifications', 'skylearn-billing-pro' ); ?></h4>
					
					<?php if ( $notification_manager ) : ?>
						<?php $notification_types = $notification_manager->get_notification_types(); ?>
						<?php foreach ( $notification_types as $type => $config ) : ?>
							<label class="preference-item">
								<input type="checkbox" 
									   name="notifications[<?php echo esc_attr( $type ); ?>][email]" 
									   value="1" 
									   <?php checked( $user_preferences[ $type ]['email'] ?? true ); ?>>
								<span class="preference-label"><?php echo esc_html( $config['name'] ); ?></span>
								<span class="preference-description"><?php echo esc_html( $config['description'] ); ?></span>
							</label>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
				
				<div class="preferences-section">
					<h4><?php esc_html_e( 'Privacy Settings', 'skylearn-billing-pro' ); ?></h4>
					
					<label class="preference-item">
						<input type="checkbox" name="privacy[analytics]" value="1">
						<span class="preference-label"><?php esc_html_e( 'Allow usage analytics', 'skylearn-billing-pro' ); ?></span>
						<span class="preference-description"><?php esc_html_e( 'Help us improve by sharing anonymous usage data', 'skylearn-billing-pro' ); ?></span>
					</label>
					
					<label class="preference-item">
						<input type="checkbox" name="privacy[marketing]" value="1">
						<span class="preference-label"><?php esc_html_e( 'Receive marketing emails', 'skylearn-billing-pro' ); ?></span>
						<span class="preference-description"><?php esc_html_e( 'Get updates about new courses and special offers', 'skylearn-billing-pro' ); ?></span>
					</label>
				</div>
				
				<div class="form-actions">
					<button type="submit" class="button button-primary">
						<?php esc_html_e( 'Save Preferences', 'skylearn-billing-pro' ); ?>
					</button>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Get user statistics.
	 *
	 * @since    1.0.0
	 * @return   array User statistics.
	 */
	private function get_user_stats() {
		global $wpdb;

		// Get active subscriptions count
		$subscriptions_table = $wpdb->prefix . 'slbp_subscriptions';
		$active_subscriptions = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$subscriptions_table} WHERE user_id = %d AND status = 'active'",
			$this->user_id
		) );

		// Get total spent
		$transactions_table = $wpdb->prefix . 'slbp_transactions';
		$total_spent = $wpdb->get_var( $wpdb->prepare(
			"SELECT SUM(amount) FROM {$transactions_table} WHERE user_id = %d AND status = 'completed'",
			$this->user_id
		) );

		// Get last payment date
		$last_payment = $wpdb->get_var( $wpdb->prepare(
			"SELECT created_at FROM {$transactions_table} WHERE user_id = %d AND status = 'completed' ORDER BY created_at DESC LIMIT 1",
			$this->user_id
		) );

		// Get enrolled courses count (if LearnDash is available)
		$total_courses = 0;
		if ( function_exists( 'learndash_user_get_enrolled_courses' ) ) {
			$enrolled_courses = learndash_user_get_enrolled_courses( $this->user_id );
			$total_courses = count( $enrolled_courses );
		}

		return array(
			'active_subscriptions' => intval( $active_subscriptions ),
			'total_courses'        => $total_courses,
			'total_spent'          => $total_spent ? '$' . number_format( $total_spent, 2 ) : '$0.00',
			'last_payment_date'    => $last_payment ? mysql2date( 'M j, Y', $last_payment ) : __( 'None', 'skylearn-billing-pro' ),
		);
	}

	/**
	 * Render recent activity.
	 *
	 * @since    1.0.0
	 */
	private function render_recent_activity() {
		global $wpdb;

		$transactions_table = $wpdb->prefix . 'slbp_transactions';
		$recent_transactions = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$transactions_table} WHERE user_id = %d ORDER BY created_at DESC LIMIT 5",
			$this->user_id
		) );

		if ( empty( $recent_transactions ) ) {
			echo '<p class="no-activity">' . esc_html__( 'No recent activity found.', 'skylearn-billing-pro' ) . '</p>';
			return;
		}

		echo '<div class="activity-items">';
		foreach ( $recent_transactions as $transaction ) {
			$status_class = 'status-' . $transaction->status;
			$amount = '$' . number_format( $transaction->amount, 2 );
			$date = mysql2date( 'M j, Y', $transaction->created_at );
			
			echo '<div class="activity-item">';
			echo '<div class="activity-icon ' . esc_attr( $status_class ) . '">💳</div>';
			echo '<div class="activity-content">';
			echo '<div class="activity-title">' . esc_html( ucfirst( $transaction->status ) ) . ' Payment</div>';
			echo '<div class="activity-meta">' . esc_html( $amount ) . ' • ' . esc_html( $date ) . '</div>';
			echo '</div>';
			echo '</div>';
		}
		echo '</div>';
	}

	/**
	 * AJAX handler to get user subscriptions.
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_user_subscriptions() {
		check_ajax_referer( 'slbp_dashboard_nonce', 'nonce' );

		if ( ! $this->user_id ) {
			wp_send_json_error( __( 'User not logged in.', 'skylearn-billing-pro' ) );
		}

		global $wpdb;
		$subscriptions_table = $wpdb->prefix . 'slbp_subscriptions';
		
		$subscriptions = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$subscriptions_table} WHERE user_id = %d ORDER BY created_at DESC",
			$this->user_id
		) );

		$formatted_subscriptions = array();
		foreach ( $subscriptions as $subscription ) {
			$formatted_subscriptions[] = array(
				'id'                => $subscription->id,
				'subscription_id'   => $subscription->subscription_id,
				'plan_id'           => $subscription->plan_id,
				'status'            => $subscription->status,
				'amount'            => number_format( $subscription->amount, 2 ),
				'currency'          => $subscription->currency,
				'billing_cycle'     => $subscription->billing_cycle,
				'next_billing_date' => $subscription->next_billing_date ? mysql2date( 'M j, Y', $subscription->next_billing_date ) : '',
				'created_at'        => mysql2date( 'M j, Y', $subscription->created_at ),
			);
		}

		wp_send_json_success( $formatted_subscriptions );
	}

	/**
	 * AJAX handler to get user transactions.
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_user_transactions() {
		check_ajax_referer( 'slbp_dashboard_nonce', 'nonce' );

		if ( ! $this->user_id ) {
			wp_send_json_error( __( 'User not logged in.', 'skylearn-billing-pro' ) );
		}

		$filters = array(
			'status'    => sanitize_text_field( $_POST['status'] ?? '' ),
			'date_from' => sanitize_text_field( $_POST['date_from'] ?? '' ),
			'date_to'   => sanitize_text_field( $_POST['date_to'] ?? '' ),
		);

		global $wpdb;
		$transactions_table = $wpdb->prefix . 'slbp_transactions';
		
		$where_conditions = array( 'user_id = %d' );
		$where_values = array( $this->user_id );

		if ( ! empty( $filters['status'] ) ) {
			$where_conditions[] = 'status = %s';
			$where_values[] = $filters['status'];
		}

		if ( ! empty( $filters['date_from'] ) ) {
			$where_conditions[] = 'DATE(created_at) >= %s';
			$where_values[] = $filters['date_from'];
		}

		if ( ! empty( $filters['date_to'] ) ) {
			$where_conditions[] = 'DATE(created_at) <= %s';
			$where_values[] = $filters['date_to'];
		}

		$where_clause = 'WHERE ' . implode( ' AND ', $where_conditions );
		$query = "SELECT * FROM {$transactions_table} {$where_clause} ORDER BY created_at DESC LIMIT 50";
		
		$transactions = $wpdb->get_results( $wpdb->prepare( $query, $where_values ) );

		$formatted_transactions = array();
		foreach ( $transactions as $transaction ) {
			$formatted_transactions[] = array(
				'id'             => $transaction->id,
				'transaction_id' => $transaction->transaction_id,
				'order_id'       => $transaction->order_id,
				'amount'         => number_format( $transaction->amount, 2 ),
				'currency'       => $transaction->currency,
				'status'         => $transaction->status,
				'payment_gateway' => $transaction->payment_gateway,
				'created_at'     => mysql2date( 'M j, Y g:i A', $transaction->created_at ),
				'download_url'   => add_query_arg( array(
					'action' => 'slbp_download_invoice',
					'transaction_id' => $transaction->id,
					'nonce' => wp_create_nonce( 'slbp_download_' . $transaction->id ),
				), admin_url( 'admin-ajax.php' ) ),
			);
		}

		wp_send_json_success( $formatted_transactions );
	}

	/**
	 * AJAX handler to get user course enrollments.
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_user_enrollments() {
		check_ajax_referer( 'slbp_dashboard_nonce', 'nonce' );

		if ( ! $this->user_id ) {
			wp_send_json_error( __( 'User not logged in.', 'skylearn-billing-pro' ) );
		}

		$courses = array();

		// Get LearnDash courses if available
		if ( function_exists( 'learndash_user_get_enrolled_courses' ) ) {
			$enrolled_course_ids = learndash_user_get_enrolled_courses( $this->user_id );
			
			foreach ( $enrolled_course_ids as $course_id ) {
				$course = get_post( $course_id );
				if ( $course ) {
					$progress = 0;
					if ( function_exists( 'learndash_course_progress' ) ) {
						$progress_data = learndash_course_progress( array(
							'user_id'   => $this->user_id,
							'course_id' => $course_id,
						) );
						$progress = intval( $progress_data['percentage'] ?? 0 );
					}

					$courses[] = array(
						'id'          => $course_id,
						'title'       => $course->post_title,
						'url'         => get_permalink( $course_id ),
						'progress'    => $progress,
						'enrolled_at' => get_user_meta( $this->user_id, 'course_' . $course_id . '_access_from', true ),
					);
				}
			}
		}

		wp_send_json_success( $courses );
	}

	/**
	 * AJAX handler to download invoice.
	 *
	 * @since    1.0.0
	 */
	public function ajax_download_invoice() {
		$transaction_id = intval( $_GET['transaction_id'] ?? 0 );
		$nonce = $_GET['nonce'] ?? '';

		if ( ! wp_verify_nonce( $nonce, 'slbp_download_' . $transaction_id ) ) {
			wp_die( __( 'Invalid nonce.', 'skylearn-billing-pro' ) );
		}

		if ( ! $this->user_id ) {
			wp_die( __( 'User not logged in.', 'skylearn-billing-pro' ) );
		}

		// Get transaction and verify ownership
		global $wpdb;
		$transactions_table = $wpdb->prefix . 'slbp_transactions';
		
		$transaction = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$transactions_table} WHERE id = %d AND user_id = %d",
			$transaction_id,
			$this->user_id
		) );

		if ( ! $transaction ) {
			wp_die( __( 'Transaction not found.', 'skylearn-billing-pro' ) );
		}

		// Generate and serve PDF invoice
		$this->generate_invoice_pdf( $transaction );
		exit;
	}

	/**
	 * Generate PDF invoice.
	 *
	 * @since    1.0.0
	 * @param    object $transaction Transaction data.
	 */
	private function generate_invoice_pdf( $transaction ) {
		// Simple text-based invoice for now
		// In a real implementation, you'd use a PDF library like TCPDF or FPDF
		
		header( 'Content-Type: text/plain' );
		header( 'Content-Disposition: attachment; filename="invoice-' . $transaction->transaction_id . '.txt"' );
		
		$user = get_user_by( 'id', $transaction->user_id );
		$site_name = get_bloginfo( 'name' );
		
		echo "INVOICE\n";
		echo "==============================\n\n";
		echo "From: {$site_name}\n";
		echo "To: {$user->display_name} ({$user->user_email})\n\n";
		echo "Transaction ID: {$transaction->transaction_id}\n";
		echo "Order ID: {$transaction->order_id}\n";
		echo "Date: " . mysql2date( 'F j, Y', $transaction->created_at ) . "\n";
		echo "Amount: {$transaction->currency} " . number_format( $transaction->amount, 2 ) . "\n";
		echo "Status: {$transaction->status}\n";
		echo "Payment Method: {$transaction->payment_gateway}\n\n";
		echo "Thank you for your purchase!\n";
	}

	/**
	 * AJAX handler to cancel subscription.
	 *
	 * @since    1.0.0
	 */
	public function ajax_cancel_subscription() {
		check_ajax_referer( 'slbp_dashboard_nonce', 'nonce' );

		if ( ! $this->user_id ) {
			wp_send_json_error( __( 'User not logged in.', 'skylearn-billing-pro' ) );
		}

		$subscription_id = sanitize_text_field( $_POST['subscription_id'] ?? '' );
		
		if ( empty( $subscription_id ) ) {
			wp_send_json_error( __( 'Subscription ID is required.', 'skylearn-billing-pro' ) );
		}

		// Verify subscription ownership
		global $wpdb;
		$subscriptions_table = $wpdb->prefix . 'slbp_subscriptions';
		
		$subscription = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$subscriptions_table} WHERE subscription_id = %s AND user_id = %d",
			$subscription_id,
			$this->user_id
		) );

		if ( ! $subscription ) {
			wp_send_json_error( __( 'Subscription not found.', 'skylearn-billing-pro' ) );
		}

		// Cancel subscription via payment gateway
		$plugin = SLBP_Plugin::get_instance();
		$gateway = $plugin->get_payment_gateway( $subscription->payment_gateway );
		
		if ( $gateway && method_exists( $gateway, 'cancel_subscription' ) ) {
			$result = $gateway->cancel_subscription( $subscription_id );
			
			if ( is_wp_error( $result ) ) {
				wp_send_json_error( $result->get_error_message() );
			}
		}

		// Update subscription status in database
		$wpdb->update(
			$subscriptions_table,
			array( 'status' => 'cancelled' ),
			array( 'subscription_id' => $subscription_id ),
			array( '%s' ),
			array( '%s' )
		);

		// Trigger cancellation event
		do_action( 'slbp_subscription_cancelled', $this->user_id, array(
			'subscription_id' => $subscription_id,
			'user_id'         => $this->user_id,
		) );

		wp_send_json_success( __( 'Subscription cancelled successfully.', 'skylearn-billing-pro' ) );
	}
}