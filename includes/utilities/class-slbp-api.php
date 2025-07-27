<?php
/**
 * SkyLearn Billing Pro Public API
 *
 * Provides public functions for developers to interact with the billing system.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/utilities
 */

/**
 * SkyLearn Billing Pro Public API Class
 *
 * Public API for creating checkouts, managing subscriptions,
 * and accessing billing data.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/utilities
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_API {

	/**
	 * Create a checkout session.
	 *
	 * @since    1.0.0
	 * @param    string    $product_id     Product ID from payment gateway.
	 * @param    array     $args           Checkout arguments.
	 * @return   array|WP_Error            Checkout data or WP_Error on failure.
	 */
	public static function create_checkout( $product_id, $args = array() ) {
		// Get the default gateway (Lemon Squeezy for now)
		$plugin = SLBP_Plugin::get_instance();
		$gateway = $plugin->get_payment_gateway( 'lemon_squeezy' );

		if ( ! $gateway ) {
			return new WP_Error( 'gateway_unavailable', 'Payment gateway not available' );
		}

		// Prepare checkout arguments
		$checkout_args = array_merge( array(
			'product_id' => $product_id,
		), $args );

		// Add current user information if not provided
		if ( is_user_logged_in() && empty( $checkout_args['user_id'] ) ) {
			$current_user = wp_get_current_user();
			$checkout_args['user_id'] = $current_user->ID;
			$checkout_args['customer_email'] = $current_user->user_email;
			$checkout_args['customer_name'] = $current_user->display_name;
		}

		return $gateway->create_checkout( $checkout_args );
	}

	/**
	 * Get user subscriptions.
	 *
	 * @since    1.0.0
	 * @param    int    $user_id    WordPress user ID. Defaults to current user.
	 * @return   array              Array of subscription data.
	 */
	public static function get_user_subscriptions( $user_id = null ) {
		if ( ! $user_id ) {
			if ( ! is_user_logged_in() ) {
				return array();
			}
			$user_id = get_current_user_id();
		}

		$subscription_ids = get_user_meta( $user_id, 'slbp_subscriptions', true ) ?: array();
		$subscriptions = array();

		foreach ( $subscription_ids as $subscription_id ) {
			$subscription = get_option( 'slbp_subscription_' . $subscription_id );
			if ( $subscription ) {
				$subscriptions[] = $subscription;
			}
		}

		return $subscriptions;
	}

	/**
	 * Get user orders/transactions.
	 *
	 * @since    1.0.0
	 * @param    int    $user_id    WordPress user ID. Defaults to current user.
	 * @return   array              Array of order data.
	 */
	public static function get_user_orders( $user_id = null ) {
		if ( ! $user_id ) {
			if ( ! is_user_logged_in() ) {
				return array();
			}
			$user_id = get_current_user_id();
		}

		$order_ids = get_user_meta( $user_id, 'slbp_orders', true ) ?: array();
		$orders = array();

		foreach ( $order_ids as $order_id ) {
			$order = get_option( 'slbp_order_' . $order_id );
			if ( $order ) {
				$orders[] = $order;
			}
		}

		return $orders;
	}

	/**
	 * Cancel a subscription.
	 *
	 * @since    1.0.0
	 * @param    string    $subscription_id    Subscription ID.
	 * @param    int       $user_id           WordPress user ID. Optional for permission check.
	 * @return   bool|WP_Error                True if successful, WP_Error on failure.
	 */
	public static function cancel_subscription( $subscription_id, $user_id = null ) {
		// Verify user has permission to cancel this subscription
		if ( $user_id && ! self::user_owns_subscription( $user_id, $subscription_id ) ) {
			return new WP_Error( 'permission_denied', 'User does not own this subscription' );
		}

		$plugin = SLBP_Plugin::get_instance();
		$gateway = $plugin->get_payment_gateway( 'lemon_squeezy' );

		if ( ! $gateway ) {
			return new WP_Error( 'gateway_unavailable', 'Payment gateway not available' );
		}

		return $gateway->cancel_subscription( $subscription_id );
	}

	/**
	 * Get available products.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Optional arguments for filtering.
	 * @return   array|WP_Error    Array of products or WP_Error on failure.
	 */
	public static function get_products( $args = array() ) {
		$plugin = SLBP_Plugin::get_instance();
		$gateway = $plugin->get_payment_gateway( 'lemon_squeezy' );

		if ( ! $gateway ) {
			return new WP_Error( 'gateway_unavailable', 'Payment gateway not available' );
		}

		return $gateway->get_products( $args );
	}

	/**
	 * Get a specific product.
	 *
	 * @since    1.0.0
	 * @param    string    $product_id    Product ID.
	 * @return   array|WP_Error           Product data or WP_Error on failure.
	 */
	public static function get_product( $product_id ) {
		$plugin = SLBP_Plugin::get_instance();
		$gateway = $plugin->get_payment_gateway( 'lemon_squeezy' );

		if ( ! $gateway ) {
			return new WP_Error( 'gateway_unavailable', 'Payment gateway not available' );
		}

		return $gateway->get_product( $product_id );
	}

	/**
	 * Check if user owns a subscription.
	 *
	 * @since    1.0.0
	 * @param    int       $user_id           WordPress user ID.
	 * @param    string    $subscription_id   Subscription ID.
	 * @return   bool                         True if user owns subscription, false otherwise.
	 */
	public static function user_owns_subscription( $user_id, $subscription_id ) {
		$user_subscriptions = get_user_meta( $user_id, 'slbp_subscriptions', true ) ?: array();
		return in_array( $subscription_id, $user_subscriptions );
	}

	/**
	 * Check if user owns an order.
	 *
	 * @since    1.0.0
	 * @param    int       $user_id    WordPress user ID.
	 * @param    string    $order_id   Order ID.
	 * @return   bool                  True if user owns order, false otherwise.
	 */
	public static function user_owns_order( $user_id, $order_id ) {
		$user_orders = get_user_meta( $user_id, 'slbp_orders', true ) ?: array();
		return in_array( $order_id, $user_orders );
	}

	/**
	 * Get user's course access based on billing.
	 *
	 * @since    1.0.0
	 * @param    int    $user_id    WordPress user ID. Defaults to current user.
	 * @return   array              Array of course IDs user has access to.
	 */
	public static function get_user_course_access( $user_id = null ) {
		if ( ! $user_id ) {
			if ( ! is_user_logged_in() ) {
				return array();
			}
			$user_id = get_current_user_id();
		}

		$product_manager = new SLBP_Product_Manager();
		return $product_manager->get_user_enrolled_courses( $user_id );
	}

	/**
	 * Check if user has access to a course through billing.
	 *
	 * @since    1.0.0
	 * @param    int    $course_id    Course ID.
	 * @param    int    $user_id      WordPress user ID. Defaults to current user.
	 * @return   bool                 True if user has access, false otherwise.
	 */
	public static function user_has_course_access( $course_id, $user_id = null ) {
		if ( ! $user_id ) {
			if ( ! is_user_logged_in() ) {
				return false;
			}
			$user_id = get_current_user_id();
		}

		$product_manager = new SLBP_Product_Manager();
		return $product_manager->user_has_course_access( $user_id, $course_id );
	}

	/**
	 * Get product mappings.
	 *
	 * @since    1.0.0
	 * @return   array    Array of product mappings.
	 */
	public static function get_product_mappings() {
		$product_manager = new SLBP_Product_Manager();
		return $product_manager->get_product_mappings();
	}

	/**
	 * Get courses for a product.
	 *
	 * @since    1.0.0
	 * @param    string    $product_id    Product ID.
	 * @return   array                    Array of course IDs.
	 */
	public static function get_courses_for_product( $product_id ) {
		$product_manager = new SLBP_Product_Manager();
		return $product_manager->get_courses_for_product( $product_id );
	}

	/**
	 * Get enrollment history for a user.
	 *
	 * @since    1.0.0
	 * @param    int    $user_id    WordPress user ID. Defaults to current user.
	 * @param    int    $limit      Number of records to retrieve.
	 * @return   array              Array of enrollment records.
	 */
	public static function get_user_enrollment_history( $user_id = null, $limit = 20 ) {
		if ( ! $user_id ) {
			if ( ! is_user_logged_in() ) {
				return array();
			}
			$user_id = get_current_user_id();
		}

		$product_manager = new SLBP_Product_Manager();
		return $product_manager->get_user_enrollment_history( $user_id, $limit );
	}

	/**
	 * Sync user enrollments with payment status.
	 *
	 * @since    1.0.0
	 * @param    int    $user_id    WordPress user ID.
	 * @return   bool               True if sync successful, false otherwise.
	 */
	public static function sync_user_enrollments( $user_id ) {
		$product_manager = new SLBP_Product_Manager();
		return $product_manager->sync_user_enrollments( $user_id );
	}

	/**
	 * Get billing stats for a user.
	 *
	 * @since    1.0.0
	 * @param    int    $user_id    WordPress user ID. Defaults to current user.
	 * @return   array              Array of billing statistics.
	 */
	public static function get_user_billing_stats( $user_id = null ) {
		if ( ! $user_id ) {
			if ( ! is_user_logged_in() ) {
				return array();
			}
			$user_id = get_current_user_id();
		}

		$subscriptions = self::get_user_subscriptions( $user_id );
		$orders = self::get_user_orders( $user_id );

		$stats = array(
			'total_subscriptions'    => count( $subscriptions ),
			'active_subscriptions'   => 0,
			'cancelled_subscriptions' => 0,
			'total_orders'           => count( $orders ),
			'total_spent'            => 0,
			'courses_enrolled'       => count( self::get_user_course_access( $user_id ) ),
		);

		// Calculate subscription stats
		foreach ( $subscriptions as $subscription ) {
			if ( in_array( $subscription['status'], array( 'active', 'trialing' ) ) ) {
				$stats['active_subscriptions']++;
			} elseif ( $subscription['cancelled'] || $subscription['status'] === 'cancelled' ) {
				$stats['cancelled_subscriptions']++;
			}
		}

		// Calculate total spent
		foreach ( $orders as $order ) {
			if ( $order['status'] === 'paid' && ! $order['refunded'] ) {
				$stats['total_spent'] += $order['total'];
			}
		}

		return $stats;
	}

	/**
	 * Check if the plugin is properly configured.
	 *
	 * @since    1.0.0
	 * @return   array    Configuration status.
	 */
	public static function get_configuration_status() {
		$payment_settings = get_option( 'slbp_payment_settings', array() );
		$general_settings = get_option( 'slbp_general_settings', array() );

		$status = array(
			'plugin_enabled'     => ! empty( $general_settings['plugin_enabled'] ),
			'api_key_set'        => ! empty( $payment_settings['lemon_squeezy_api_key'] ),
			'store_id_set'       => ! empty( $payment_settings['lemon_squeezy_store_id'] ),
			'webhook_secret_set' => ! empty( $payment_settings['webhook_secret'] ),
			'product_mappings'   => count( self::get_product_mappings() ),
			'learndash_available' => function_exists( 'ld_update_course_access' ),
		);

		$status['fully_configured'] = $status['plugin_enabled'] && 
		                              $status['api_key_set'] && 
		                              $status['store_id_set'] && 
		                              $status['webhook_secret_set'] && 
		                              $status['product_mappings'] > 0;

		return $status;
	}
}

