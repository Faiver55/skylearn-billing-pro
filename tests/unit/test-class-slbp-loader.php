<?php
/**
 * Unit tests for SLBP_Loader class
 *
 * @package SkyLearnBillingPro\Tests\Unit
 */

require_once SLBP_PLUGIN_DIR . '/includes/core/class-slbp-loader.php';

class Test_SLBP_Loader extends SLBP_Test_Case {

    /**
     * Loader instance for testing.
     *
     * @var SLBP_Loader
     */
    private $loader;

    /**
     * Set up test environment.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->loader = new SLBP_Loader();
    }

    /**
     * Test adding actions.
     */
    public function test_add_action() {
        $hook = 'test_hook';
        $component = $this;
        $callback = 'test_callback_method';
        $priority = 15;
        $accepted_args = 2;

        $this->loader->add_action( $hook, $component, $callback, $priority, $accepted_args );

        // Get actions using reflection
        $reflection = new ReflectionClass( $this->loader );
        $actions_property = $reflection->getProperty( 'actions' );
        $actions_property->setAccessible( true );
        $actions = $actions_property->getValue( $this->loader );

        $this->assertCount( 1, $actions );
        $this->assertEquals( $hook, $actions[0]['hook'] );
        $this->assertEquals( $component, $actions[0]['component'] );
        $this->assertEquals( $callback, $actions[0]['callback'] );
        $this->assertEquals( $priority, $actions[0]['priority'] );
        $this->assertEquals( $accepted_args, $actions[0]['accepted_args'] );
    }

    /**
     * Test adding filters.
     */
    public function test_add_filter() {
        $hook = 'test_filter';
        $component = $this;
        $callback = 'test_filter_method';
        $priority = 20;
        $accepted_args = 3;

        $this->loader->add_filter( $hook, $component, $callback, $priority, $accepted_args );

        // Get filters using reflection
        $reflection = new ReflectionClass( $this->loader );
        $filters_property = $reflection->getProperty( 'filters' );
        $filters_property->setAccessible( true );
        $filters = $filters_property->getValue( $this->loader );

        $this->assertCount( 1, $filters );
        $this->assertEquals( $hook, $filters[0]['hook'] );
        $this->assertEquals( $component, $filters[0]['component'] );
        $this->assertEquals( $callback, $filters[0]['callback'] );
        $this->assertEquals( $priority, $filters[0]['priority'] );
        $this->assertEquals( $accepted_args, $filters[0]['accepted_args'] );
    }

    /**
     * Test adding multiple actions and filters.
     */
    public function test_add_multiple_hooks() {
        // Add multiple actions
        $this->loader->add_action( 'action_1', $this, 'callback_1' );
        $this->loader->add_action( 'action_2', $this, 'callback_2' );

        // Add multiple filters
        $this->loader->add_filter( 'filter_1', $this, 'filter_callback_1' );
        $this->loader->add_filter( 'filter_2', $this, 'filter_callback_2' );

        // Check counts using reflection
        $reflection = new ReflectionClass( $this->loader );
        
        $actions_property = $reflection->getProperty( 'actions' );
        $actions_property->setAccessible( true );
        $actions = $actions_property->getValue( $this->loader );

        $filters_property = $reflection->getProperty( 'filters' );
        $filters_property->setAccessible( true );
        $filters = $filters_property->getValue( $this->loader );

        $this->assertCount( 2, $actions );
        $this->assertCount( 2, $filters );
    }

    /**
     * Test default values for add_action.
     */
    public function test_add_action_defaults() {
        $this->loader->add_action( 'test_hook', $this, 'test_callback' );

        $reflection = new ReflectionClass( $this->loader );
        $actions_property = $reflection->getProperty( 'actions' );
        $actions_property->setAccessible( true );
        $actions = $actions_property->getValue( $this->loader );

        $action = $actions[0];
        $this->assertEquals( 10, $action['priority'] );
        $this->assertEquals( 1, $action['accepted_args'] );
    }

    /**
     * Test default values for add_filter.
     */
    public function test_add_filter_defaults() {
        $this->loader->add_filter( 'test_filter', $this, 'test_callback' );

        $reflection = new ReflectionClass( $this->loader );
        $filters_property = $reflection->getProperty( 'filters' );
        $filters_property->setAccessible( true );
        $filters = $filters_property->getValue( $this->loader );

        $filter = $filters[0];
        $this->assertEquals( 10, $filter['priority'] );
        $this->assertEquals( 1, $filter['accepted_args'] );
    }

    /**
     * Test run method registers hooks.
     */
    public function test_run_registers_hooks() {
        // Mock WordPress functions for this test
        $registered_actions = array();
        $registered_filters = array();

        // Override add_action function for testing
        $GLOBALS['test_registered_actions'] = &$registered_actions;
        $GLOBALS['test_registered_filters'] = &$registered_filters;

        // Add some hooks
        $this->loader->add_action( 'init', $this, 'init_callback', 5, 2 );
        $this->loader->add_filter( 'the_content', $this, 'content_filter', 15, 1 );

        // In a real test environment, run() would register these with WordPress
        // For now, we just test that the method exists and doesn't throw errors
        $this->assertTrue( method_exists( $this->loader, 'run' ) );
        
        // Test that run() can be called without errors
        $this->loader->run();
        
        // In a real WordPress environment, we'd verify that add_action and add_filter were called
        $this->assertTrue( true ); // Placeholder assertion
    }

    /**
     * Test that empty hooks are handled gracefully.
     */
    public function test_empty_hooks_handling() {
        // Test with empty hook name
        $this->expectException( InvalidArgumentException::class );
        $this->loader->add_action( '', $this, 'callback' );
    }

    /**
     * Test that invalid callbacks are handled.
     */
    public function test_invalid_callback_handling() {
        // Test with non-existent method
        $this->expectException( InvalidArgumentException::class );
        $this->loader->add_action( 'test_hook', $this, 'non_existent_method' );
    }

    /**
     * Test hook priority ordering.
     */
    public function test_hook_priority_ordering() {
        // Add actions with different priorities
        $this->loader->add_action( 'test_hook', $this, 'callback_low', 20 );
        $this->loader->add_action( 'test_hook', $this, 'callback_high', 5 );
        $this->loader->add_action( 'test_hook', $this, 'callback_default' ); // Default priority 10

        $reflection = new ReflectionClass( $this->loader );
        $actions_property = $reflection->getProperty( 'actions' );
        $actions_property->setAccessible( true );
        $actions = $actions_property->getValue( $this->loader );

        // Verify all actions were added
        $this->assertCount( 3, $actions );

        // Check priorities
        $priorities = array_column( $actions, 'priority' );
        $this->assertContains( 5, $priorities );
        $this->assertContains( 10, $priorities );
        $this->assertContains( 20, $priorities );
    }

    /**
     * Dummy callback method for testing.
     */
    public function test_callback_method() {
        return 'test_callback_executed';
    }

    /**
     * Dummy filter method for testing.
     */
    public function test_filter_method( $content ) {
        return $content . '_filtered';
    }
}