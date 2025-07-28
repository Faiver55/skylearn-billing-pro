<?php
/**
 * Third-party analytics integration for SkyLearn Billing Pro.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/external-integrations
 */

/**
 * Third-party analytics integration class.
 *
 * Handles integration with external analytics services like Google Analytics,
 * Mixpanel, and other analytics platforms.
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/external-integrations
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Third_Party_Analytics {

	/**
	 * Supported analytics providers.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $providers    Supported analytics providers.
	 */
	private $providers = array();

	/**
	 * Active integrations.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $active_integrations    Active integrations.
	 */
	private $active_integrations = array();

	/**
	 * Initialize the class.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->init_providers();
		$this->load_active_integrations();
		$this->init_hooks();
	}

	/**
	 * Initialize supported providers.
	 *
	 * @since    1.0.0
	 */
	private function init_providers() {
		$this->providers = array(
			'google_analytics' => array(
				'name' => __( 'Google Analytics', 'skylearn-billing-pro' ),
				'description' => __( 'Send billing and user events to Google Analytics', 'skylearn-billing-pro' ),
				'fields' => array(
					'tracking_id' => array(
						'label' => __( 'Tracking ID', 'skylearn-billing-pro' ),
						'type' => 'text',
						'required' => true,
					),
					'enhanced_ecommerce' => array(
						'label' => __( 'Enable Enhanced Ecommerce', 'skylearn-billing-pro' ),
						'type' => 'checkbox',
						'default' => true,
					),
				),
			),
			'google_analytics_4' => array(
				'name' => __( 'Google Analytics 4', 'skylearn-billing-pro' ),
				'description' => __( 'Send events to Google Analytics 4 (GA4)', 'skylearn-billing-pro' ),
				'fields' => array(
					'measurement_id' => array(
						'label' => __( 'Measurement ID', 'skylearn-billing-pro' ),
						'type' => 'text',
						'required' => true,
					),
					'api_secret' => array(
						'label' => __( 'API Secret', 'skylearn-billing-pro' ),
						'type' => 'password',
						'required' => true,
					),
				),
			),
			'mixpanel' => array(
				'name' => __( 'Mixpanel', 'skylearn-billing-pro' ),
				'description' => __( 'Track user events and revenue in Mixpanel', 'skylearn-billing-pro' ),
				'fields' => array(
					'project_token' => array(
						'label' => __( 'Project Token', 'skylearn-billing-pro' ),
						'type' => 'text',
						'required' => true,
					),
					'api_secret' => array(
						'label' => __( 'API Secret', 'skylearn-billing-pro' ),
						'type' => 'password',
						'required' => false,
					),
				),
			),
			'segment' => array(
				'name' => __( 'Segment', 'skylearn-billing-pro' ),
				'description' => __( 'Send data to Segment for multi-platform analytics', 'skylearn-billing-pro' ),
				'fields' => array(
					'write_key' => array(
						'label' => __( 'Write Key', 'skylearn-billing-pro' ),
						'type' => 'password',
						'required' => true,
					),
				),
			),
			'facebook_pixel' => array(
				'name' => __( 'Facebook Pixel', 'skylearn-billing-pro' ),
				'description' => __( 'Track conversions with Facebook Pixel', 'skylearn-billing-pro' ),
				'fields' => array(
					'pixel_id' => array(
						'label' => __( 'Pixel ID', 'skylearn-billing-pro' ),
						'type' => 'text',
						'required' => true,
					),
					'access_token' => array(
						'label' => __( 'Access Token', 'skylearn-billing-pro' ),
						'type' => 'password',
						'required' => false,
					),
				),
			),
			'custom_webhook' => array(
				'name' => __( 'Custom Webhook', 'skylearn-billing-pro' ),
				'description' => __( 'Send data to custom webhook endpoints', 'skylearn-billing-pro' ),
				'fields' => array(
					'webhook_url' => array(
						'label' => __( 'Webhook URL', 'skylearn-billing-pro' ),
						'type' => 'url',
						'required' => true,
					),
					'secret_key' => array(
						'label' => __( 'Secret Key', 'skylearn-billing-pro' ),
						'type' => 'password',
						'required' => false,
					),
					'headers' => array(
						'label' => __( 'Custom Headers (JSON)', 'skylearn-billing-pro' ),
						'type' => 'textarea',
						'required' => false,
					),
				),
			),
		);

		// Allow custom providers via filter
		$this->providers = apply_filters( 'slbp_analytics_providers', $this->providers );
	}

	/**
	 * Load active integrations from options.
	 *
	 * @since    1.0.0
	 */
	private function load_active_integrations() {
		$this->active_integrations = get_option( 'slbp_analytics_integrations', array() );
	}

	/**
	 * Initialize hooks.
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		// Track billing events
		add_action( 'slbp_payment_completed', array( $this, 'track_purchase' ), 10, 2 );
		add_action( 'slbp_payment_refunded', array( $this, 'track_refund' ), 10, 2 );
		add_action( 'slbp_subscription_created', array( $this, 'track_subscription_start' ), 10, 2 );
		add_action( 'slbp_subscription_cancelled', array( $this, 'track_subscription_cancel' ), 10, 2 );
		add_action( 'slbp_subscription_renewed', array( $this, 'track_subscription_renewal' ), 10, 2 );

		// Track user events
		add_action( 'slbp_user_enrolled', array( $this, 'track_enrollment' ), 10, 3 );
		add_action( 'slbp_course_completed', array( $this, 'track_course_completion' ), 10, 3 );
		add_action( 'wp_login', array( $this, 'track_user_login' ), 10, 2 );

		// Track admin events
		add_action( 'slbp_report_generated', array( $this, 'track_report_generation' ), 10, 2 );
		add_action( 'slbp_export_completed', array( $this, 'track_data_export' ), 10, 2 );

		// Frontend tracking scripts
		add_action( 'wp_head', array( $this, 'output_tracking_scripts' ) );
		add_action( 'wp_footer', array( $this, 'output_tracking_events' ) );
	}

	/**
	 * Track purchase event.
	 *
	 * @since    1.0.0
	 * @param    int      $transaction_id    Transaction ID.
	 * @param    array    $transaction_data  Transaction data.
	 */
	public function track_purchase( $transaction_id, $transaction_data ) {
		$event_data = array(
			'event' => 'purchase',
			'transaction_id' => $transaction_id,
			'amount' => $transaction_data['amount'],
			'currency' => $transaction_data['currency'],
			'gateway' => $transaction_data['gateway'],
			'user_id' => $transaction_data['user_id'],
			'timestamp' => current_time( 'timestamp' ),
		);

		$this->send_to_all_providers( $event_data );
	}

	/**
	 * Track refund event.
	 *
	 * @since    1.0.0
	 * @param    int      $transaction_id    Transaction ID.
	 * @param    array    $refund_data       Refund data.
	 */
	public function track_refund( $transaction_id, $refund_data ) {
		$event_data = array(
			'event' => 'refund',
			'transaction_id' => $transaction_id,
			'amount' => $refund_data['amount'],
			'currency' => $refund_data['currency'],
			'reason' => $refund_data['reason'],
			'user_id' => $refund_data['user_id'],
			'timestamp' => current_time( 'timestamp' ),
		);

		$this->send_to_all_providers( $event_data );
	}

	/**
	 * Track subscription start.
	 *
	 * @since    1.0.0
	 * @param    int      $subscription_id    Subscription ID.
	 * @param    array    $subscription_data  Subscription data.
	 */
	public function track_subscription_start( $subscription_id, $subscription_data ) {
		$event_data = array(
			'event' => 'subscription_start',
			'subscription_id' => $subscription_id,
			'plan_name' => $subscription_data['plan_name'],
			'amount' => $subscription_data['amount'],
			'currency' => $subscription_data['currency'],
			'user_id' => $subscription_data['user_id'],
			'timestamp' => current_time( 'timestamp' ),
		);

		$this->send_to_all_providers( $event_data );
	}

	/**
	 * Track course enrollment.
	 *
	 * @since    1.0.0
	 * @param    int    $user_id     User ID.
	 * @param    int    $course_id   Course ID.
	 * @param    array  $enrollment_data  Enrollment data.
	 */
	public function track_enrollment( $user_id, $course_id, $enrollment_data ) {
		$event_data = array(
			'event' => 'course_enrollment',
			'user_id' => $user_id,
			'course_id' => $course_id,
			'course_title' => get_the_title( $course_id ),
			'enrollment_method' => $enrollment_data['method'] ?? 'manual',
			'timestamp' => current_time( 'timestamp' ),
		);

		$this->send_to_all_providers( $event_data );
	}

	/**
	 * Send event data to all active providers.
	 *
	 * @since    1.0.0
	 * @param    array    $event_data    Event data to send.
	 */
	private function send_to_all_providers( $event_data ) {
		foreach ( $this->active_integrations as $provider_id => $config ) {
			if ( ! $config['enabled'] ) {
				continue;
			}

			$this->send_to_provider( $provider_id, $event_data, $config );
		}
	}

	/**
	 * Send event data to specific provider.
	 *
	 * @since    1.0.0
	 * @param    string    $provider_id    Provider ID.
	 * @param    array     $event_data     Event data.
	 * @param    array     $config         Provider configuration.
	 */
	private function send_to_provider( $provider_id, $event_data, $config ) {
		switch ( $provider_id ) {
			case 'google_analytics':
				$this->send_to_google_analytics( $event_data, $config );
				break;
			case 'google_analytics_4':
				$this->send_to_google_analytics_4( $event_data, $config );
				break;
			case 'mixpanel':
				$this->send_to_mixpanel( $event_data, $config );
				break;
			case 'segment':
				$this->send_to_segment( $event_data, $config );
				break;
			case 'facebook_pixel':
				$this->send_to_facebook_pixel( $event_data, $config );
				break;
			case 'custom_webhook':
				$this->send_to_custom_webhook( $event_data, $config );
				break;
			default:
				// Allow custom providers
				do_action( "slbp_send_to_{$provider_id}", $event_data, $config );
		}
	}

	/**
	 * Send to Google Analytics (Universal Analytics).
	 *
	 * @since    1.0.0
	 * @param    array    $event_data    Event data.
	 * @param    array    $config        Provider configuration.
	 */
	private function send_to_google_analytics( $event_data, $config ) {
		if ( empty( $config['tracking_id'] ) ) {
			return;
		}

		// Use Measurement Protocol for server-side tracking
		$measurement_url = 'https://www.google-analytics.com/collect';
		
		$payload = array(
			'v' => '1',
			'tid' => $config['tracking_id'],
			'cid' => $this->get_client_id( $event_data['user_id'] ),
			't' => 'event',
			'ec' => 'billing',
			'ea' => $event_data['event'],
		);

		if ( isset( $event_data['amount'] ) ) {
			$payload['tr'] = $event_data['amount'];
		}

		$this->send_http_request( $measurement_url, $payload );
	}

	/**
	 * Send to Google Analytics 4.
	 *
	 * @since    1.0.0
	 * @param    array    $event_data    Event data.
	 * @param    array    $config        Provider configuration.
	 */
	private function send_to_google_analytics_4( $event_data, $config ) {
		if ( empty( $config['measurement_id'] ) || empty( $config['api_secret'] ) ) {
			return;
		}

		$measurement_url = 'https://www.google-analytics.com/mp/collect?' . http_build_query( array(
			'measurement_id' => $config['measurement_id'],
			'api_secret' => $config['api_secret'],
		) );

		$payload = array(
			'client_id' => $this->get_client_id( $event_data['user_id'] ),
			'events' => array(
				array(
					'name' => $event_data['event'],
					'parameters' => array(
						'currency' => $event_data['currency'] ?? 'USD',
						'value' => $event_data['amount'] ?? 0,
						'transaction_id' => $event_data['transaction_id'] ?? '',
					),
				),
			),
		);

		$this->send_http_request( $measurement_url, $payload, 'POST', 'application/json' );
	}

	/**
	 * Send to Mixpanel.
	 *
	 * @since    1.0.0
	 * @param    array    $event_data    Event data.
	 * @param    array    $config        Provider configuration.
	 */
	private function send_to_mixpanel( $event_data, $config ) {
		if ( empty( $config['project_token'] ) ) {
			return;
		}

		$mixpanel_url = 'https://api.mixpanel.com/track/';

		$payload = array(
			'event' => $event_data['event'],
			'properties' => array_merge( $event_data, array(
				'token' => $config['project_token'],
				'distinct_id' => $event_data['user_id'],
				'time' => $event_data['timestamp'],
			) ),
		);

		$encoded_payload = base64_encode( wp_json_encode( $payload ) );
		
		$this->send_http_request( $mixpanel_url, array( 'data' => $encoded_payload ) );
	}

	/**
	 * Send to Segment.
	 *
	 * @since    1.0.0
	 * @param    array    $event_data    Event data.
	 * @param    array    $config        Provider configuration.
	 */
	private function send_to_segment( $event_data, $config ) {
		if ( empty( $config['write_key'] ) ) {
			return;
		}

		$segment_url = 'https://api.segment.io/v1/track';

		$payload = array(
			'userId' => $event_data['user_id'],
			'event' => $event_data['event'],
			'properties' => $event_data,
			'timestamp' => date( 'c', $event_data['timestamp'] ),
		);

		$headers = array(
			'Authorization' => 'Basic ' . base64_encode( $config['write_key'] . ':' ),
		);

		$this->send_http_request( $segment_url, $payload, 'POST', 'application/json', $headers );
	}

	/**
	 * Send to Facebook Pixel.
	 *
	 * @since    1.0.0
	 * @param    array    $event_data    Event data.
	 * @param    array    $config        Provider configuration.
	 */
	private function send_to_facebook_pixel( $event_data, $config ) {
		if ( empty( $config['pixel_id'] ) ) {
			return;
		}

		// Store event data for frontend tracking
		$pixel_events = get_option( 'slbp_pixel_events', array() );
		$pixel_events[] = array(
			'pixel_id' => $config['pixel_id'],
			'event' => $this->map_event_to_facebook( $event_data['event'] ),
			'data' => $event_data,
		);
		update_option( 'slbp_pixel_events', $pixel_events );
	}

	/**
	 * Send to custom webhook.
	 *
	 * @since    1.0.0
	 * @param    array    $event_data    Event data.
	 * @param    array    $config        Provider configuration.
	 */
	private function send_to_custom_webhook( $event_data, $config ) {
		if ( empty( $config['webhook_url'] ) ) {
			return;
		}

		$headers = array();
		
		if ( ! empty( $config['secret_key'] ) ) {
			$headers['X-SLBP-Signature'] = hash_hmac( 'sha256', wp_json_encode( $event_data ), $config['secret_key'] );
		}

		if ( ! empty( $config['headers'] ) ) {
			$custom_headers = json_decode( $config['headers'], true );
			if ( is_array( $custom_headers ) ) {
				$headers = array_merge( $headers, $custom_headers );
			}
		}

		$this->send_http_request( $config['webhook_url'], $event_data, 'POST', 'application/json', $headers );
	}

	/**
	 * Send HTTP request.
	 *
	 * @since    1.0.0
	 * @param    string    $url         Request URL.
	 * @param    array     $data        Request data.
	 * @param    string    $method      HTTP method.
	 * @param    string    $content_type Content type.
	 * @param    array     $headers     Additional headers.
	 */
	private function send_http_request( $url, $data, $method = 'POST', $content_type = 'application/x-www-form-urlencoded', $headers = array() ) {
		$args = array(
			'method' => $method,
			'timeout' => 30,
			'headers' => array_merge( array(
				'Content-Type' => $content_type,
				'User-Agent' => 'SkyLearn-Billing-Pro/1.0',
			), $headers ),
		);

		if ( 'application/json' === $content_type ) {
			$args['body'] = wp_json_encode( $data );
		} else {
			$args['body'] = $data;
		}

		$response = wp_remote_request( $url, $args );

		// Log errors for debugging
		if ( is_wp_error( $response ) ) {
			error_log( 'SLBP Analytics Integration Error: ' . $response->get_error_message() );
		}
	}

	/**
	 * Get client ID for tracking.
	 *
	 * @since    1.0.0
	 * @param    int    $user_id    User ID.
	 * @return   string             Client ID.
	 */
	private function get_client_id( $user_id ) {
		$client_id = get_user_meta( $user_id, 'slbp_analytics_client_id', true );
		
		if ( empty( $client_id ) ) {
			$client_id = wp_generate_uuid4();
			update_user_meta( $user_id, 'slbp_analytics_client_id', $client_id );
		}

		return $client_id;
	}

	/**
	 * Map event to Facebook Pixel event name.
	 *
	 * @since    1.0.0
	 * @param    string    $event    Internal event name.
	 * @return   string             Facebook Pixel event name.
	 */
	private function map_event_to_facebook( $event ) {
		$mapping = array(
			'purchase' => 'Purchase',
			'subscription_start' => 'Subscribe',
			'course_enrollment' => 'InitiateCheckout',
			'refund' => 'Refund',
		);

		return $mapping[ $event ] ?? 'CustomEvent';
	}

	/**
	 * Output tracking scripts in head.
	 *
	 * @since    1.0.0
	 */
	public function output_tracking_scripts() {
		foreach ( $this->active_integrations as $provider_id => $config ) {
			if ( ! $config['enabled'] ) {
				continue;
			}

			switch ( $provider_id ) {
				case 'google_analytics':
					$this->output_google_analytics_script( $config );
					break;
				case 'google_analytics_4':
					$this->output_google_analytics_4_script( $config );
					break;
				case 'facebook_pixel':
					$this->output_facebook_pixel_script( $config );
					break;
			}
		}
	}

	/**
	 * Output Google Analytics script.
	 *
	 * @since    1.0.0
	 * @param    array    $config    Configuration.
	 */
	private function output_google_analytics_script( $config ) {
		if ( empty( $config['tracking_id'] ) ) {
			return;
		}

		echo "
		<script async src='https://www.googletagmanager.com/gtag/js?id={$config['tracking_id']}'></script>
		<script>
			window.dataLayer = window.dataLayer || [];
			function gtag(){dataLayer.push(arguments);}
			gtag('js', new Date());
			gtag('config', '{$config['tracking_id']}');
		</script>
		";
	}

	/**
	 * Output tracking events in footer.
	 *
	 * @since    1.0.0
	 */
	public function output_tracking_events() {
		// Output any pending Facebook Pixel events
		$pixel_events = get_option( 'slbp_pixel_events', array() );
		if ( ! empty( $pixel_events ) ) {
			foreach ( $pixel_events as $event ) {
				echo "<script>fbq('track', '{$event['event']}', " . wp_json_encode( $event['data'] ) . ");</script>";
			}
			delete_option( 'slbp_pixel_events' );
		}
	}

	/**
	 * Get available providers.
	 *
	 * @since    1.0.0
	 * @return   array    Available providers.
	 */
	public function get_providers() {
		return $this->providers;
	}

	/**
	 * Save integration configuration.
	 *
	 * @since    1.0.0
	 * @param    string    $provider_id    Provider ID.
	 * @param    array     $config         Configuration data.
	 * @return   bool                     Success status.
	 */
	public function save_integration( $provider_id, $config ) {
		$this->active_integrations[ $provider_id ] = $config;
		return update_option( 'slbp_analytics_integrations', $this->active_integrations );
	}

	/**
	 * Remove integration.
	 *
	 * @since    1.0.0
	 * @param    string    $provider_id    Provider ID.
	 * @return   bool                     Success status.
	 */
	public function remove_integration( $provider_id ) {
		unset( $this->active_integrations[ $provider_id ] );
		return update_option( 'slbp_analytics_integrations', $this->active_integrations );
	}

	/**
	 * Additional tracking methods that were referenced but not defined.
	 */
	public function track_subscription_cancel( $subscription_id, $subscription_data ) {
		$event_data = array(
			'event' => 'subscription_cancel',
			'subscription_id' => $subscription_id,
			'plan_name' => $subscription_data['plan_name'],
			'user_id' => $subscription_data['user_id'],
			'timestamp' => current_time( 'timestamp' ),
		);
		$this->send_to_all_providers( $event_data );
	}

	public function track_subscription_renewal( $subscription_id, $subscription_data ) {
		$event_data = array(
			'event' => 'subscription_renewal',
			'subscription_id' => $subscription_id,
			'plan_name' => $subscription_data['plan_name'],
			'amount' => $subscription_data['amount'],
			'user_id' => $subscription_data['user_id'],
			'timestamp' => current_time( 'timestamp' ),
		);
		$this->send_to_all_providers( $event_data );
	}

	public function track_course_completion( $user_id, $course_id, $completion_data ) {
		$event_data = array(
			'event' => 'course_completion',
			'user_id' => $user_id,
			'course_id' => $course_id,
			'course_title' => get_the_title( $course_id ),
			'completion_time' => $completion_data['completion_time'] ?? 0,
			'timestamp' => current_time( 'timestamp' ),
		);
		$this->send_to_all_providers( $event_data );
	}

	public function track_user_login( $user_login, $user ) {
		$event_data = array(
			'event' => 'user_login',
			'user_id' => $user->ID,
			'user_login' => $user_login,
			'timestamp' => current_time( 'timestamp' ),
		);
		$this->send_to_all_providers( $event_data );
	}

	public function track_report_generation( $report_type, $user_id ) {
		$event_data = array(
			'event' => 'report_generated',
			'report_type' => $report_type,
			'user_id' => $user_id,
			'timestamp' => current_time( 'timestamp' ),
		);
		$this->send_to_all_providers( $event_data );
	}

	public function track_data_export( $export_type, $user_id ) {
		$event_data = array(
			'event' => 'data_export',
			'export_type' => $export_type,
			'user_id' => $user_id,
			'timestamp' => current_time( 'timestamp' ),
		);
		$this->send_to_all_providers( $event_data );
	}

	private function output_google_analytics_4_script( $config ) {
		if ( empty( $config['measurement_id'] ) ) {
			return;
		}

		echo "
		<script async src='https://www.googletagmanager.com/gtag/js?id={$config['measurement_id']}'></script>
		<script>
			window.dataLayer = window.dataLayer || [];
			function gtag(){dataLayer.push(arguments);}
			gtag('js', new Date());
			gtag('config', '{$config['measurement_id']}');
		</script>
		";
	}

	private function output_facebook_pixel_script( $config ) {
		if ( empty( $config['pixel_id'] ) ) {
			return;
		}

		echo "
		<script>
			!function(f,b,e,v,n,t,s)
			{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
			n.callMethod.apply(n,arguments):n.queue.push(arguments)};
			if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
			n.queue=[];t=b.createElement(e);t.async=!0;
			t.src=v;s=b.getElementsByTagName(e)[0];
			s.parentNode.insertBefore(t,s)}(window, document,'script',
			'https://connect.facebook.net/en_US/fbevents.js');
			fbq('init', '{$config['pixel_id']}');
			fbq('track', 'PageView');
		</script>
		<noscript>
			<img height='1' width='1' style='display:none'
			src='https://www.facebook.com/tr?id={$config['pixel_id']}&ev=PageView&noscript=1'/>
		</noscript>
		";
	}
}