// Convenience functions for developers
if ( ! function_exists( 'slbp_create_checkout' ) ) {
	/**
	 * Create a checkout session.
	 *
	 * @since    1.0.0
	 * @param    string    $product_id    Product ID.
	 * @param    array     $args          Checkout arguments.
	 * @return   array|WP_Error           Checkout data or WP_Error.
	 */
	function slbp_create_checkout( $product_id, $args = array() ) {
		return SLBP_API::create_checkout( $product_id, $args );
	}
}

if ( ! function_exists( 'slbp_get_user_subscriptions' ) ) {
	/**
	 * Get user subscriptions.
	 *
	 * @since    1.0.0
	 * @param    int    $user_id    WordPress user ID.
	 * @return   array              Array of subscriptions.
	 */
	function slbp_get_user_subscriptions( $user_id = null ) {
		return SLBP_API::get_user_subscriptions( $user_id );
	}
}

if ( ! function_exists( 'slbp_user_has_course_access' ) ) {
	/**
	 * Check if user has course access through billing.
	 *
	 * @since    1.0.0
	 * @param    int    $course_id    Course ID.
	 * @param    int    $user_id      WordPress user ID.
	 * @return   bool                 True if has access.
	 */
	function slbp_user_has_course_access( $course_id, $user_id = null ) {
		return SLBP_API::user_has_course_access( $course_id, $user_id );
	}
}

if ( ! function_exists( 'slbp_get_user_billing_stats' ) ) {
	/**
	 * Get user billing statistics.
	 *
	 * @since    1.0.0
	 * @param    int    $user_id    WordPress user ID.
	 * @return   array              Billing statistics.
	 */
	function slbp_get_user_billing_stats( $user_id = null ) {
		return SLBP_API::get_user_billing_stats( $user_id );
	}
}