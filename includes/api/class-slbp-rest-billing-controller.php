<?php
/**
 * SkyLearn Billing Pro REST Billing Controller
 *
 * Handles billing-related API endpoints.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/api
 */

/**
 * REST Billing Controller Class
 *
 * Provides REST API endpoints for billing operations.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/api
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_REST_Billing_Controller extends WP_REST_Controller {

	/**
	 * API namespace.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $namespace    API namespace.
	 */
	protected $namespace;

	/**
	 * REST base for this controller.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $rest_base    REST base.
	 */
	protected $rest_base = 'billing';

	/**
	 * Initialize the controller.
	 *
	 * @since    1.0.0
	 * @param    string    $namespace    API namespace.
	 */
	public function __construct( $namespace ) {
		$this->namespace = $namespace;
	}

	/**
	 * Register routes for this controller.
	 *
	 * @since    1.0.0
	 */
	public function register_routes() {
		// Get invoices
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/invoices', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_invoices' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'args'                => $this->get_collection_params(),
			),
		) );

		// Get specific invoice
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/invoices/(?P<id>[\w-]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_invoice' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'args'                => array(
					'id' => array(
						'description' => 'Invoice ID',
						'type'        => 'string',
						'required'    => true,
					),
				),
			),
		) );

		// Get transactions
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/transactions', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_transactions' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'args'                => $this->get_collection_params(),
			),
		) );

		// Get specific transaction
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/transactions/(?P<id>[\w-]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_transaction' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'args'                => array(
					'id' => array(
						'description' => 'Transaction ID',
						'type'        => 'string',
						'required'    => true,
					),
				),
			),
		) );

		// Process refund
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/refunds', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_refund' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
				'args'                => array(
					'transaction_id' => array(
						'description' => 'Transaction ID to refund',
						'type'        => 'string',
						'required'    => true,
					),
					'amount' => array(
						'description' => 'Refund amount (optional for partial refund)',
						'type'        => 'number',
						'required'    => false,
					),
					'reason' => array(
						'description' => 'Refund reason',
						'type'        => 'string',
						'required'    => false,
					),
				),
			),
		) );

		// Get payment methods
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/payment-methods', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_payment_methods' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
			),
		) );
	}

	/**
	 * Get invoices.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   WP_REST_Response               Response object.
	 */
	public function get_invoices( $request ) {
		global $wpdb;

		$params = $this->prepare_collection_query( $request );
		$table_name = $wpdb->prefix . 'slbp_transactions';

		// Build WHERE clause
		$where_clauses = array( "status = 'paid'" );
		$where_values = array();

		// Filter by user if not admin
		if ( ! current_user_can( 'manage_options' ) ) {
			$where_clauses[] = 'user_id = %d';
			$where_values[] = get_current_user_id();
		} elseif ( ! empty( $params['user_id'] ) ) {
			$where_clauses[] = 'user_id = %d';
			$where_values[] = $params['user_id'];
		}

		// Date filters
		if ( ! empty( $params['after'] ) ) {
			$where_clauses[] = 'created_at >= %s';
			$where_values[] = $params['after'];
		}

		if ( ! empty( $params['before'] ) ) {
			$where_clauses[] = 'created_at <= %s';
			$where_values[] = $params['before'];
		}

		$where_sql = implode( ' AND ', $where_clauses );

		// Get total count
		$total_items = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table_name WHERE $where_sql",
			$where_values
		) );

		// Get invoices
		$invoices = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE $where_sql ORDER BY created_at DESC LIMIT %d OFFSET %d",
			array_merge( $where_values, array( $params['per_page'], $params['offset'] ) )
		) );

		// Prepare response data
		$data = array();
		foreach ( $invoices as $invoice ) {
			$data[] = $this->prepare_invoice_for_response( $invoice, $request );
		}

		$response = rest_ensure_response( $data );
		$response->header( 'X-Total-Count', $total_items );
		$response->header( 'X-Total-Pages', ceil( $total_items / $params['per_page'] ) );

		return $response;
	}

	/**
	 * Get specific invoice.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   WP_REST_Response               Response object.
	 */
	public function get_invoice( $request ) {
		global $wpdb;

		$invoice_id = $request['id'];
		$table_name = $wpdb->prefix . 'slbp_transactions';

		$invoice = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE order_id = %s OR transaction_id = %s",
			$invoice_id,
			$invoice_id
		) );

		if ( ! $invoice ) {
			return new WP_Error( 'invoice_not_found', 'Invoice not found', array( 'status' => 404 ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) && $invoice->user_id !== get_current_user_id() ) {
			return new WP_Error( 'forbidden', 'Access denied', array( 'status' => 403 ) );
		}

		return rest_ensure_response( $this->prepare_invoice_for_response( $invoice, $request ) );
	}

	/**
	 * Get transactions.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   WP_REST_Response               Response object.
	 */
	public function get_transactions( $request ) {
		global $wpdb;

		$params = $this->prepare_collection_query( $request );
		$table_name = $wpdb->prefix . 'slbp_transactions';

		// Build WHERE clause
		$where_clauses = array( '1=1' );
		$where_values = array();

		// Filter by user if not admin
		if ( ! current_user_can( 'manage_options' ) ) {
			$where_clauses[] = 'user_id = %d';
			$where_values[] = get_current_user_id();
		} elseif ( ! empty( $params['user_id'] ) ) {
			$where_clauses[] = 'user_id = %d';
			$where_values[] = $params['user_id'];
		}

		// Status filter
		if ( ! empty( $params['status'] ) ) {
			$where_clauses[] = 'status = %s';
			$where_values[] = $params['status'];
		}

		// Gateway filter
		if ( ! empty( $params['gateway'] ) ) {
			$where_clauses[] = 'payment_gateway = %s';
			$where_values[] = $params['gateway'];
		}

		$where_sql = implode( ' AND ', $where_clauses );

		// Get total count
		$total_items = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table_name WHERE $where_sql",
			$where_values
		) );

		// Get transactions
		$transactions = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE $where_sql ORDER BY created_at DESC LIMIT %d OFFSET %d",
			array_merge( $where_values, array( $params['per_page'], $params['offset'] ) )
		) );

		// Prepare response data
		$data = array();
		foreach ( $transactions as $transaction ) {
			$data[] = $this->prepare_transaction_for_response( $transaction, $request );
		}

		$response = rest_ensure_response( $data );
		$response->header( 'X-Total-Count', $total_items );
		$response->header( 'X-Total-Pages', ceil( $total_items / $params['per_page'] ) );

		return $response;
	}

	/**
	 * Get specific transaction.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   WP_REST_Response               Response object.
	 */
	public function get_transaction( $request ) {
		global $wpdb;

		$transaction_id = $request['id'];
		$table_name = $wpdb->prefix . 'slbp_transactions';

		$transaction = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE transaction_id = %s OR order_id = %s",
			$transaction_id,
			$transaction_id
		) );

		if ( ! $transaction ) {
			return new WP_Error( 'transaction_not_found', 'Transaction not found', array( 'status' => 404 ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) && $transaction->user_id !== get_current_user_id() ) {
			return new WP_Error( 'forbidden', 'Access denied', array( 'status' => 403 ) );
		}

		return rest_ensure_response( $this->prepare_transaction_for_response( $transaction, $request ) );
	}

	/**
	 * Create refund.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   WP_REST_Response               Response object.
	 */
	public function create_refund( $request ) {
		$transaction_id = $request['transaction_id'];
		$amount = $request['amount'];
		$reason = $request['reason'] ?: 'Requested via API';

		// Get transaction
		global $wpdb;
		$table_name = $wpdb->prefix . 'slbp_transactions';

		$transaction = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE transaction_id = %s",
			$transaction_id
		) );

		if ( ! $transaction ) {
			return new WP_Error( 'transaction_not_found', 'Transaction not found', array( 'status' => 404 ) );
		}

		// Check if transaction can be refunded
		if ( $transaction->status !== 'paid' ) {
			return new WP_Error( 'invalid_transaction', 'Only paid transactions can be refunded', array( 'status' => 400 ) );
		}

		// Process refund through payment gateway
		$plugin = SLBP_Plugin::get_instance();
		$gateway = $plugin->get_payment_gateway( $transaction->payment_gateway );

		if ( ! $gateway ) {
			return new WP_Error( 'gateway_unavailable', 'Payment gateway not available', array( 'status' => 500 ) );
		}

		$refund_result = $gateway->process_refund( $transaction_id, $amount, $reason );

		if ( is_wp_error( $refund_result ) ) {
			return $refund_result;
		}

		return rest_ensure_response( array(
			'success'        => true,
			'refund_id'      => $refund_result['refund_id'],
			'amount'         => $refund_result['amount'],
			'transaction_id' => $transaction_id,
			'reason'         => $reason,
		) );
	}

	/**
	 * Get payment methods.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   WP_REST_Response               Response object.
	 */
	public function get_payment_methods( $request ) {
		$plugin = SLBP_Plugin::get_instance();
		$payment_settings = get_option( 'slbp_payment_settings', array() );

		$methods = array();

		// Check available gateways
		if ( ! empty( $payment_settings['lemon_squeezy_enabled'] ) ) {
			$methods[] = array(
				'id'          => 'lemon_squeezy',
				'name'        => 'Lemon Squeezy',
				'description' => 'Credit card and other payment methods via Lemon Squeezy',
				'enabled'     => true,
			);
		}

		return rest_ensure_response( $methods );
	}

	/**
	 * Prepare invoice for response.
	 *
	 * @since    1.0.0
	 * @param    object             $invoice    Invoice object.
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   array                          Prepared data.
	 */
	protected function prepare_invoice_for_response( $invoice, $request ) {
		$metadata = json_decode( $invoice->metadata, true ) ?: array();

		return array(
			'id'          => $invoice->order_id,
			'user_id'     => (int) $invoice->user_id,
			'amount'      => (float) $invoice->amount,
			'currency'    => $invoice->currency,
			'status'      => $invoice->status,
			'gateway'     => $invoice->payment_gateway,
			'course_id'   => $invoice->course_id ? (int) $invoice->course_id : null,
			'metadata'    => $metadata,
			'created_at'  => $invoice->created_at,
			'updated_at'  => $invoice->updated_at,
		);
	}

	/**
	 * Prepare transaction for response.
	 *
	 * @since    1.0.0
	 * @param    object             $transaction    Transaction object.
	 * @param    WP_REST_Request    $request        Request object.
	 * @return   array                              Prepared data.
	 */
	protected function prepare_transaction_for_response( $transaction, $request ) {
		return $this->prepare_invoice_for_response( $transaction, $request );
	}

	/**
	 * Check read permission.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   bool                           Permission result.
	 */
	public function check_read_permission( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'unauthorized', 'Authentication required', array( 'status' => 401 ) );
		}

		$permissions = $this->get_current_user_permissions();

		return in_array( 'read', $permissions ) || in_array( 'read_billing', $permissions );
	}

	/**
	 * Check write permission.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   bool                           Permission result.
	 */
	public function check_write_permission( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'unauthorized', 'Authentication required', array( 'status' => 401 ) );
		}

		$permissions = $this->get_current_user_permissions();

		return in_array( 'write', $permissions ) || in_array( 'write_billing', $permissions );
	}

	/**
	 * Get current user permissions.
	 *
	 * @since    1.0.0
	 * @return   array    User permissions.
	 */
	protected function get_current_user_permissions() {
		// If admin user, grant all permissions
		if ( current_user_can( 'manage_options' ) ) {
			return array( 'read', 'write', 'admin' );
		}

		// Get permissions from API key if used
		$api_key = $this->get_api_key_from_request();
		if ( $api_key ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'slbp_api_keys';
			
			$key_data = $wpdb->get_row( $wpdb->prepare(
				"SELECT permissions FROM $table_name WHERE api_key = %s AND is_active = 1",
				$api_key
			) );

			if ( $key_data ) {
				return json_decode( $key_data->permissions, true ) ?: array();
			}
		}

		// Default user permissions
		return array( 'read' );
	}

	/**
	 * Get API key from request.
	 *
	 * @since    1.0.0
	 * @return   string|null    API key or null.
	 */
	protected function get_api_key_from_request() {
		// Check Authorization header
		$auth_header = isset( $_SERVER['HTTP_AUTHORIZATION'] ) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
		
		if ( ! empty( $auth_header ) && strpos( $auth_header, 'Bearer ' ) === 0 ) {
			return substr( $auth_header, 7 );
		}

		// Check X-API-Key header
		if ( isset( $_SERVER['HTTP_X_API_KEY'] ) ) {
			return sanitize_text_field( $_SERVER['HTTP_X_API_KEY'] );
		}

		// Check query parameter
		if ( isset( $_GET['api_key'] ) ) {
			return sanitize_text_field( $_GET['api_key'] );
		}

		return null;
	}

	/**
	 * Prepare collection query parameters.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   array                          Query parameters.
	 */
	protected function prepare_collection_query( $request ) {
		$params = array();

		$params['page'] = (int) $request->get_param( 'page' ) ?: 1;
		$params['per_page'] = min( 100, (int) $request->get_param( 'per_page' ) ?: 10 );
		$params['offset'] = ( $params['page'] - 1 ) * $params['per_page'];

		$params['user_id'] = $request->get_param( 'user_id' );
		$params['status'] = $request->get_param( 'status' );
		$params['gateway'] = $request->get_param( 'gateway' );
		$params['after'] = $request->get_param( 'after' );
		$params['before'] = $request->get_param( 'before' );

		return $params;
	}

	/**
	 * Get collection parameters.
	 *
	 * @since    1.0.0
	 * @return   array    Collection parameters.
	 */
	public function get_collection_params() {
		return array(
			'page' => array(
				'description' => 'Current page of the collection',
				'type'        => 'integer',
				'default'     => 1,
				'minimum'     => 1,
			),
			'per_page' => array(
				'description' => 'Maximum number of items to return',
				'type'        => 'integer',
				'default'     => 10,
				'minimum'     => 1,
				'maximum'     => 100,
			),
			'user_id' => array(
				'description' => 'Filter by user ID',
				'type'        => 'integer',
			),
			'status' => array(
				'description' => 'Filter by transaction status',
				'type'        => 'string',
				'enum'        => array( 'pending', 'paid', 'failed', 'refunded' ),
			),
			'gateway' => array(
				'description' => 'Filter by payment gateway',
				'type'        => 'string',
			),
			'after' => array(
				'description' => 'Filter transactions after this date',
				'type'        => 'string',
				'format'      => 'date-time',
			),
			'before' => array(
				'description' => 'Filter transactions before this date',
				'type'        => 'string',
				'format'      => 'date-time',
			),
		);
	}
}