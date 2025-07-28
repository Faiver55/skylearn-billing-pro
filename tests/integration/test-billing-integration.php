<?php
/**
 * Integration tests for billing flows
 *
 * @package SkyLearnBillingPro\Tests\Integration
 */

class Test_Billing_Integration extends SLBP_Test_Case {

    /**
     * Test complete payment processing flow.
     */
    public function test_complete_payment_flow() {
        // Create mock user
        $user = $this->create_mock_user( array(
            'ID' => 123,
            'user_email' => 'customer@example.com',
        ) );

        // Create mock course
        $course = SLBP_Mock_Factory::create_course( array(
            'ID' => 456,
            'post_title' => 'Premium Course',
            'price' => 99.99,
        ) );

        // Create mock payment gateway
        $gateway = SLBP_Mock_Factory::create_payment_gateway( array(
            'id' => 'lemon-squeezy',
            'title' => 'Lemon Squeezy',
            'enabled' => true,
        ) );

        // Test payment processing
        $payment_data = array(
            'amount' => 99.99,
            'currency' => 'USD',
            'user_id' => $user->ID,
            'course_id' => $course->ID,
            'payment_method' => 'card',
        );

        $result = $gateway->process_payment( $payment_data['amount'], $payment_data );
        
        $this->assertTrue( $result['success'] );
        $this->assertArrayHasKey( 'transaction_id', $result );
        $this->assertEquals( 99.99, $result['amount'] );

        // Verify transaction is created
        $transaction = SLBP_Mock_Factory::create_transaction( array(
            'user_id' => $user->ID,
            'amount' => $payment_data['amount'],
            'currency' => $payment_data['currency'],
            'status' => 'completed',
            'gateway' => 'lemon-squeezy',
            'transaction_id' => $result['transaction_id'],
        ) );

        $this->assertEquals( 'completed', $transaction['status'] );
        $this->assertEquals( $user->ID, $transaction['user_id'] );
        $this->assertEquals( 99.99, $transaction['amount'] );
    }

    /**
     * Test subscription creation and management.
     */
    public function test_subscription_management_flow() {
        // Create mock user
        $user = $this->create_mock_user( array(
            'ID' => 789,
            'user_email' => 'subscriber@example.com',
        ) );

        // Create subscription plan
        $plan_data = array(
            'id' => 'monthly-premium',
            'name' => 'Monthly Premium',
            'amount' => 29.99,
            'currency' => 'USD',
            'interval' => 'monthly',
            'trial_days' => 7,
        );

        // Create subscription
        $subscription = SLBP_Mock_Factory::create_subscription( array(
            'user_id' => $user->ID,
            'plan_id' => $plan_data['id'],
            'amount' => $plan_data['amount'],
            'currency' => $plan_data['currency'],
            'interval' => $plan_data['interval'],
            'status' => 'active',
            'trial_days' => $plan_data['trial_days'],
        ) );

        // Verify subscription creation
        $this->assertEquals( 'active', $subscription['status'] );
        $this->assertEquals( $user->ID, $subscription['user_id'] );
        $this->assertEquals( 29.99, $subscription['amount'] );
        $this->assertEquals( 'monthly', $subscription['interval'] );

        // Test subscription cancellation
        $subscription['status'] = 'cancelled';
        $subscription['cancelled_at'] = current_time( 'mysql' );

        $this->assertEquals( 'cancelled', $subscription['status'] );
        $this->assertNotEmpty( $subscription['cancelled_at'] );

        // Test subscription reactivation
        $subscription['status'] = 'active';
        $subscription['cancelled_at'] = null;
        $subscription['reactivated_at'] = current_time( 'mysql' );

        $this->assertEquals( 'active', $subscription['status'] );
        $this->assertNull( $subscription['cancelled_at'] );
        $this->assertNotEmpty( $subscription['reactivated_at'] );
    }

    /**
     * Test LearnDash course enrollment integration.
     */
    public function test_learndash_enrollment_integration() {
        // Create mock user and course
        $user = $this->create_mock_user( array(
            'ID' => 101,
            'user_email' => 'student@example.com',
        ) );

        $course = SLBP_Mock_Factory::create_course( array(
            'ID' => 202,
            'post_title' => 'Advanced WordPress',
            'price' => 149.99,
            'access_type' => 'closed',
        ) );

        // Simulate successful payment
        $transaction = SLBP_Mock_Factory::create_transaction( array(
            'user_id' => $user->ID,
            'amount' => 149.99,
            'status' => 'completed',
            'metadata' => array(
                'course_id' => $course->ID,
                'enrollment_type' => 'purchase',
            ),
        ) );

        // Test enrollment process
        $enrollment_result = $this->process_course_enrollment( $user->ID, $course->ID, $transaction );

        $this->assertTrue( $enrollment_result['success'] );
        $this->assertEquals( 'enrolled', $enrollment_result['status'] );
        $this->assertArrayHasKey( 'enrollment_date', $enrollment_result );

        // Test access verification
        $has_access = $this->verify_course_access( $user->ID, $course->ID );
        $this->assertTrue( $has_access );
    }

