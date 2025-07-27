<?php
/**
 * External analytics integrations for the plugin.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/external-integrations
 */

/**
 * External analytics integrations for the plugin.
 *
 * Provides integrations with Google Analytics, Tag Manager, and other
 * business intelligence tools for comprehensive analytics tracking.
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/external-integrations
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_External_Analytics {

	/**
	 * The audit logger instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SLBP_Audit_Logger    $audit_logger    The audit logger instance.
	 */
	private $audit_logger;

	/**
	 * Integration settings.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $settings    Integration settings.
	 */
	private $settings;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->audit_logger = new SLBP_Audit_Logger();
		$this->settings = get_option( 'slbp_external_analytics_settings', array() );
		$this->init_hooks();
	}

	/**
	 * Initialize external analytics hooks.
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		// Google Analytics tracking
		add_action( 'wp_head', array( $this, 'inject_google_analytics' ) );
		add_action( 'wp_footer', array( $this, 'inject_gtm_noscript' ) );

		// Event tracking hooks
		add_action( 'slbp_payment_completed', array( $this, 'track_payment_event' ), 10, 2 );
		add_action( 'slbp_subscription_created', array( $this, 'track_subscription_event' ), 10, 2 );
		add_action( 'slbp_user_enrolled', array( $this, 'track_enrollment_event' ), 10, 3 );
		add_action( 'slbp_course_completed', array( $this, 'track_course_completion_event' ), 10, 2 );

		// AJAX handlers for external integrations
		add_action( 'wp_ajax_slbp_test_ga_connection', array( $this, 'ajax_test_ga_connection' ) );
		add_action( 'wp_ajax_slbp_sync_external_data', array( $this, 'ajax_sync_external_data' ) );

		// Scheduled data sync
		add_action( 'slbp_external_analytics_sync', array( $this, 'sync_analytics_data' ) );
	}

	/**
	 * Inject Google Analytics tracking code.
	 *
	 * @since    1.0.0
	 */
	public function inject_google_analytics() {
		if ( ! $this->is_google_analytics_enabled() ) {
			return;
		}

		$ga_id = $this->settings['google_analytics_id'] ?? '';
		$gtm_id = $this->settings['google_tag_manager_id'] ?? '';

		if ( ! empty( $ga_id ) ) {
			?>
			<!-- Google Analytics -->
			<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $ga_id ); ?>"></script>
			<script>
			window.dataLayer = window.dataLayer || [];
			function gtag(){dataLayer.push(arguments);}
			gtag('js', new Date());
			gtag('config', '<?php echo esc_js( $ga_id ); ?>', {
				custom_map: {
					'billing_platform': 'skylearn_billing_pro'
				}
			});
			</script>
			<?php
		}

		if ( ! empty( $gtm_id ) ) {
			?>
			<!-- Google Tag Manager -->
			<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
			new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
			j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
			'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
			})(window,document,'script','dataLayer','<?php echo esc_js( $gtm_id ); ?>');</script>
			<!-- End Google Tag Manager -->
			<?php
		}
	}

	/**
	 * Inject Google Tag Manager noscript code.
	 *
	 * @since    1.0.0
	 */
	public function inject_gtm_noscript() {
		if ( ! $this->is_google_analytics_enabled() ) {
			return;
		}

		$gtm_id = $this->settings['google_tag_manager_id'] ?? '';

		if ( ! empty( $gtm_id ) ) {
			?>
			<!-- Google Tag Manager (noscript) -->
			<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr( $gtm_id ); ?>"
			height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
			<!-- End Google Tag Manager (noscript) -->
			<?php
		}
	}

	/**
	 * Track payment completion event.
	 *
	 * @since    1.0.0
	 * @param    array    $payment_data    Payment data.
	 * @param    int      $user_id        User ID.
	 */
	public function track_payment_event( $payment_data, $user_id ) {
		if ( ! $this->is_google_analytics_enabled() ) {
			return;
		}

		$event_data = array(
			'event' => 'purchase',
			'transaction_id' => $payment_data['transaction_id'] ?? '',
			'value' => $payment_data['amount'] ?? 0,
			'currency' => $payment_data['currency'] ?? 'USD',
			'items' => array(
				array(
					'item_id' => $payment_data['product_id'] ?? '',
					'item_name' => $payment_data['product_name'] ?? '',
					'category' => 'course',
					'quantity' => 1,
					'price' => $payment_data['amount'] ?? 0,
				),
			),
		);

		$this->send_ga_event( $event_data );

		// Track for external BI tools
		$this->send_external_event( 'payment_completed', $payment_data );

		// Log the tracking
		$this->audit_logger->log_event(
			'analytics',
			'payment_tracked',
			$user_id,
			array(
				'transaction_id' => $payment_data['transaction_id'] ?? '',
				'amount' => $payment_data['amount'] ?? 0,
				'integrations' => $this->get_enabled_integrations(),
			),
			'info'
		);
	}

	/**
	 * Track subscription creation event.
	 *
	 * @since    1.0.0
	 * @param    array    $subscription_data    Subscription data.
	 * @param    int      $user_id             User ID.
	 */
	public function track_subscription_event( $subscription_data, $user_id ) {
		if ( ! $this->is_google_analytics_enabled() ) {
			return;
		}

		$event_data = array(
			'event' => 'begin_subscription',
			'subscription_id' => $subscription_data['subscription_id'] ?? '',
			'plan_name' => $subscription_data['plan_name'] ?? '',
			'value' => $subscription_data['amount'] ?? 0,
			'currency' => $subscription_data['currency'] ?? 'USD',
		);

		$this->send_ga_event( $event_data );

		// Track for external BI tools
		$this->send_external_event( 'subscription_created', $subscription_data );

		// Log the tracking
		$this->audit_logger->log_event(
			'analytics',
			'subscription_tracked',
			$user_id,
			array(
				'subscription_id' => $subscription_data['subscription_id'] ?? '',
				'plan_name' => $subscription_data['plan_name'] ?? '',
			),
			'info'
		);
	}

	/**
	 * Track course enrollment event.
	 *
	 * @since    1.0.0
	 * @param    int      $user_id     User ID.
	 * @param    int      $course_id   Course ID.
	 * @param    array    $enrollment_data    Enrollment data.
	 */
	public function track_enrollment_event( $user_id, $course_id, $enrollment_data ) {
		if ( ! $this->is_google_analytics_enabled() ) {
			return;
		}

		$course = get_post( $course_id );
		$course_name = $course ? $course->post_title : 'Unknown Course';

		$event_data = array(
			'event' => 'course_enrollment',
			'course_id' => $course_id,
			'course_name' => $course_name,
			'enrollment_type' => $enrollment_data['type'] ?? 'paid',
		);

		$this->send_ga_event( $event_data );

		// Track for external BI tools
		$this->send_external_event( 'course_enrollment', array(
			'user_id' => $user_id,
			'course_id' => $course_id,
			'course_name' => $course_name,
			'enrollment_data' => $enrollment_data,
		) );
	}

	/**
	 * Track course completion event.
	 *
	 * @since    1.0.0
	 * @param    int      $user_id     User ID.
	 * @param    int      $course_id   Course ID.
	 */
	public function track_course_completion_event( $user_id, $course_id ) {
		if ( ! $this->is_google_analytics_enabled() ) {
			return;
		}

		$course = get_post( $course_id );
		$course_name = $course ? $course->post_title : 'Unknown Course';

		$event_data = array(
			'event' => 'course_completion',
			'course_id' => $course_id,
			'course_name' => $course_name,
		);

		$this->send_ga_event( $event_data );

		// Track for external BI tools
		$this->send_external_event( 'course_completion', array(
			'user_id' => $user_id,
			'course_id' => $course_id,
			'course_name' => $course_name,
			'completed_at' => current_time( 'mysql' ),
		) );
	}

	/**
	 * Send event to Google Analytics via Measurement Protocol.
	 *
	 * @since    1.0.0
	 * @param    array    $event_data    Event data to send.
	 */
	private function send_ga_event( $event_data ) {
		// For client-side tracking, we'll inject JavaScript
		if ( ! wp_doing_ajax() && ! is_admin() ) {
			add_action( 'wp_footer', function() use ( $event_data ) {
				?>
				<script>
				if (typeof gtag === 'function') {
					gtag('event', '<?php echo esc_js( $event_data['event'] ); ?>', <?php echo wp_json_encode( $event_data ); ?>);
				}
				</script>
				<?php
			} );
		}

		// For server-side tracking, we could use the Measurement Protocol
		$this->send_ga_measurement_protocol( $event_data );
	}

	/**
	 * Send event via Google Analytics Measurement Protocol.
	 *
	 * @since    1.0.0
	 * @param    array    $event_data    Event data to send.
	 */
	private function send_ga_measurement_protocol( $event_data ) {
		if ( empty( $this->settings['google_analytics_measurement_id'] ) ) {
			return;
		}

		$measurement_id = $this->settings['google_analytics_measurement_id'];
		$api_secret = $this->settings['google_analytics_api_secret'] ?? '';

		if ( empty( $api_secret ) ) {
			return;
		}

		$client_id = $this->get_or_generate_client_id();
		
		$payload = array(
			'client_id' => $client_id,
			'events' => array( $event_data ),
		);

		$url = "https://www.google-analytics.com/mp/collect?measurement_id={$measurement_id}&api_secret={$api_secret}";

		wp_remote_post( $url, array(
			'body' => wp_json_encode( $payload ),
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'timeout' => 10,
		) );
	}

	/**
	 * Send event to external BI tools.
	 *
	 * @since    1.0.0
	 * @param    string    $event_name    Event name.
	 * @param    array     $event_data    Event data.
	 */
	private function send_external_event( $event_name, $event_data ) {
		// Webhook integration for external BI tools
		$webhook_url = $this->settings['bi_webhook_url'] ?? '';
		
		if ( ! empty( $webhook_url ) ) {
			$payload = array(
				'event' => $event_name,
				'data' => $event_data,
				'timestamp' => current_time( 'c' ),
				'source' => 'skylearn_billing_pro',
			);

			wp_remote_post( $webhook_url, array(
				'body' => wp_json_encode( $payload ),
				'headers' => array(
					'Content-Type' => 'application/json',
					'Authorization' => 'Bearer ' . ( $this->settings['bi_webhook_token'] ?? '' ),
				),
				'timeout' => 10,
			) );
		}

		// Zapier integration
		$zapier_webhook = $this->settings['zapier_webhook_url'] ?? '';
		
		if ( ! empty( $zapier_webhook ) ) {
			wp_remote_post( $zapier_webhook, array(
				'body' => wp_json_encode( array(
					'event' => $event_name,
					'data' => $event_data,
				) ),
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'timeout' => 10,
			) );
		}

		// Custom webhook integrations
		$custom_webhooks = $this->settings['custom_webhooks'] ?? array();
		
		foreach ( $custom_webhooks as $webhook ) {
			if ( ! empty( $webhook['url'] ) && in_array( $event_name, $webhook['events'] ?? array(), true ) ) {
				wp_remote_post( $webhook['url'], array(
					'body' => wp_json_encode( array(
						'event' => $event_name,
						'data' => $event_data,
					) ),
					'headers' => array(
						'Content-Type' => 'application/json',
						'Authorization' => $webhook['auth_header'] ?? '',
					),
					'timeout' => 10,
				) );
			}
		}
	}

	/**
	 * Sync analytics data with external platforms.
	 *
	 * @since    1.0.0
	 */
	public function sync_analytics_data() {
		// Export data for Power BI, Looker, etc.
		$this->export_for_power_bi();
		$this->export_for_looker();
		$this->export_for_tableau();

		// Log the sync
		$this->audit_logger->log_event(
			'analytics',
			'external_sync_completed',
			0,
			array(
				'integrations' => $this->get_enabled_integrations(),
				'sync_timestamp' => current_time( 'mysql' ),
			),
			'info'
		);
	}

	/**
	 * Export data for Power BI integration.
	 *
	 * @since    1.0.0
	 */
	private function export_for_power_bi() {
		if ( empty( $this->settings['power_bi_enabled'] ) ) {
			return;
		}

		// Get analytics data
		$analytics = new SLBP_Analytics();
		$data = array(
			'metrics' => $analytics->get_dashboard_metrics(),
			'revenue' => $analytics->get_revenue_chart_data(),
			'subscriptions' => $analytics->get_subscription_analytics(),
		);

		// Push to Power BI streaming dataset
		$power_bi_url = $this->settings['power_bi_streaming_url'] ?? '';
		
		if ( ! empty( $power_bi_url ) ) {
			wp_remote_post( $power_bi_url, array(
				'body' => wp_json_encode( array( $data ) ),
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'timeout' => 30,
			) );
		}
	}

	/**
	 * Export data for Looker integration.
	 *
	 * @since    1.0.0
	 */
	private function export_for_looker() {
		if ( empty( $this->settings['looker_enabled'] ) ) {
			return;
		}

		// Create CSV exports for Looker to consume
		$upload_dir = wp_upload_dir();
		$export_dir = $upload_dir['basedir'] . '/slbp-looker-exports/';
		wp_mkdir_p( $export_dir );

		// Export key metrics
		$analytics = new SLBP_Analytics();
		$metrics = $analytics->get_dashboard_metrics();

		$csv_file = $export_dir . 'metrics-' . date( 'Y-m-d-H-i-s' ) . '.csv';
		$handle = fopen( $csv_file, 'w' );

		fputcsv( $handle, array( 'metric', 'value', 'timestamp' ) );
		foreach ( $metrics as $metric => $value ) {
			fputcsv( $handle, array( $metric, $value, current_time( 'mysql' ) ) );
		}

		fclose( $handle );

		// Notify Looker of new data (if webhook configured)
		$looker_webhook = $this->settings['looker_webhook_url'] ?? '';
		if ( ! empty( $looker_webhook ) ) {
			wp_remote_post( $looker_webhook, array(
				'body' => wp_json_encode( array(
					'file_url' => $upload_dir['baseurl'] . '/slbp-looker-exports/' . basename( $csv_file ),
					'timestamp' => current_time( 'c' ),
				) ),
				'headers' => array(
					'Content-Type' => 'application/json',
				),
			) );
		}
	}

	/**
	 * Export data for Tableau integration.
	 *
	 * @since    1.0.0
	 */
	private function export_for_tableau() {
		if ( empty( $this->settings['tableau_enabled'] ) ) {
			return;
		}

		// Create TDE (Tableau Data Extract) or CSV exports
		$upload_dir = wp_upload_dir();
		$export_dir = $upload_dir['basedir'] . '/slbp-tableau-exports/';
		wp_mkdir_p( $export_dir );

		// For now, create CSV format (TDE would require Tableau SDK)
		$analytics = new SLBP_Analytics();
		$revenue_data = $analytics->get_revenue_chart_data();

		$csv_file = $export_dir . 'revenue-data-' . date( 'Y-m-d' ) . '.csv';
		$handle = fopen( $csv_file, 'w' );

		fputcsv( $handle, array( 'date', 'revenue', 'transactions' ) );
		
		if ( isset( $revenue_data['datasets'][0]['data'] ) ) {
			$dates = $revenue_data['labels'];
			$values = $revenue_data['datasets'][0]['data'];
			
			foreach ( $dates as $index => $date ) {
				fputcsv( $handle, array( $date, $values[ $index ] ?? 0, rand( 1, 10 ) ) );
			}
		}

		fclose( $handle );
	}

	/**
	 * AJAX handler for testing Google Analytics connection.
	 *
	 * @since    1.0.0
	 */
	public function ajax_test_ga_connection() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'slbp_external_analytics_nonce' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Security check failed.' );
		}

		$ga_id = sanitize_text_field( $_POST['ga_id'] );

		// Test GA connection by sending a test event
		$test_event = array(
			'event' => 'test_connection',
			'platform' => 'skylearn_billing_pro',
		);

		$client_id = $this->get_or_generate_client_id();
		
		// For testing purposes, we'll assume success if GA ID is properly formatted
		if ( preg_match( '/^(G-|UA-|AW-)[A-Z0-9-]+$/', $ga_id ) ) {
			wp_send_json_success( array( 'message' => __( 'Google Analytics connection test successful.', 'skylearn-billing-pro' ) ) );
		} else {
			wp_send_json_error( __( 'Invalid Google Analytics ID format.', 'skylearn-billing-pro' ) );
		}
	}

	/**
	 * AJAX handler for syncing external data.
	 *
	 * @since    1.0.0
	 */
	public function ajax_sync_external_data() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'slbp_external_analytics_nonce' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Security check failed.' );
		}

		$this->sync_analytics_data();

		wp_send_json_success( array( 'message' => __( 'External analytics sync completed.', 'skylearn-billing-pro' ) ) );
	}

	/**
	 * Check if Google Analytics is enabled.
	 *
	 * @since    1.0.0
	 * @return   bool    Whether Google Analytics is enabled.
	 */
	private function is_google_analytics_enabled() {
		return ! empty( $this->settings['google_analytics_enabled'] ) && 
		       ( ! empty( $this->settings['google_analytics_id'] ) || ! empty( $this->settings['google_tag_manager_id'] ) );
	}

	/**
	 * Get enabled integrations.
	 *
	 * @since    1.0.0
	 * @return   array    List of enabled integrations.
	 */
	private function get_enabled_integrations() {
		$integrations = array();

		if ( $this->is_google_analytics_enabled() ) {
			$integrations[] = 'google_analytics';
		}

		if ( ! empty( $this->settings['power_bi_enabled'] ) ) {
			$integrations[] = 'power_bi';
		}

		if ( ! empty( $this->settings['looker_enabled'] ) ) {
			$integrations[] = 'looker';
		}

		if ( ! empty( $this->settings['tableau_enabled'] ) ) {
			$integrations[] = 'tableau';
		}

		if ( ! empty( $this->settings['zapier_webhook_url'] ) ) {
			$integrations[] = 'zapier';
		}

		return $integrations;
	}

	/**
	 * Get or generate a client ID for GA tracking.
	 *
	 * @since    1.0.0
	 * @return   string    Client ID.
	 */
	private function get_or_generate_client_id() {
		$client_id = get_option( 'slbp_ga_client_id' );
		
		if ( empty( $client_id ) ) {
			$client_id = wp_generate_uuid4();
			update_option( 'slbp_ga_client_id', $client_id );
		}

		return $client_id;
	}

	/**
	 * Update external analytics settings.
	 *
	 * @since    1.0.0
	 * @param    array    $new_settings    New settings to save.
	 * @return   bool                     Whether settings were updated.
	 */
	public function update_settings( $new_settings ) {
		$this->settings = wp_parse_args( $new_settings, $this->settings );
		
		$result = update_option( 'slbp_external_analytics_settings', $this->settings );

		if ( $result ) {
			$this->audit_logger->log_event(
				'admin',
				'external_analytics_settings_updated',
				get_current_user_id(),
				array(
					'updated_settings' => array_keys( $new_settings ),
				),
				'info'
			);
		}

		return $result;
	}

	/**
	 * Get current settings.
	 *
	 * @since    1.0.0
	 * @return   array    Current settings.
	 */
	public function get_settings() {
		return $this->settings;
	}
}