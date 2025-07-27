<?php
/**
 * User dashboard template.
 *
 * This template can be overridden by copying it to yourtheme/slbp-user-dashboard.php.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/public/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

get_header();

// Get dashboard instance
$plugin = SLBP_Plugin::get_instance();
$dashboard = $plugin->resolve( 'user_dashboard' );

?>

<div class="slbp-dashboard-page">
	<div class="container">
		<?php
		if ( $dashboard ) {
			$dashboard->render_dashboard();
		} else {
			echo '<div class="slbp-error">';
			echo '<h2>' . esc_html__( 'Dashboard Unavailable', 'skylearn-billing-pro' ) . '</h2>';
			echo '<p>' . esc_html__( 'The billing dashboard is currently unavailable. Please try again later.', 'skylearn-billing-pro' ) . '</p>';
			echo '</div>';
		}
		?>
	</div>
</div>

<style>
.slbp-dashboard-page {
	padding: 40px 0;
	background: #f8f9fa;
	min-height: 500px;
}

.slbp-dashboard-page .container {
	max-width: 1200px;
	margin: 0 auto;
	padding: 0 20px;
}

.slbp-error {
	text-align: center;
	padding: 60px 20px;
	background: white;
	border-radius: 8px;
	box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.slbp-error h2 {
	color: #dc3232;
	margin-bottom: 10px;
}

.slbp-error p {
	color: #646970;
}
</style>

<?php get_footer(); ?>