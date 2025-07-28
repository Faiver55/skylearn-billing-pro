<?php
/**
 * API tests for SkyLearn Billing Pro REST endpoints
 *
 * @package SkyLearnBillingPro\Tests\API
 */

class Test_REST_API extends SLBP_Test_Case {

    /**
     * Test health check endpoint.
     */
    public function test_health_check_endpoint() {
        $request = SLBP_Mock_Factory::create_api_request( array(
            'method' => 'GET',
            'route' => '/skylearn-billing-pro/v1/health',
        ) );

        $response = $this->dispatch_request( $request );

        $this->assertEquals( 200, $response['status'] );
        $this->assertArrayHasKey( 'status', $response['data'] );
        $this->assertEquals( 'healthy', $response['data']['status'] );
        $this->assertArrayHasKey( 'timestamp', $response['data'] );
        $this->assertArrayHasKey( 'version', $response['data'] );
    }

    /**
     * Test transactions endpoint with authentication.
     */
    public function test_transactions_endpoint() {
        $request = SLBP_Mock_Factory::create_api_request( array(
            'method' => 'GET',
            'route' => '/skylearn-billing-pro/v1/transactions',
            'headers' => array(
                'Authorization' => 'Bearer test_token_123',
            ),
            'params' => array(
                'limit' => 10,
                'status' => 'completed',
            ),
        ) );

        $response = $this->dispatch_request( $request );

        $this->assertEquals( 200, $response['status'] );
        $this->assertArrayHasKey( 'transactions', $response['data'] );
        $this->assertArrayHasKey( 'pagination', $response['data'] );
        $this->assertArrayHasKey( 'total', $response['data']['pagination'] );
        $this->assertArrayHasKey( 'per_page', $response['data']['pagination'] );
    }

