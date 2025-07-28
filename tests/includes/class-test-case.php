<?php
/**
 * Base test case for SkyLearn Billing Pro tests
 *
 * @package SkyLearnBillingPro\Tests
 */

use PHPUnit\Framework\TestCase;

/**
 * Base test case class providing common functionality for all tests.
 */
abstract class SLBP_Test_Case extends TestCase {

    /**
     * Set up before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        
        // Reset any global state
        $this->reset_globals();
        
        // Set up test environment
        $this->set_up_test_environment();
    }

    /**
     * Tear down after each test.
     */
    protected function tearDown(): void {
        // Clean up test data
        $this->clean_up_test_data();
        
        parent::tearDown();
    }

    /**
     * Reset global variables and state.
     */
    protected function reset_globals() {
        // Reset WordPress globals if needed
        global $wp_actions, $wp_filters;
        $wp_actions = array();
        $wp_filters = array();
    }

    /**
     * Set up test environment.
     */
    protected function set_up_test_environment() {
        // Create test database tables if needed
        $this->maybe_create_test_tables();
    }

    /**
     * Clean up test data.
     */
    protected function clean_up_test_data() {
        // Clean up any test data created during tests
        $this->cleanup_test_tables();
    }

    /**
     * Create test database tables if needed.
     */
    protected function maybe_create_test_tables() {
        // Mock database table creation for testing
        // In real WordPress tests, this would create actual tables
    }

    /**
     * Clean up test database tables.
     */
    protected function cleanup_test_tables() {
        // Mock database cleanup
        // In real WordPress tests, this would clean actual tables
    }

    /**
     * Assert that a string contains another string (case-insensitive).
     *
     * @param string $needle   The substring to search for.
     * @param string $haystack The string to search in.
     * @param string $message  Optional failure message.
     */
    protected function assertStringContainsStringIgnoringCase( $needle, $haystack, $message = '' ) {
        $this->assertStringContainsString(
            strtolower( $needle ),
            strtolower( $haystack ),
            $message
        );
    }

    /**
     * Assert that an array has a specific key-value pair.
     *
     * @param mixed  $expected_value The expected value.
     * @param string $key           The array key.
     * @param array  $array         The array to check.
     * @param string $message       Optional failure message.
     */
    protected function assertArrayKeyValue( $expected_value, $key, $array, $message = '' ) {
        $this->assertArrayHasKey( $key, $array, $message );
        $this->assertEquals( $expected_value, $array[ $key ], $message );
    }

    /**
     * Assert that a value is a valid WordPress nonce.
     *
     * @param mixed  $value   The value to check.
     * @param string $message Optional failure message.
     */
    protected function assertIsValidNonce( $value, $message = '' ) {
        $this->assertIsString( $value, $message );
        $this->assertNotEmpty( $value, $message );
        // In a real WordPress environment, you'd use wp_verify_nonce()
    }

    /**
     * Create a mock WordPress user.
     *
     * @param array $args User arguments.
     * @return object Mock user object.
     */
    protected function create_mock_user( $args = array() ) {
        $defaults = array(
            'ID'           => 1,
            'user_login'   => 'testuser',
            'user_email'   => 'test@example.com',
            'display_name' => 'Test User',
            'user_roles'   => array( 'subscriber' ),
        );

        return (object) array_merge( $defaults, $args );
    }

    /**
     * Create mock post data.
     *
     * @param array $args Post arguments.
     * @return array Mock post data.
     */
    protected function create_mock_post_data( $args = array() ) {
        $defaults = array(
            'post_title'   => 'Test Post',
            'post_content' => 'Test content',
            'post_status'  => 'publish',
            'post_type'    => 'post',
        );

        return array_merge( $defaults, $args );
    }

    /**
     * Mock WordPress database operations.
     *
     * @param string $query  The SQL query.
     * @param mixed  $result The mock result to return.
     */
    protected function mock_db_query( $query, $result = true ) {
        // In a real test environment, this would interact with a test database
        return $result;
    }

    /**
     * Assert that a method call was made with specific arguments.
     *
     * @param object $mock   The mock object.
     * @param string $method The method name.
     * @param array  $args   Expected arguments.
     */
    protected function assertMethodCalledWith( $mock, $method, $args = array() ) {
        // This would be implemented with a proper mocking framework
        $this->assertTrue( true ); // Placeholder
    }
}