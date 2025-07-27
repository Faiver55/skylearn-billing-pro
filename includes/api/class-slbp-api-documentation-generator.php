<?php
/**
 * SkyLearn Billing Pro API Documentation Generator
 *
 * Generates OpenAPI/Swagger documentation for the REST API.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/api
 */

/**
 * API Documentation Generator Class
 *
 * Generates OpenAPI 3.0 specification for the REST API.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/api
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_API_Documentation_Generator {

	/**
	 * Generate OpenAPI specification.
	 *
	 * @since    1.0.0
	 * @return   array    OpenAPI specification.
	 */
	public function generate_openapi_spec() {
		$spec = array(
			'openapi'    => '3.0.0',
			'info'       => $this->get_api_info(),
			'servers'    => $this->get_servers(),
			'paths'      => $this->get_paths(),
			'components' => $this->get_components(),
			'security'   => $this->get_security(),
		);

		return $spec;
	}

	/**
	 * Get API information.
	 *
	 * @since    1.0.0
	 * @return   array    API information.
	 */
	private function get_api_info() {
		return array(
			'title'       => 'SkyLearn Billing Pro API',
			'description' => 'Professional LearnDash billing management API with multiple payment gateway support.',
			'version'     => '1.0.0',
			'contact'     => array(
				'name'  => 'Skyian LLC',
				'url'   => 'https://skyianllc.com',
				'email' => 'contact@skyianllc.com',
			),
			'license'     => array(
				'name' => 'GPL v2 or later',
				'url'  => 'http://www.gnu.org/licenses/gpl-2.0.txt',
			),
		);
	}

	/**
	 * Get server configurations.
	 *
	 * @since    1.0.0
	 * @return   array    Server configurations.
	 */
	private function get_servers() {
		return array(
			array(
				'url'         => home_url( '/wp-json/slbp/v1' ),
				'description' => 'Production API Server',
			),
		);
	}

	/**
	 * Get API paths.
	 *
	 * @since    1.0.0
	 * @return   array    API paths.
	 */
	private function get_paths() {
		return array(
			'/status' => array(
				'get' => array(
					'summary'     => 'Get API status',
					'description' => 'Returns the current status of the API',
					'tags'        => array( 'General' ),
					'responses'   => array(
						'200' => array(
							'description' => 'API status information',
							'content'     => array(
								'application/json' => array(
									'schema' => array(
										'$ref' => '#/components/schemas/ApiStatus'
									),
								),
							),
						),
					),
				),
			),
			'/auth' => array(
				'post' => array(
					'summary'     => 'Authenticate API key',
					'description' => 'Validate an API key and return authentication information',
					'tags'        => array( 'Authentication' ),
					'requestBody' => array(
						'required' => true,
						'content'  => array(
							'application/json' => array(
								'schema' => array(
									'type'       => 'object',
									'properties' => array(
										'api_key' => array(
											'type'        => 'string',
											'description' => 'API key to validate',
										),
									),
									'required' => array( 'api_key' ),
								),
							),
						),
					),
					'responses'   => array(
						'200' => array(
							'description' => 'Authentication successful',
							'content'     => array(
								'application/json' => array(
									'schema' => array(
										'$ref' => '#/components/schemas/AuthResponse'
									),
								),
							),
						),
						'401' => array(
							'description' => 'Invalid API key',
							'content'     => array(
								'application/json' => array(
									'schema' => array(
										'$ref' => '#/components/schemas/Error'
									),
								),
							),
						),
					),
				),
			),
			'/billing/invoices' => array(
				'get' => array(
					'summary'     => 'List invoices',
					'description' => 'Get a list of invoices',
					'tags'        => array( 'Billing' ),
					'parameters'  => array(
						array(
							'name'        => 'page',
							'in'          => 'query',
							'description' => 'Page number',
							'schema'      => array(
								'type'    => 'integer',
								'default' => 1,
								'minimum' => 1,
							),
						),
						array(
							'name'        => 'per_page',
							'in'          => 'query',
							'description' => 'Number of items per page',
							'schema'      => array(
								'type'    => 'integer',
								'default' => 10,
								'minimum' => 1,
								'maximum' => 100,
							),
						),
						array(
							'name'        => 'user_id',
							'in'          => 'query',
							'description' => 'Filter by user ID',
							'schema'      => array( 'type' => 'integer' ),
						),
						array(
							'name'        => 'status',
							'in'          => 'query',
							'description' => 'Filter by invoice status',
							'schema'      => array(
								'type' => 'string',
								'enum' => array( 'pending', 'paid', 'failed', 'refunded' ),
							),
						),
					),
					'responses'   => array(
						'200' => array(
							'description' => 'List of invoices',
							'content'     => array(
								'application/json' => array(
									'schema' => array(
										'type'  => 'array',
										'items' => array(
											'$ref' => '#/components/schemas/Invoice'
										),
									),
								),
							),
							'headers'     => array(
								'X-Total-Count' => array(
									'description' => 'Total number of invoices',
									'schema'      => array( 'type' => 'integer' ),
								),
								'X-Total-Pages' => array(
									'description' => 'Total number of pages',
									'schema'      => array( 'type' => 'integer' ),
								),
							),
						),
						'401' => array(
							'description' => 'Unauthorized',
							'content'     => array(
								'application/json' => array(
									'schema' => array(
										'$ref' => '#/components/schemas/Error'
									),
								),
							),
						),
					),
					'security'    => array(
						array( 'ApiKeyAuth' => array() ),
						array( 'BearerAuth' => array() ),
					),
				),
			),
			'/billing/invoices/{id}' => array(
				'get' => array(
					'summary'     => 'Get invoice',
					'description' => 'Get a specific invoice by ID',
					'tags'        => array( 'Billing' ),
					'parameters'  => array(
						array(
							'name'        => 'id',
							'in'          => 'path',
							'description' => 'Invoice ID',
							'required'    => true,
							'schema'      => array( 'type' => 'string' ),
						),
					),
					'responses'   => array(
						'200' => array(
							'description' => 'Invoice details',
							'content'     => array(
								'application/json' => array(
									'schema' => array(
										'$ref' => '#/components/schemas/Invoice'
									),
								),
							),
						),
						'404' => array(
							'description' => 'Invoice not found',
							'content'     => array(
								'application/json' => array(
									'schema' => array(
										'$ref' => '#/components/schemas/Error'
									),
								),
							),
						),
					),
					'security'    => array(
						array( 'ApiKeyAuth' => array() ),
						array( 'BearerAuth' => array() ),
					),
				),
			),
			'/billing/transactions' => array(
				'get' => array(
					'summary'     => 'List transactions',
					'description' => 'Get a list of transactions',
					'tags'        => array( 'Billing' ),
					'parameters'  => array(
						array(
							'name'        => 'page',
							'in'          => 'query',
							'description' => 'Page number',
							'schema'      => array(
								'type'    => 'integer',
								'default' => 1,
								'minimum' => 1,
							),
						),
						array(
							'name'        => 'per_page',
							'in'          => 'query',
							'description' => 'Number of items per page',
							'schema'      => array(
								'type'    => 'integer',
								'default' => 10,
								'minimum' => 1,
								'maximum' => 100,
							),
						),
					),
					'responses'   => array(
						'200' => array(
							'description' => 'List of transactions',
							'content'     => array(
								'application/json' => array(
									'schema' => array(
										'type'  => 'array',
										'items' => array(
											'$ref' => '#/components/schemas/Transaction'
										),
									),
								),
							),
						),
					),
					'security'    => array(
						array( 'ApiKeyAuth' => array() ),
						array( 'BearerAuth' => array() ),
					),
				),
			),
			'/billing/refunds' => array(
				'post' => array(
					'summary'     => 'Process refund',
					'description' => 'Process a refund for a transaction',
					'tags'        => array( 'Billing' ),
					'requestBody' => array(
						'required' => true,
						'content'  => array(
							'application/json' => array(
								'schema' => array(
									'type'       => 'object',
									'properties' => array(
										'transaction_id' => array(
											'type'        => 'string',
											'description' => 'Transaction ID to refund',
										),
										'amount' => array(
											'type'        => 'number',
											'description' => 'Refund amount (optional for partial refund)',
										),
										'reason' => array(
											'type'        => 'string',
											'description' => 'Refund reason',
										),
									),
									'required' => array( 'transaction_id' ),
								),
							),
						),
					),
					'responses'   => array(
						'200' => array(
							'description' => 'Refund processed successfully',
							'content'     => array(
								'application/json' => array(
									'schema' => array(
										'$ref' => '#/components/schemas/RefundResponse'
									),
								),
							),
						),
						'400' => array(
							'description' => 'Invalid request',
							'content'     => array(
								'application/json' => array(
									'schema' => array(
										'$ref' => '#/components/schemas/Error'
									),
								),
							),
						),
					),
					'security'    => array(
						array( 'ApiKeyAuth' => array() ),
						array( 'BearerAuth' => array() ),
					),
				),
			),
			'/subscriptions' => array(
				'get' => array(
					'summary'     => 'List subscriptions',
					'description' => 'Get a list of subscriptions',
					'tags'        => array( 'Subscriptions' ),
					'responses'   => array(
						'200' => array(
							'description' => 'List of subscriptions',
							'content'     => array(
								'application/json' => array(
									'schema' => array(
										'type'  => 'array',
										'items' => array(
											'$ref' => '#/components/schemas/Subscription'
										),
									),
								),
							),
						),
					),
					'security'    => array(
						array( 'ApiKeyAuth' => array() ),
						array( 'BearerAuth' => array() ),
					),
				),
			),
		);
	}

	/**
	 * Get API components.
	 *
	 * @since    1.0.0
	 * @return   array    API components.
	 */
	private function get_components() {
		return array(
			'schemas' => array(
				'ApiStatus' => array(
					'type'       => 'object',
					'properties' => array(
						'status'    => array( 'type' => 'string', 'example' => 'active' ),
						'version'   => array( 'type' => 'string', 'example' => '1.0' ),
						'endpoints' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
						'timestamp' => array( 'type' => 'integer', 'example' => 1640995200 ),
					),
				),
				'AuthResponse' => array(
					'type'       => 'object',
					'properties' => array(
						'authenticated' => array( 'type' => 'boolean' ),
						'user_id'       => array( 'type' => 'integer' ),
						'permissions'   => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
					),
				),
				'Invoice' => array(
					'type'       => 'object',
					'properties' => array(
						'id'         => array( 'type' => 'string', 'example' => 'inv_123456' ),
						'user_id'    => array( 'type' => 'integer', 'example' => 1 ),
						'amount'     => array( 'type' => 'number', 'format' => 'float', 'example' => 29.99 ),
						'currency'   => array( 'type' => 'string', 'example' => 'USD' ),
						'status'     => array( 'type' => 'string', 'enum' => array( 'pending', 'paid', 'failed', 'refunded' ) ),
						'gateway'    => array( 'type' => 'string', 'example' => 'lemon_squeezy' ),
						'course_id'  => array( 'type' => 'integer', 'nullable' => true ),
						'metadata'   => array( 'type' => 'object' ),
						'created_at' => array( 'type' => 'string', 'format' => 'date-time' ),
						'updated_at' => array( 'type' => 'string', 'format' => 'date-time' ),
					),
				),
				'Transaction' => array(
					'type'       => 'object',
					'properties' => array(
						'id'         => array( 'type' => 'string' ),
						'user_id'    => array( 'type' => 'integer' ),
						'amount'     => array( 'type' => 'number', 'format' => 'float' ),
						'currency'   => array( 'type' => 'string' ),
						'status'     => array( 'type' => 'string' ),
						'gateway'    => array( 'type' => 'string' ),
						'course_id'  => array( 'type' => 'integer', 'nullable' => true ),
						'metadata'   => array( 'type' => 'object' ),
						'created_at' => array( 'type' => 'string', 'format' => 'date-time' ),
						'updated_at' => array( 'type' => 'string', 'format' => 'date-time' ),
					),
				),
				'Subscription' => array(
					'type'       => 'object',
					'properties' => array(
						'id'                => array( 'type' => 'string' ),
						'user_id'           => array( 'type' => 'integer' ),
						'plan_id'           => array( 'type' => 'string' ),
						'status'            => array( 'type' => 'string' ),
						'amount'            => array( 'type' => 'number', 'format' => 'float' ),
						'currency'          => array( 'type' => 'string' ),
						'billing_cycle'     => array( 'type' => 'string' ),
						'next_billing_date' => array( 'type' => 'string', 'format' => 'date-time', 'nullable' => true ),
						'created_at'        => array( 'type' => 'string', 'format' => 'date-time' ),
						'updated_at'        => array( 'type' => 'string', 'format' => 'date-time' ),
					),
				),
				'RefundResponse' => array(
					'type'       => 'object',
					'properties' => array(
						'success'        => array( 'type' => 'boolean' ),
						'refund_id'      => array( 'type' => 'string' ),
						'amount'         => array( 'type' => 'number', 'format' => 'float' ),
						'transaction_id' => array( 'type' => 'string' ),
						'reason'         => array( 'type' => 'string' ),
					),
				),
				'Error' => array(
					'type'       => 'object',
					'properties' => array(
						'code'    => array( 'type' => 'string' ),
						'message' => array( 'type' => 'string' ),
						'data'    => array( 'type' => 'object', 'nullable' => true ),
					),
				),
			),
			'securitySchemes' => array(
				'ApiKeyAuth' => array(
					'type' => 'apiKey',
					'in'   => 'header',
					'name' => 'X-API-Key',
				),
				'BearerAuth' => array(
					'type'   => 'http',
					'scheme' => 'bearer',
				),
			),
		);
	}

	/**
	 * Get security requirements.
	 *
	 * @since    1.0.0
	 * @return   array    Security requirements.
	 */
	private function get_security() {
		return array(
			array( 'ApiKeyAuth' => array() ),
			array( 'BearerAuth' => array() ),
		);
	}

	/**
	 * Generate Swagger UI HTML.
	 *
	 * @since    1.0.0
	 * @return   string    Swagger UI HTML.
	 */
	public function generate_swagger_ui() {
		$spec_url = home_url( '/wp-json/slbp/v1/docs/openapi.json' );
		
		$html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SkyLearn Billing Pro API Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@3.52.5/swagger-ui.css" />
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }
        *, *:before, *:after {
            box-sizing: inherit;
        }
        body {
            margin:0;
            background: #fafafa;
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@3.52.5/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@3.52.5/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: "' . esc_url( $spec_url ) . '",
                dom_id: "#swagger-ui",
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout"
            });
        };
    </script>
</body>
</html>';

		return $html;
	}

	/**
	 * Register documentation endpoints.
	 *
	 * @since    1.0.0
	 */
	public function register_documentation_endpoints() {
		// OpenAPI JSON spec endpoint
		register_rest_route( 'slbp/v1', '/docs/openapi.json', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'serve_openapi_spec' ),
			'permission_callback' => '__return_true',
		) );

		// Swagger UI endpoint
		register_rest_route( 'slbp/v1', '/docs', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'serve_swagger_ui' ),
			'permission_callback' => '__return_true',
		) );
	}

	/**
	 * Serve OpenAPI specification.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   WP_REST_Response               OpenAPI specification.
	 */
	public function serve_openapi_spec( $request ) {
		$spec = $this->generate_openapi_spec();
		
		$response = rest_ensure_response( $spec );
		$response->header( 'Content-Type', 'application/json' );
		
		return $response;
	}

	/**
	 * Serve Swagger UI.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   WP_REST_Response               Swagger UI HTML.
	 */
	public function serve_swagger_ui( $request ) {
		$html = $this->generate_swagger_ui();
		
		$response = new WP_REST_Response( $html );
		$response->header( 'Content-Type', 'text/html' );
		
		return $response;
	}
}