    /**
     * Test creating a transaction via API.
     */
    public function test_create_transaction() {
        $transaction_data = array(
            'user_id' => 123,
            'amount' => 49.99,
            'currency' => 'USD',
            'gateway' => 'lemon-squeezy',
            'course_id' => 456,
        );

        $request = SLBP_Mock_Factory::create_api_request( array(
            'method' => 'POST',
            'route' => '/skylearn-billing-pro/v1/transactions',
            'headers' => array(
                'Authorization' => 'Bearer test_token_123',
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode( $transaction_data ),
        ) );

        $response = $this->dispatch_request( $request );

        $this->assertEquals( 201, $response['status'] );
        $this->assertArrayHasKey( 'transaction', $response['data'] );
        $this->assertEquals( 49.99, $response['data']['transaction']['amount'] );
        $this->assertEquals( 'USD', $response['data']['transaction']['currency'] );
        $this->assertArrayHasKey( 'id', $response['data']['transaction'] );
    }

    /**
     * Test subscriptions endpoint.
     */
    public function test_subscriptions_endpoint() {
        $request = SLBP_Mock_Factory::create_api_request( array(
            'method' => 'GET',
            'route' => '/skylearn-billing-pro/v1/subscriptions',
            'headers' => array(
                'Authorization' => 'Bearer test_token_123',
            ),
            'params' => array(
                'user_id' => 123,
                'status' => 'active',
            ),
        ) );

        $response = $this->dispatch_request( $request );

        $this->assertEquals( 200, $response['status'] );
        $this->assertArrayHasKey( 'subscriptions', $response['data'] );
        $this->assertIsArray( $response['data']['subscriptions'] );
    }

    /**
     * Test subscription creation.
     */
    public function test_create_subscription() {
        $subscription_data = array(
            'user_id' => 123,
            'plan_id' => 'premium-monthly',
            'amount' => 29.99,
            'currency' => 'USD',
            'interval' => 'monthly',
            'trial_days' => 7,
        );

        $request = SLBP_Mock_Factory::create_api_request( array(
            'method' => 'POST',
            'route' => '/skylearn-billing-pro/v1/subscriptions',
            'headers' => array(
                'Authorization' => 'Bearer test_token_123',
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode( $subscription_data ),
        ) );

        $response = $this->dispatch_request( $request );

        $this->assertEquals( 201, $response['status'] );
        $this->assertArrayHasKey( 'subscription', $response['data'] );
        $this->assertEquals( 29.99, $response['data']['subscription']['amount'] );
        $this->assertEquals( 'monthly', $response['data']['subscription']['interval'] );
    }

    /**
     * Test webhook endpoint.
     */
    public function test_webhook_endpoint() {
        $webhook_payload = SLBP_Mock_Factory::create_webhook_payload( 'payment.completed', array(
            'transaction_id' => 'webhook_test_123',
            'amount' => 99.99,
            'currency' => 'USD',
            'customer_email' => 'webhook@example.com',
        ) );

        $request = SLBP_Mock_Factory::create_api_request( array(
            'method' => 'POST',
            'route' => '/skylearn-billing-pro/v1/webhooks/lemon-squeezy',
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-Signature' => 'test_signature_123',
            ),
            'body' => json_encode( $webhook_payload ),
        ) );

        $response = $this->dispatch_request( $request );

        $this->assertEquals( 200, $response['status'] );
        $this->assertArrayHasKey( 'received', $response['data'] );
        $this->assertTrue( $response['data']['received'] );
        $this->assertArrayHasKey( 'processed', $response['data'] );
    }

    /**
     * Test API authentication failure.
     */
    public function test_authentication_failure() {
        $request = SLBP_Mock_Factory::create_api_request( array(
            'method' => 'GET',
            'route' => '/skylearn-billing-pro/v1/transactions',
            // No authorization header
        ) );

        $response = $this->dispatch_request( $request );

        $this->assertEquals( 401, $response['status'] );
        $this->assertArrayHasKey( 'error', $response['data'] );
        $this->assertEquals( 'authentication_required', $response['data']['error']['code'] );
    }

    /**
     * Test API rate limiting.
     */
    public function test_rate_limiting() {
        $requests_made = 0;
        $rate_limit = 100; // Requests per minute

        // Simulate making many requests
        for ( $i = 0; $i < $rate_limit + 10; $i++ ) {
            $request = SLBP_Mock_Factory::create_api_request( array(
                'method' => 'GET',
                'route' => '/skylearn-billing-pro/v1/health',
                'headers' => array(
                    'X-Client-IP' => '192.168.1.100',
                ),
            ) );

            $response = $this->dispatch_request( $request );
            $requests_made++;

            // After hitting rate limit, should get 429 response
            if ( $requests_made > $rate_limit ) {
                $this->assertEquals( 429, $response['status'] );
                $this->assertArrayHasKey( 'error', $response['data'] );
                $this->assertEquals( 'rate_limit_exceeded', $response['data']['error']['code'] );
                break;
            }
        }
    }

    /**
     * Test API validation errors.
     */
    public function test_validation_errors() {
        // Test creating transaction with invalid data
        $invalid_data = array(
            'amount' => -10, // Invalid negative amount
            'currency' => 'INVALID', // Invalid currency
            'user_id' => 'not_a_number', // Invalid user ID
        );

        $request = SLBP_Mock_Factory::create_api_request( array(
            'method' => 'POST',
            'route' => '/skylearn-billing-pro/v1/transactions',
            'headers' => array(
                'Authorization' => 'Bearer test_token_123',
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode( $invalid_data ),
        ) );

        $response = $this->dispatch_request( $request );

        $this->assertEquals( 400, $response['status'] );
        $this->assertArrayHasKey( 'error', $response['data'] );
        $this->assertEquals( 'validation_failed', $response['data']['error']['code'] );
        $this->assertArrayHasKey( 'validation_errors', $response['data']['error'] );
    }

    /**
     * Test API response format.
     */
    public function test_api_response_format() {
        $request = SLBP_Mock_Factory::create_api_request( array(
            'method' => 'GET',
            'route' => '/skylearn-billing-pro/v1/health',
        ) );

        $response = $this->dispatch_request( $request );

        // Check response structure
        $this->assertArrayHasKey( 'status', $response );
        $this->assertArrayHasKey( 'data', $response );
        $this->assertIsInt( $response['status'] );
        $this->assertIsArray( $response['data'] );

        // Check data contains required fields
        $this->assertArrayHasKey( 'status', $response['data'] );
        $this->assertArrayHasKey( 'timestamp', $response['data'] );
    }

    /**
     * Test API pagination.
     */
    public function test_api_pagination() {
        $request = SLBP_Mock_Factory::create_api_request( array(
            'method' => 'GET',
            'route' => '/skylearn-billing-pro/v1/transactions',
            'headers' => array(
                'Authorization' => 'Bearer test_token_123',
            ),
            'params' => array(
                'page' => 2,
                'per_page' => 5,
            ),
        ) );

        $response = $this->dispatch_request( $request );

        $this->assertEquals( 200, $response['status'] );
        $this->assertArrayHasKey( 'pagination', $response['data'] );
        
        $pagination = $response['data']['pagination'];
        $this->assertArrayHasKey( 'current_page', $pagination );
        $this->assertArrayHasKey( 'per_page', $pagination );
        $this->assertArrayHasKey( 'total', $pagination );
        $this->assertArrayHasKey( 'total_pages', $pagination );
        
        $this->assertEquals( 2, $pagination['current_page'] );
        $this->assertEquals( 5, $pagination['per_page'] );
    }

    /**
     * Mock method to dispatch API requests.
     *
     * @param object $request Mock request object.
     * @return array Mock response.
     */
    private function dispatch_request( $request ) {
        $route = $request->get_route();
        $method = $request->get_method();
        
        // Mock different endpoints
        switch ( $route ) {
            case '/skylearn-billing-pro/v1/health':
                return $this->mock_health_response();
                
            case '/skylearn-billing-pro/v1/transactions':
                if ( $method === 'GET' ) {
                    return $this->mock_transactions_list_response( $request );
                } elseif ( $method === 'POST' ) {
                    return $this->mock_create_transaction_response( $request );
                }
                break;
                
            case '/skylearn-billing-pro/v1/subscriptions':
                if ( $method === 'GET' ) {
                    return $this->mock_subscriptions_list_response( $request );
                } elseif ( $method === 'POST' ) {
                    return $this->mock_create_subscription_response( $request );
                }
                break;
                
            case '/skylearn-billing-pro/v1/webhooks/lemon-squeezy':
                return $this->mock_webhook_response( $request );
        }
        
        return array(
            'status' => 404,
            'data' => array(
                'error' => array(
                    'code' => 'endpoint_not_found',
                    'message' => 'API endpoint not found',
                ),
            ),
        );
    }

    /**
     * Mock health check response.
     */
    private function mock_health_response() {
        return array(
            'status' => 200,
            'data' => array(
                'status' => 'healthy',
                'timestamp' => current_time( 'mysql' ),
                'version' => SLBP_VERSION,
            ),
        );
    }

    /**
     * Mock transactions list response.
     */
    private function mock_transactions_list_response( $request ) {
        $auth_header = $request->get_header( 'Authorization' );
        if ( empty( $auth_header ) ) {
            return array(
                'status' => 401,
                'data' => array(
                    'error' => array(
                        'code' => 'authentication_required',
                        'message' => 'Authentication required',
                    ),
                ),
            );
        }

        return array(
            'status' => 200,
            'data' => array(
                'transactions' => array(),
                'pagination' => array(
                    'current_page' => $request->get_param( 'page' ) ?: 1,
                    'per_page' => $request->get_param( 'per_page' ) ?: 10,
                    'total' => 100,
                    'total_pages' => 10,
                ),
            ),
        );
    }

    /**
     * Mock create transaction response.
     */
    private function mock_create_transaction_response( $request ) {
        $body = json_decode( $request->get_body(), true );
        
        // Validate required fields
        if ( !isset( $body['amount'] ) || $body['amount'] <= 0 ) {
            return array(
                'status' => 400,
                'data' => array(
                    'error' => array(
                        'code' => 'validation_failed',
                        'message' => 'Validation failed',
                        'validation_errors' => array(
                            'amount' => 'Amount must be greater than 0',
                        ),
                    ),
                ),
            );
        }

        return array(
            'status' => 201,
            'data' => array(
                'transaction' => array_merge( $body, array(
                    'id' => uniqid(),
                    'status' => 'pending',
                    'created_at' => current_time( 'mysql' ),
                ) ),
            ),
        );
    }

    /**
     * Mock subscriptions list response.
     */
    private function mock_subscriptions_list_response( $request ) {
        return array(
            'status' => 200,
            'data' => array(
                'subscriptions' => array(),
            ),
        );
    }

    /**
     * Mock create subscription response.
     */
    private function mock_create_subscription_response( $request ) {
        $body = json_decode( $request->get_body(), true );
        
        return array(
            'status' => 201,
            'data' => array(
                'subscription' => array_merge( $body, array(
                    'id' => uniqid(),
                    'status' => 'active',
                    'created_at' => current_time( 'mysql' ),
                ) ),
            ),
        );
    }

    /**
     * Mock webhook response.
     */
    private function mock_webhook_response( $request ) {
        return array(
            'status' => 200,
            'data' => array(
                'received' => true,
                'processed' => true,
                'timestamp' => current_time( 'mysql' ),
            ),
        );
    }
}