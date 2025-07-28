<?php
/**
 * Unit tests for SLBP_Plugin class
 *
 * @package SkyLearnBillingPro\Tests\Unit
 */

require_once SLBP_PLUGIN_DIR . '/includes/core/class-slbp-plugin.php';
require_once SLBP_PLUGIN_DIR . '/includes/core/class-slbp-loader.php';

class Test_SLBP_Plugin extends SLBP_Test_Case {

    /**
     * Test plugin singleton instance.
     */
    public function test_singleton_instance() {
        $instance1 = SLBP_Plugin::get_instance();
        $instance2 = SLBP_Plugin::get_instance();

        $this->assertInstanceOf( 'SLBP_Plugin', $instance1 );
        $this->assertSame( $instance1, $instance2, 'Plugin should follow singleton pattern' );
    }

    /**
     * Test plugin initialization.
     */
    public function test_plugin_initialization() {
        $plugin = SLBP_Plugin::get_instance();

        // Test that plugin name is set correctly
        $this->assertEquals( 'skylearn-billing-pro', $plugin->get_plugin_name() );

        // Test that version is set correctly
        $this->assertEquals( SLBP_VERSION, $plugin->get_version() );

        // Test that loader is initialized
        $this->assertInstanceOf( 'SLBP_Loader', $plugin->get_loader() );
    }

    /**
     * Test dependency injection container.
     */
    public function test_dependency_injection() {
        $plugin = SLBP_Plugin::get_instance();

        // Test registering a dependency
        $test_service = new stdClass();
        $test_service->name = 'test_service';

        $plugin->register( 'test_service', $test_service );

        // Test retrieving the dependency
        $retrieved_service = $plugin->get( 'test_service' );
        $this->assertSame( $test_service, $retrieved_service );
        $this->assertEquals( 'test_service', $retrieved_service->name );
    }

    /**
     * Test getting non-existent dependency throws exception.
     */
    public function test_get_non_existent_dependency() {
        $plugin = SLBP_Plugin::get_instance();

        $this->expectException( Exception::class );
        $this->expectExceptionMessage( 'Service non_existent_service not found in container' );

        $plugin->get( 'non_existent_service' );
    }

    /**
     * Test plugin run method.
     */
    public function test_plugin_run() {
        $plugin = SLBP_Plugin::get_instance();
        $loader = $this->createMock( 'SLBP_Loader' );

        // Mock the loader run method
        $loader->expects( $this->once() )
               ->method( 'run' );

        // Replace the loader with our mock
        $reflection = new ReflectionClass( $plugin );
        $loader_property = $reflection->getProperty( 'loader' );
        $loader_property->setAccessible( true );
        $loader_property->setValue( $plugin, $loader );

        // Run the plugin
        $plugin->run();
    }

    /**
     * Test plugin constants are defined.
     */
    public function test_plugin_constants() {
        $this->assertTrue( defined( 'SLBP_VERSION' ) );
        $this->assertTrue( defined( 'SLBP_PLUGIN_FILE' ) );
        $this->assertTrue( defined( 'SLBP_PLUGIN_PATH' ) );
        $this->assertTrue( defined( 'SLBP_PLUGIN_URL' ) );
        $this->assertTrue( defined( 'SLBP_TEXT_DOMAIN' ) );

        $this->assertEquals( '1.0.0', SLBP_VERSION );
        $this->assertEquals( 'skylearn-billing-pro', SLBP_TEXT_DOMAIN );
    }

    /**
     * Test admin initialization.
     */
    public function test_admin_initialization() {
        $plugin = SLBP_Plugin::get_instance();

        // In a real test, we'd check if admin hooks are registered
        // For now, just test that admin can be retrieved
        $admin = $plugin->get_admin();
        $this->assertNotNull( $admin );
    }

    /**
     * Test internationalization initialization.
     */
    public function test_i18n_initialization() {
        $plugin = SLBP_Plugin::get_instance();

        // Test that i18n is initialized
        $i18n = $plugin->get_i18n();
        $this->assertNotNull( $i18n );
    }

    /**
     * Test that plugin handles errors gracefully.
     */
    public function test_error_handling() {
        $plugin = SLBP_Plugin::get_instance();

        // Test that plugin doesn't break with invalid dependencies
        try {
            $plugin->register( '', null );
            $this->fail( 'Should throw exception for empty service name' );
        } catch ( Exception $e ) {
            $this->assertStringContainsString( 'Service name cannot be empty', $e->getMessage() );
        }
    }

    /**
     * Test plugin deactivation cleanup.
     */
    public function test_plugin_cleanup() {
        $plugin = SLBP_Plugin::get_instance();

        // Test that cleanup methods exist
        $this->assertTrue( method_exists( $plugin, 'cleanup' ) );

        // Test cleanup execution
        $result = $plugin->cleanup();
        $this->assertTrue( $result );
    }

    /**
     * Test plugin version comparison.
     */
    public function test_version_handling() {
        $plugin = SLBP_Plugin::get_instance();

        $current_version = $plugin->get_version();
        $this->assertTrue( version_compare( $current_version, '0.9.0', '>' ) );
        $this->assertTrue( version_compare( $current_version, '2.0.0', '<' ) );
    }
}