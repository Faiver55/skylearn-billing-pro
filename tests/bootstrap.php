<?php
/**
 * PHPUnit bootstrap file for SkyLearn Billing Pro
 *
 * @package SkyLearnBillingPro
 */

// Prevent WordPress from calling home
if ( ! defined( 'WP_TESTS_DOMAIN' ) ) {
    define( 'WP_TESTS_DOMAIN', 'example.org' );
}

// Define test constants
define( 'SLBP_TESTS_DIR', __DIR__ );
define( 'SLBP_PLUGIN_DIR', dirname( __DIR__ ) );
define( 'SLBP_PLUGIN_FILE', SLBP_PLUGIN_DIR . '/skylearn-billing-pro.php' );

// Set test mode
define( 'SLBP_TEST_MODE', true );
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', false );
define( 'WP_DEBUG_DISPLAY', false );

// Prevent WordPress from trying to call home
if ( ! defined( 'WP_HTTP_BLOCK_EXTERNAL' ) ) {
    define( 'WP_HTTP_BLOCK_EXTERNAL', true );
}

// Mock WordPress functions for unit testing
if ( ! function_exists( 'wp_die' ) ) {
    function wp_die( $message = '', $title = '', $args = array() ) {
        throw new Exception( $message );
    }
}

if ( ! function_exists( 'esc_html' ) ) {
    function esc_html( $text ) {
        return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'esc_attr' ) ) {
    function esc_attr( $text ) {
        return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $str ) {
        return trim( strip_tags( $str ) );
    }
}

if ( ! function_exists( 'wp_nonce_field' ) ) {
    function wp_nonce_field( $action = -1, $name = '_wpnonce', $referer = true, $echo = true ) {
        $nonce_field = '<input type="hidden" id="' . $name . '" name="' . $name . '" value="test_nonce" />';
        if ( $echo ) {
            echo $nonce_field;
        }
        return $nonce_field;
    }
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
    function wp_verify_nonce( $nonce, $action = -1 ) {
        return $nonce === 'test_nonce';
    }
}

if ( ! function_exists( 'current_user_can' ) ) {
    function current_user_can( $capability ) {
        return true; // Allow all capabilities in tests
    }
}

if ( ! function_exists( 'add_action' ) ) {
    function add_action( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
        // Mock implementation for testing
        return true;
    }
}

if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
        // Mock implementation for testing
        return true;
    }
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
    function plugin_dir_path( $file ) {
        return dirname( $file ) . '/';
    }
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
    function plugin_dir_url( $file ) {
        return 'http://example.com/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
    }
}

if ( ! function_exists( 'plugin_basename' ) ) {
    function plugin_basename( $file ) {
        return basename( dirname( $file ) ) . '/' . basename( $file );
    }
}

if ( ! function_exists( 'register_activation_hook' ) ) {
    function register_activation_hook( $file, $function ) {
        // Mock implementation for testing
        return true;
    }
}

if ( ! function_exists( 'register_deactivation_hook' ) ) {
    function register_deactivation_hook( $file, $function ) {
        // Mock implementation for testing
        return true;
    }
}

if ( ! function_exists( 'is_admin' ) ) {
    function is_admin() {
        return false;
    }
}

if ( ! function_exists( 'wp_insert_user' ) ) {
    function wp_insert_user( $userdata ) {
        return rand( 1, 1000 );
    }
}

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', '/tmp/wordpress/' );
}

// Check if constants are already defined before loading plugin file
if ( ! defined( 'SLBP_PLUGIN_FILE' ) ) {
    // Load the plugin constants
    require_once SLBP_PLUGIN_FILE;
}

// Include test utilities
require_once SLBP_TESTS_DIR . '/includes/class-test-case.php';
require_once SLBP_TESTS_DIR . '/includes/class-mock-factory.php';

// Load Composer autoloader if available
if ( file_exists( SLBP_PLUGIN_DIR . '/vendor/autoload.php' ) ) {
    require_once SLBP_PLUGIN_DIR . '/vendor/autoload.php';
}

echo "SkyLearn Billing Pro Test Bootstrap Loaded\n";