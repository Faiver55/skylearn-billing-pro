<?php
/**
 * The admin area security and compliance dashboard.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/admin
 */

/**
 * The admin area security and compliance dashboard.
 *
 * Provides centralized security and compliance management interface
 * for administrators to monitor, configure, and audit security measures.
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/admin
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Security_Dashboard {

	/**
	 * The security manager instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SLBP_Security_Manager    $security_manager    The security manager instance.
	 */
	private $security_manager;

	/**
	 * The compliance manager instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SLBP_Compliance_Manager    $compliance_manager    The compliance manager instance.
	 */
	private $compliance_manager;

	/**
	 * The PCI compliance manager instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SLBP_PCI_Compliance_Manager    $pci_manager    The PCI compliance manager instance.
	 */
	private $pci_manager;

	/**
	 * The privacy manager instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SLBP_Privacy_Manager    $privacy_manager    The privacy manager instance.
	 */
	private $privacy_manager;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->security_manager = new SLBP_Security_Manager();
		$this->compliance_manager = new SLBP_Compliance_Manager();
		$this->pci_manager = new SLBP_PCI_Compliance_Manager();
		$this->privacy_manager = new SLBP_Privacy_Manager();
		$this->init_hooks();
	}

	/**
	 * Initialize admin hooks.
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		add_action( 'admin_menu', array( $this, 'add_security_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'admin_init', array( $this, 'register_security_settings' ) );

		// AJAX handlers
		add_action( 'wp_ajax_slbp_get_security_overview', array( $this, 'ajax_get_security_overview' ) );
		add_action( 'wp_ajax_slbp_get_compliance_status', array( $this, 'ajax_get_compliance_status' ) );
		add_action( 'wp_ajax_slbp_export_audit_logs', array( $this, 'ajax_export_audit_logs' ) );
		add_action( 'wp_ajax_slbp_run_security_scan', array( $this, 'ajax_run_security_scan' ) );

		// Dashboard widgets
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widgets' ) );
	}

	/**
	 * Add security menu to admin.
	 *
	 * @since    1.0.0
	 */
	public function add_security_menu() {
		add_menu_page(
			__( 'Security & Compliance', 'skylearn-billing-pro' ),
			__( 'Security', 'skylearn-billing-pro' ),
			'manage_options',
			'slbp-security',
			array( $this, 'security_dashboard_page' ),
			'dashicons-shield-alt',
			30
		);

		add_submenu_page(
			'slbp-security',
			__( 'Security Overview', 'skylearn-billing-pro' ),
			__( 'Overview', 'skylearn-billing-pro' ),
			'manage_options',
			'slbp-security',
			array( $this, 'security_dashboard_page' )
		);

		add_submenu_page(
			'slbp-security',
			__( 'Security Settings', 'skylearn-billing-pro' ),
			__( 'Settings', 'skylearn-billing-pro' ),
			'manage_options',
			'slbp-security-settings',
			array( $this, 'security_settings_page' )
		);

		add_submenu_page(
			'slbp-security',
			__( 'Audit Logs', 'skylearn-billing-pro' ),
			__( 'Audit Logs', 'skylearn-billing-pro' ),
			'manage_options',
			'slbp-audit-logs',
			array( $this, 'audit_logs_page' )
		);

		add_submenu_page(
			'slbp-security',
			__( 'Compliance Reports', 'skylearn-billing-pro' ),
			__( 'Compliance', 'skylearn-billing-pro' ),
			'manage_options',
			'slbp-compliance',
			array( $this, 'compliance_page' )
		);
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @since    1.0.0
	 * @param    string    $hook    Current admin page hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		// Only load on our security pages
		if ( strpos( $hook, 'slbp-security' ) === false && strpos( $hook, 'slbp-audit' ) === false && strpos( $hook, 'slbp-compliance' ) === false ) {
			return;
		}

		wp_enqueue_script(
			'slbp-security-admin',
			SLBP_PLUGIN_URL . 'admin/js/slbp-security-admin.js',
			array( 'jquery', 'chart-js' ),
			SLBP_VERSION,
			true
		);

		wp_enqueue_style(
			'slbp-security-admin',
			SLBP_PLUGIN_URL . 'admin/css/slbp-security-admin.css',
			array(),
			SLBP_VERSION
		);

		// Enqueue Chart.js for analytics
		wp_enqueue_script(
			'chart-js',
			'https://cdn.jsdelivr.net/npm/chart.js',
			array(),
			'3.9.1',
			true
		);

		wp_localize_script( 'slbp-security-admin', 'slbp_security_admin', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'slbp_security_nonce' ),
			'strings' => array(
				'loading' => __( 'Loading...', 'skylearn-billing-pro' ),
				'error' => __( 'An error occurred', 'skylearn-billing-pro' ),
				'confirm_scan' => __( 'Are you sure you want to run a security scan?', 'skylearn-billing-pro' ),
			),
		) );
	}

	/**
	 * Security dashboard page.
	 *
	 * @since    1.0.0
	 */
	public function security_dashboard_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Security & Compliance Dashboard', 'skylearn-billing-pro' ); ?></h1>
			
			<div class="slbp-security-dashboard">
				<!-- Security Score Card -->
				<div class="slbp-dashboard-card slbp-security-score">
					<h2><?php esc_html_e( 'Security Score', 'skylearn-billing-pro' ); ?></h2>
					<div class="slbp-score-display">
						<div class="slbp-score-circle" id="slbp-security-score-circle">
							<span class="slbp-score-number" id="slbp-security-score">--</span>
							<span class="slbp-score-text">/100</span>
						</div>
						<div class="slbp-score-status" id="slbp-security-status">
							<?php esc_html_e( 'Loading...', 'skylearn-billing-pro' ); ?>
						</div>
					</div>
					<button type="button" id="slbp-run-security-scan" class="button button-primary">
						<?php esc_html_e( 'Run Security Scan', 'skylearn-billing-pro' ); ?>
					</button>
				</div>

				<!-- PCI Compliance Status -->
				<div class="slbp-dashboard-card slbp-pci-status">
					<h2><?php esc_html_e( 'PCI DSS Compliance', 'skylearn-billing-pro' ); ?></h2>
					<div class="slbp-compliance-indicators" id="slbp-pci-indicators">
						<div class="slbp-indicator">
							<span class="slbp-indicator-label"><?php esc_html_e( 'Data Protection', 'skylearn-billing-pro' ); ?></span>
							<span class="slbp-indicator-status slbp-status-loading">...</span>
						</div>
						<div class="slbp-indicator">
							<span class="slbp-indicator-label"><?php esc_html_e( 'Access Control', 'skylearn-billing-pro' ); ?></span>
							<span class="slbp-indicator-status slbp-status-loading">...</span>
						</div>
						<div class="slbp-indicator">
							<span class="slbp-indicator-label"><?php esc_html_e( 'Monitoring', 'skylearn-billing-pro' ); ?></span>
							<span class="slbp-indicator-status slbp-status-loading">...</span>
						</div>
					</div>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=slbp-pci-compliance' ) ); ?>" class="button">
						<?php esc_html_e( 'View Full Assessment', 'skylearn-billing-pro' ); ?>
					</a>
				</div>

				<!-- GDPR/CCPA Status -->
				<div class="slbp-dashboard-card slbp-privacy-status">
					<h2><?php esc_html_e( 'Privacy Compliance', 'skylearn-billing-pro' ); ?></h2>
					<div class="slbp-privacy-stats" id="slbp-privacy-stats">
						<div class="slbp-stat">
							<span class="slbp-stat-number" id="slbp-data-requests">--</span>
							<span class="slbp-stat-label"><?php esc_html_e( 'Data Requests', 'skylearn-billing-pro' ); ?></span>
						</div>
						<div class="slbp-stat">
							<span class="slbp-stat-number" id="slbp-consent-rate">--%</span>
							<span class="slbp-stat-label"><?php esc_html_e( 'Consent Rate', 'skylearn-billing-pro' ); ?></span>
						</div>
					</div>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=slbp-privacy' ) ); ?>" class="button">
						<?php esc_html_e( 'Privacy Settings', 'skylearn-billing-pro' ); ?>
					</a>
				</div>

				<!-- Recent Security Events -->
				<div class="slbp-dashboard-card slbp-recent-events">
					<h2><?php esc_html_e( 'Recent Security Events', 'skylearn-billing-pro' ); ?></h2>
					<div class="slbp-events-list" id="slbp-recent-events">
						<div class="slbp-loading"><?php esc_html_e( 'Loading events...', 'skylearn-billing-pro' ); ?></div>
					</div>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=slbp-audit-logs' ) ); ?>" class="button">
						<?php esc_html_e( 'View All Logs', 'skylearn-billing-pro' ); ?>
					</a>
				</div>

				<!-- Security Recommendations -->
				<div class="slbp-dashboard-card slbp-recommendations">
					<h2><?php esc_html_e( 'Security Recommendations', 'skylearn-billing-pro' ); ?></h2>
					<div class="slbp-recommendations-list" id="slbp-security-recommendations">
						<div class="slbp-loading"><?php esc_html_e( 'Loading recommendations...', 'skylearn-billing-pro' ); ?></div>
					</div>
				</div>

				<!-- Threat Intelligence -->
				<div class="slbp-dashboard-card slbp-threat-intel">
					<h2><?php esc_html_e( 'Threat Intelligence', 'skylearn-billing-pro' ); ?></h2>
					<div class="slbp-threat-stats">
						<div class="slbp-stat">
							<span class="slbp-stat-number" id="slbp-blocked-attempts">--</span>
							<span class="slbp-stat-label"><?php esc_html_e( 'Blocked Attempts (24h)', 'skylearn-billing-pro' ); ?></span>
						</div>
						<div class="slbp-stat">
							<span class="slbp-stat-number" id="slbp-threat-level">--</span>
							<span class="slbp-stat-label"><?php esc_html_e( 'Threat Level', 'skylearn-billing-pro' ); ?></span>
						</div>
					</div>
					<canvas id="slbp-threat-chart" width="400" height="200"></canvas>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Security settings page.
	 *
	 * @since    1.0.0
	 */
	public function security_settings_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Security Settings', 'skylearn-billing-pro' ); ?></h1>
			
			<form method="post" action="options.php">
				<?php
				settings_fields( 'slbp_security_settings_group' );
				do_settings_sections( 'slbp_security_settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Audit logs page.
	 *
	 * @since    1.0.0
	 */
	public function audit_logs_page() {
		$audit_logger = new SLBP_Audit_Logger();
		
		// Handle filtering
		$filters = array(
			'event_type' => sanitize_text_field( $_GET['event_type'] ?? '' ),
			'severity' => sanitize_text_field( $_GET['severity'] ?? '' ),
			'start_date' => sanitize_text_field( $_GET['start_date'] ?? '' ),
			'end_date' => sanitize_text_field( $_GET['end_date'] ?? '' ),
			'search' => sanitize_text_field( $_GET['search'] ?? '' ),
		);

		$page = max( 1, intval( $_GET['paged'] ?? 1 ) );
		$per_page = 50;
		$offset = ( $page - 1 ) * $per_page;

		$filters['limit'] = $per_page;
		$filters['offset'] = $offset;

		$logs_result = $audit_logger->get_logs( $filters );
		$logs = $logs_result['logs'];
		$total_logs = $logs_result['total'];
		$total_pages = ceil( $total_logs / $per_page );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Audit Logs', 'skylearn-billing-pro' ); ?></h1>
			
			<!-- Filters -->
			<div class="slbp-log-filters">
				<form method="get" action="">
					<input type="hidden" name="page" value="slbp-audit-logs">
					
					<select name="event_type">
						<option value=""><?php esc_html_e( 'All Event Types', 'skylearn-billing-pro' ); ?></option>
						<option value="security" <?php selected( $filters['event_type'], 'security' ); ?>><?php esc_html_e( 'Security', 'skylearn-billing-pro' ); ?></option>
						<option value="payment" <?php selected( $filters['event_type'], 'payment' ); ?>><?php esc_html_e( 'Payment', 'skylearn-billing-pro' ); ?></option>
						<option value="user" <?php selected( $filters['event_type'], 'user' ); ?>><?php esc_html_e( 'User', 'skylearn-billing-pro' ); ?></option>
						<option value="api" <?php selected( $filters['event_type'], 'api' ); ?>><?php esc_html_e( 'API', 'skylearn-billing-pro' ); ?></option>
						<option value="compliance" <?php selected( $filters['event_type'], 'compliance' ); ?>><?php esc_html_e( 'Compliance', 'skylearn-billing-pro' ); ?></option>
					</select>
					
					<select name="severity">
						<option value=""><?php esc_html_e( 'All Severities', 'skylearn-billing-pro' ); ?></option>
						<option value="info" <?php selected( $filters['severity'], 'info' ); ?>><?php esc_html_e( 'Info', 'skylearn-billing-pro' ); ?></option>
						<option value="warning" <?php selected( $filters['severity'], 'warning' ); ?>><?php esc_html_e( 'Warning', 'skylearn-billing-pro' ); ?></option>
						<option value="error" <?php selected( $filters['severity'], 'error' ); ?>><?php esc_html_e( 'Error', 'skylearn-billing-pro' ); ?></option>
					</select>
					
					<input type="date" name="start_date" value="<?php echo esc_attr( $filters['start_date'] ); ?>" placeholder="<?php esc_attr_e( 'Start Date', 'skylearn-billing-pro' ); ?>">
					<input type="date" name="end_date" value="<?php echo esc_attr( $filters['end_date'] ); ?>" placeholder="<?php esc_attr_e( 'End Date', 'skylearn-billing-pro' ); ?>">
					
					<input type="text" name="search" value="<?php echo esc_attr( $filters['search'] ); ?>" placeholder="<?php esc_attr_e( 'Search logs...', 'skylearn-billing-pro' ); ?>">
					
					<button type="submit" class="button"><?php esc_html_e( 'Filter', 'skylearn-billing-pro' ); ?></button>
					<button type="button" id="slbp-export-logs" class="button"><?php esc_html_e( 'Export CSV', 'skylearn-billing-pro' ); ?></button>
				</form>
			</div>

			<!-- Logs Table -->
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date/Time', 'skylearn-billing-pro' ); ?></th>
						<th><?php esc_html_e( 'Event Type', 'skylearn-billing-pro' ); ?></th>
						<th><?php esc_html_e( 'Action', 'skylearn-billing-pro' ); ?></th>
						<th><?php esc_html_e( 'User', 'skylearn-billing-pro' ); ?></th>
						<th><?php esc_html_e( 'IP Address', 'skylearn-billing-pro' ); ?></th>
						<th><?php esc_html_e( 'Severity', 'skylearn-billing-pro' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $logs ) ) : ?>
					<tr>
						<td colspan="6"><?php esc_html_e( 'No logs found.', 'skylearn-billing-pro' ); ?></td>
					</tr>
					<?php else : ?>
					<?php foreach ( $logs as $log ) : ?>
					<tr class="slbp-log-row slbp-severity-<?php echo esc_attr( $log->severity ); ?>" data-log-id="<?php echo esc_attr( $log->id ); ?>">
						<td><?php echo esc_html( $log->created_at ); ?></td>
						<td>
							<span class="slbp-event-type slbp-event-<?php echo esc_attr( $log->event_type ); ?>">
								<?php echo esc_html( ucfirst( $log->event_type ) ); ?>
							</span>
						</td>
						<td><?php echo esc_html( $log->action ); ?></td>
						<td>
							<?php if ( $log->user_id ) : ?>
								<?php $user = get_userdata( $log->user_id ); ?>
								<?php echo $user ? esc_html( $user->display_name ) : esc_html__( 'Unknown User', 'skylearn-billing-pro' ); ?>
							<?php else : ?>
								<?php esc_html_e( 'System', 'skylearn-billing-pro' ); ?>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $log->user_ip ); ?></td>
						<td>
							<span class="slbp-severity slbp-severity-<?php echo esc_attr( $log->severity ); ?>">
								<?php echo esc_html( ucfirst( $log->severity ) ); ?>
							</span>
						</td>
					</tr>
					<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<!-- Pagination -->
			<?php if ( $total_pages > 1 ) : ?>
			<div class="tablenav">
				<div class="tablenav-pages">
					<?php
					echo paginate_links( array(
						'base' => add_query_arg( 'paged', '%#%' ),
						'format' => '',
						'prev_text' => __( '&laquo;', 'skylearn-billing-pro' ),
						'next_text' => __( '&raquo;', 'skylearn-billing-pro' ),
						'total' => $total_pages,
						'current' => $page,
					) );
					?>
				</div>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Compliance page.
	 *
	 * @since    1.0.0
	 */
	public function compliance_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Compliance Reports', 'skylearn-billing-pro' ); ?></h1>
			
			<div class="slbp-compliance-dashboard">
				<!-- GDPR/CCPA Compliance -->
				<div class="slbp-compliance-section">
					<h2><?php esc_html_e( 'GDPR/CCPA Compliance', 'skylearn-billing-pro' ); ?></h2>
					<div class="slbp-compliance-status" id="slbp-gdpr-status">
						<div class="slbp-loading"><?php esc_html_e( 'Loading compliance status...', 'skylearn-billing-pro' ); ?></div>
					</div>
					<div class="slbp-compliance-actions">
						<button type="button" id="slbp-generate-gdpr-report" class="button button-primary">
							<?php esc_html_e( 'Generate GDPR Report', 'skylearn-billing-pro' ); ?>
						</button>
						<button type="button" id="slbp-export-user-data" class="button">
							<?php esc_html_e( 'Export User Data', 'skylearn-billing-pro' ); ?>
						</button>
					</div>
				</div>

				<!-- PCI DSS Compliance -->
				<div class="slbp-compliance-section">
					<h2><?php esc_html_e( 'PCI DSS Compliance', 'skylearn-billing-pro' ); ?></h2>
					<div class="slbp-compliance-status" id="slbp-pci-status-detailed">
						<div class="slbp-loading"><?php esc_html_e( 'Loading PCI compliance status...', 'skylearn-billing-pro' ); ?></div>
					</div>
					<div class="slbp-compliance-actions">
						<button type="button" id="slbp-run-pci-assessment" class="button button-primary">
							<?php esc_html_e( 'Run PCI Assessment', 'skylearn-billing-pro' ); ?>
						</button>
						<button type="button" id="slbp-generate-pci-report" class="button">
							<?php esc_html_e( 'Generate PCI Report', 'skylearn-billing-pro' ); ?>
						</button>
					</div>
				</div>

				<!-- Data Retention Policies -->
				<div class="slbp-compliance-section">
					<h2><?php esc_html_e( 'Data Retention Policies', 'skylearn-billing-pro' ); ?></h2>
					<div class="slbp-retention-summary">
						<table class="wp-list-table widefat">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Data Type', 'skylearn-billing-pro' ); ?></th>
									<th><?php esc_html_e( 'Retention Period', 'skylearn-billing-pro' ); ?></th>
									<th><?php esc_html_e( 'Records Count', 'skylearn-billing-pro' ); ?></th>
									<th><?php esc_html_e( 'Next Cleanup', 'skylearn-billing-pro' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><?php esc_html_e( 'Transaction Data', 'skylearn-billing-pro' ); ?></td>
									<td>7 years</td>
									<td id="transaction-count">--</td>
									<td id="transaction-cleanup">--</td>
								</tr>
								<tr>
									<td><?php esc_html_e( 'Audit Logs', 'skylearn-billing-pro' ); ?></td>
									<td>90 days</td>
									<td id="audit-count">--</td>
									<td id="audit-cleanup">--</td>
								</tr>
								<tr>
									<td><?php esc_html_e( 'User Consent', 'skylearn-billing-pro' ); ?></td>
									<td>Indefinite</td>
									<td id="consent-count">--</td>
									<td>N/A</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Register security settings.
	 *
	 * @since    1.0.0
	 */
	public function register_security_settings() {
		register_setting( 'slbp_security_settings_group', 'slbp_security_settings', array( $this, 'sanitize_security_settings' ) );

		// General Security Section
		add_settings_section(
			'slbp_security_general',
			__( 'General Security Settings', 'skylearn-billing-pro' ),
			array( $this, 'security_general_section_callback' ),
			'slbp_security_settings'
		);

		add_settings_field(
			'mandatory_2fa_for_admins',
			__( 'Mandatory 2FA for Admins', 'skylearn-billing-pro' ),
			array( $this, 'checkbox_field_callback' ),
			'slbp_security_settings',
			'slbp_security_general',
			array( 'field' => 'mandatory_2fa_for_admins' )
		);

		add_settings_field(
			'max_login_attempts',
			__( 'Max Login Attempts', 'skylearn-billing-pro' ),
			array( $this, 'number_field_callback' ),
			'slbp_security_settings',
			'slbp_security_general',
			array( 'field' => 'max_login_attempts', 'min' => 1, 'max' => 20 )
		);

		add_settings_field(
			'session_timeout',
			__( 'Session Timeout (minutes)', 'skylearn-billing-pro' ),
			array( $this, 'number_field_callback' ),
			'slbp_security_settings',
			'slbp_security_general',
			array( 'field' => 'session_timeout', 'min' => 5, 'max' => 1440 )
		);

		// API Security Section
		add_settings_section(
			'slbp_api_security',
			__( 'API Security Settings', 'skylearn-billing-pro' ),
			array( $this, 'api_security_section_callback' ),
			'slbp_security_settings'
		);

		add_settings_field(
			'api_rate_limit',
			__( 'API Rate Limit (requests per hour)', 'skylearn-billing-pro' ),
			array( $this, 'number_field_callback' ),
			'slbp_security_settings',
			'slbp_api_security',
			array( 'field' => 'api_rate_limit', 'min' => 10, 'max' => 10000 )
		);

		add_settings_field(
			'require_api_authentication',
			__( 'Require API Authentication', 'skylearn-billing-pro' ),
			array( $this, 'checkbox_field_callback' ),
			'slbp_security_settings',
			'slbp_api_security',
			array( 'field' => 'require_api_authentication' )
		);
	}

	/**
	 * Add dashboard widgets.
	 *
	 * @since    1.0.0
	 */
	public function add_dashboard_widgets() {
		wp_add_dashboard_widget(
			'slbp_security_widget',
			__( 'SkyLearn Security Status', 'skylearn-billing-pro' ),
			array( $this, 'security_dashboard_widget' )
		);
	}

	/**
	 * Security dashboard widget.
	 *
	 * @since    1.0.0
	 */
	public function security_dashboard_widget() {
		$audit_results = $this->security_manager->run_security_audit();
		$security_score = $audit_results['security_score'];
		
		$score_class = 'high';
		if ( $security_score < 50 ) {
			$score_class = 'low';
		} elseif ( $security_score < 80 ) {
			$score_class = 'medium';
		}
		?>
		<div class="slbp-widget-content">
			<div class="slbp-widget-score">
				<span class="slbp-score-number slbp-score-<?php echo esc_attr( $score_class ); ?>">
					<?php echo esc_html( $security_score ); ?>
				</span>
				<span class="slbp-score-label">/100</span>
			</div>
			<div class="slbp-widget-status">
				<?php if ( $security_score >= 80 ) : ?>
					<span class="slbp-status-good"><?php esc_html_e( 'Good Security Posture', 'skylearn-billing-pro' ); ?></span>
				<?php elseif ( $security_score >= 50 ) : ?>
					<span class="slbp-status-warning"><?php esc_html_e( 'Security Needs Attention', 'skylearn-billing-pro' ); ?></span>
				<?php else : ?>
					<span class="slbp-status-critical"><?php esc_html_e( 'Critical Security Issues', 'skylearn-billing-pro' ); ?></span>
				<?php endif; ?>
			</div>
			<div class="slbp-widget-actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=slbp-security' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'View Security Dashboard', 'skylearn-billing-pro' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX handler for getting security overview.
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_security_overview() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'slbp_security_nonce' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Security check failed.' );
		}

		$audit_results = $this->security_manager->run_security_audit();
		$pci_assessment = $this->pci_manager->run_pci_assessment();

		// Get recent security events
		$audit_logger = new SLBP_Audit_Logger();
		$recent_events = $audit_logger->get_logs( array(
			'event_type' => 'security',
			'limit' => 5,
			'orderby' => 'created_at',
			'order' => 'DESC',
		) );

		// Calculate threat statistics
		$threat_stats = $this->calculate_threat_stats();

		wp_send_json_success( array(
			'security_score' => $audit_results['security_score'],
			'pci_score' => $pci_assessment['score'],
			'recent_events' => $recent_events['logs'],
			'recommendations' => $this->get_security_recommendations( $audit_results ),
			'threat_stats' => $threat_stats,
		) );
	}

	/**
	 * AJAX handler for getting compliance status.
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_compliance_status() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'slbp_security_nonce' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Security check failed.' );
		}

		// Get GDPR/CCPA status
		global $wpdb;
		$data_requests = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'user_request'" );
		
		// Calculate consent rate
		$total_users = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->users}" );
		$users_with_consent = $wpdb->get_var( "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} WHERE meta_key = 'slbp_consent_preferences'" );
		$consent_rate = $total_users > 0 ? round( ( $users_with_consent / $total_users ) * 100 ) : 0;

		wp_send_json_success( array(
			'data_requests' => $data_requests,
			'consent_rate' => $consent_rate,
			'gdpr_compliant' => $consent_rate > 50, // Simple compliance check
		) );
	}

	/**
	 * Calculate threat statistics.
	 *
	 * @since    1.0.0
	 * @return   array    Threat statistics.
	 */
	private function calculate_threat_stats() {
		$audit_logger = new SLBP_Audit_Logger();
		
		// Get blocked attempts in last 24 hours
		$blocked_attempts = $audit_logger->get_logs( array(
			'action' => 'brute_force_blocked',
			'start_date' => date( 'Y-m-d H:i:s', strtotime( '-24 hours' ) ),
			'limit' => 0,
		) );

		// Get threat level based on recent activity
		$threat_events = $audit_logger->get_logs( array(
			'severity' => 'error',
			'start_date' => date( 'Y-m-d H:i:s', strtotime( '-7 days' ) ),
			'limit' => 0,
		) );

		$threat_level = 'Low';
		if ( $threat_events['total'] > 50 ) {
			$threat_level = 'High';
		} elseif ( $threat_events['total'] > 20 ) {
			$threat_level = 'Medium';
		}

		return array(
			'blocked_attempts' => $blocked_attempts['total'],
			'threat_level' => $threat_level,
			'chart_data' => $this->get_threat_chart_data(),
		);
	}

	/**
	 * Get threat chart data for the last 7 days.
	 *
	 * @since    1.0.0
	 * @return   array    Chart data.
	 */
	private function get_threat_chart_data() {
		$audit_logger = new SLBP_Audit_Logger();
		$chart_data = array();

		for ( $i = 6; $i >= 0; $i-- ) {
			$date = date( 'Y-m-d', strtotime( "-{$i} days" ) );
			$start_date = $date . ' 00:00:00';
			$end_date = $date . ' 23:59:59';

			$threats = $audit_logger->get_logs( array(
				'severity' => 'error',
				'start_date' => $start_date,
				'end_date' => $end_date,
				'limit' => 0,
			) );

			$chart_data[] = array(
				'date' => $date,
				'threats' => $threats['total'],
			);
		}

		return $chart_data;
	}

	/**
	 * Get security recommendations based on audit results.
	 *
	 * @since    1.0.0
	 * @param    array    $audit_results    Security audit results.
	 * @return   array                     Security recommendations.
	 */
	private function get_security_recommendations( $audit_results ) {
		$recommendations = array();

		if ( ! $audit_results['ssl_config']['is_ssl'] ) {
			$recommendations[] = array(
				'title' => __( 'Enable SSL/HTTPS', 'skylearn-billing-pro' ),
				'description' => __( 'Your site is not using SSL encryption. Enable SSL to protect data in transit.', 'skylearn-billing-pro' ),
				'priority' => 'high',
			);
		}

		if ( $audit_results['wordpress_version']['is_outdated'] ) {
			$recommendations[] = array(
				'title' => __( 'Update WordPress', 'skylearn-billing-pro' ),
				'description' => __( 'Your WordPress installation is outdated. Update to the latest version for security patches.', 'skylearn-billing-pro' ),
				'priority' => 'high',
			);
		}

		if ( ! empty( $audit_results['user_security'] ) ) {
			$recommendations[] = array(
				'title' => __( 'Improve User Security', 'skylearn-billing-pro' ),
				'description' => __( 'Some users have security issues like weak passwords or missing 2FA.', 'skylearn-billing-pro' ),
				'priority' => 'medium',
			);
		}

		return $recommendations;
	}

	/**
	 * Sanitize security settings.
	 *
	 * @since    1.0.0
	 * @param    array    $input    Raw input data.
	 * @return   array             Sanitized settings.
	 */
	public function sanitize_security_settings( $input ) {
		$sanitized = array();

		$sanitized['mandatory_2fa_for_admins'] = ! empty( $input['mandatory_2fa_for_admins'] );
		$sanitized['max_login_attempts'] = max( 1, min( 20, intval( $input['max_login_attempts'] ) ) );
		$sanitized['session_timeout'] = max( 5, min( 1440, intval( $input['session_timeout'] ) ) );
		$sanitized['api_rate_limit'] = max( 10, min( 10000, intval( $input['api_rate_limit'] ) ) );
		$sanitized['require_api_authentication'] = ! empty( $input['require_api_authentication'] );

		return $sanitized;
	}

	/**
	 * Security general section callback.
	 *
	 * @since    1.0.0
	 */
	public function security_general_section_callback() {
		echo '<p>' . esc_html__( 'Configure general security settings for the plugin.', 'skylearn-billing-pro' ) . '</p>';
	}

	/**
	 * API security section callback.
	 *
	 * @since    1.0.0
	 */
	public function api_security_section_callback() {
		echo '<p>' . esc_html__( 'Configure API security and rate limiting settings.', 'skylearn-billing-pro' ) . '</p>';
	}

	/**
	 * Checkbox field callback.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Field arguments.
	 */
	public function checkbox_field_callback( $args ) {
		$settings = get_option( 'slbp_security_settings', array() );
		$field = $args['field'];
		$value = $settings[$field] ?? false;
		?>
		<input type="checkbox" name="slbp_security_settings[<?php echo esc_attr( $field ); ?>]" value="1" <?php checked( $value ); ?> />
		<?php
	}

	/**
	 * Number field callback.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Field arguments.
	 */
	public function number_field_callback( $args ) {
		$settings = get_option( 'slbp_security_settings', array() );
		$field = $args['field'];
		$value = $settings[$field] ?? ( $args['default'] ?? '' );
		$min = $args['min'] ?? '';
		$max = $args['max'] ?? '';
		?>
		<input type="number" 
			   name="slbp_security_settings[<?php echo esc_attr( $field ); ?>]" 
			   value="<?php echo esc_attr( $value ); ?>"
			   <?php echo ! empty( $min ) ? 'min="' . esc_attr( $min ) . '"' : ''; ?>
			   <?php echo ! empty( $max ) ? 'max="' . esc_attr( $max ) . '"' : ''; ?>
		/>
		<?php
	}
}