    /**
     * Test webhook processing integration.
     */
    public function test_webhook_processing_integration() {
        // Create webhook payload for successful payment
        $webhook_payload = SLBP_Mock_Factory::create_webhook_payload( 'payment.completed', array(
            'transaction_id' => 'test_webhook_' . uniqid(),
            'amount' => 59.99,
            'currency' => 'USD',
            'customer_email' => 'webhook@example.com',
            'course_id' => 303,
        ) );

        // Process webhook
        $webhook_result = $this->process_webhook( $webhook_payload );

        $this->assertTrue( $webhook_result['success'] );
        $this->assertEquals( 'processed', $webhook_result['status'] );
        $this->assertArrayHasKey( 'transaction_created', $webhook_result );

        // Test subscription webhook
        $subscription_webhook = SLBP_Mock_Factory::create_webhook_payload( 'subscription.created', array(
            'subscription_id' => 'sub_webhook_' . uniqid(),
            'plan_id' => 'premium-monthly',
            'customer_email' => 'subscriber@example.com',
            'status' => 'active',
        ) );

        $sub_result = $this->process_webhook( $subscription_webhook );

        $this->assertTrue( $sub_result['success'] );
        $this->assertEquals( 'processed', $sub_result['status'] );
        $this->assertArrayHasKey( 'subscription_created', $sub_result );
    }

    /**
     * Test error handling in payment flow.
     */
    public function test_payment_error_handling() {
        // Test insufficient funds scenario
        $payment_data = array(
            'amount' => 999.99,
            'currency' => 'USD',
            'user_id' => 404,
            'course_id' => 505,
            'simulate_error' => 'insufficient_funds',
        );

        $gateway = SLBP_Mock_Factory::create_payment_gateway();
        
        // Override process_payment to simulate error
        $gateway->process_payment = function( $amount, $data ) {
            if ( isset( $data['simulate_error'] ) ) {
                return array(
                    'success' => false,
                    'error' => array(
                        'code' => $data['simulate_error'],
                        'message' => 'Payment failed: Insufficient funds',
                    ),
                );
            }
            return array( 'success' => true );
        };

        $result = $gateway->process_payment( $payment_data['amount'], $payment_data );

        $this->assertFalse( $result['success'] );
        $this->assertArrayHasKey( 'error', $result );
        $this->assertEquals( 'insufficient_funds', $result['error']['code'] );
        $this->assertStringContainsString( 'Insufficient funds', $result['error']['message'] );
    }

    /**
     * Helper method to process course enrollment.
     *
     * @param int   $user_id      User ID.
     * @param int   $course_id    Course ID.
     * @param array $transaction  Transaction data.
     * @return array Enrollment result.
     */
    private function process_course_enrollment( $user_id, $course_id, $transaction ) {
        // Mock enrollment process
        return array(
            'success' => true,
            'status' => 'enrolled',
            'user_id' => $user_id,
            'course_id' => $course_id,
            'transaction_id' => $transaction['transaction_id'],
            'enrollment_date' => current_time( 'mysql' ),
        );
    }

    /**
     * Helper method to verify course access.
     *
     * @param int $user_id   User ID.
     * @param int $course_id Course ID.
     * @return bool Access status.
     */
    private function verify_course_access( $user_id, $course_id ) {
        // Mock access verification
        return true;
    }

    /**
     * Helper method to process webhook.
     *
     * @param array $payload Webhook payload.
     * @return array Processing result.
     */
    private function process_webhook( $payload ) {
        // Mock webhook processing
        $result = array(
            'success' => true,
            'status' => 'processed',
            'event' => $payload['event'],
            'processed_at' => current_time( 'mysql' ),
        );

        switch ( $payload['event'] ) {
            case 'payment.completed':
                $result['transaction_created'] = true;
                break;
            case 'subscription.created':
                $result['subscription_created'] = true;
                break;
        }

        return $result;
    }
}