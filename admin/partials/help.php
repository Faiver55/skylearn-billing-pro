<?php
/**
 * Enhanced Help & Documentation Page
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/admin/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access is forbidden.' );
}

// Get current user
$current_user = wp_get_current_user();

// Initialize training manager
$training_manager = new SLBP_Training_Manager();
$video_manager = new SLBP_Video_Manager();
$in_app_help = new SLBP_In_App_Help();

// Get documentation structure
$documentation = $training_manager->get_documentation();
$videos = $training_manager->get_videos();

// Get current section
$current_category = isset( $_GET['category'] ) ? sanitize_text_field( $_GET['category'] ) : 'getting_started';
$current_section = isset( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] ) : '';

// Get search query
$search_query = isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '';

?>

<div class="wrap slbp-help-page">
	<h1 class="slbp-page-title">
		<?php esc_html_e( 'Help & Documentation', 'skylearn-billing-pro' ); ?>
		<span class="slbp-version-badge">v<?php echo esc_html( SLBP_VERSION ); ?></span>
	</h1>

	<?php if ( ! empty( $search_query ) ) : ?>
		<div class="slbp-search-results-header">
			<h2><?php printf( esc_html__( 'Search Results for: %s', 'skylearn-billing-pro' ), '<em>' . esc_html( $search_query ) . '</em>' ); ?></h2>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=slbp-help' ) ); ?>" class="button">
				<?php esc_html_e( 'Back to Documentation', 'skylearn-billing-pro' ); ?>
			</a>
		</div>

		<?php
		$search_results = $training_manager->search_documentation( $search_query );
		if ( ! empty( $search_results ) ) :
		?>
			<div class="slbp-search-results">
				<?php foreach ( $search_results as $result ) : ?>
					<div class="slbp-search-result">
						<h3>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=slbp-help&category=' . $result['category'] . '&section=' . $result['section'] ) ); ?>">
								<?php echo esc_html( $result['title'] ); ?>
							</a>
						</h3>
						<p><?php echo wp_kses_post( $result['excerpt'] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<div class="slbp-no-results">
				<p><?php esc_html_e( 'No results found. Try different keywords or browse the categories below.', 'skylearn-billing-pro' ); ?></p>
			</div>
		<?php endif; ?>

	<?php else : ?>

		<!-- Help Navigation -->
		<div class="slbp-help-header">
			<div class="slbp-help-search">
				<form method="get" action="">
					<input type="hidden" name="page" value="slbp-help">
					<input type="search" name="search" placeholder="<?php esc_attr_e( 'Search documentation...', 'skylearn-billing-pro' ); ?>" value="">
					<button type="submit" class="button">
						<span class="dashicons dashicons-search"></span>
						<?php esc_html_e( 'Search', 'skylearn-billing-pro' ); ?>
					</button>
				</form>
			</div>

			<div class="slbp-help-actions">
				<button type="button" class="button" id="slbp-feedback-button">
					<span class="dashicons dashicons-admin-comments"></span>
					<?php esc_html_e( 'Send Feedback', 'skylearn-billing-pro' ); ?>
				</button>
				<a href="https://skyianllc.com/support" target="_blank" class="button button-primary">
					<span class="dashicons dashicons-external"></span>
					<?php esc_html_e( 'Contact Support', 'skylearn-billing-pro' ); ?>
				</a>
			</div>
		</div>

		<div class="slbp-help-layout">
			<!-- Sidebar Navigation -->
			<div class="slbp-help-sidebar">
				<nav class="slbp-help-nav">
					<?php foreach ( $documentation as $category_key => $category ) : ?>
						<div class="slbp-help-nav-category <?php echo ( $current_category === $category_key ) ? 'active' : ''; ?>">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=slbp-help&category=' . $category_key ) ); ?>" class="slbp-help-nav-title">
								<span class="dashicons dashicons-<?php echo esc_attr( $category['icon'] ); ?>"></span>
								<?php echo esc_html( $category['title'] ); ?>
							</a>
							
							<?php if ( $current_category === $category_key && ! empty( $category['sections'] ) ) : ?>
								<ul class="slbp-help-nav-sections">
									<?php foreach ( $category['sections'] as $section_key => $section ) : ?>
										<li class="<?php echo ( $current_section === $section_key ) ? 'active' : ''; ?>">
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=slbp-help&category=' . $category_key . '&section=' . $section_key ) ); ?>">
												<?php echo esc_html( $section['title'] ); ?>
											</a>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>

					<div class="slbp-help-nav-category">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=slbp-help&category=videos' ) ); ?>" class="slbp-help-nav-title <?php echo ( $current_category === 'videos' ) ? 'active' : ''; ?>">
							<span class="dashicons dashicons-video-alt3"></span>
							<?php esc_html_e( 'Video Tutorials', 'skylearn-billing-pro' ); ?>
						</a>
					</div>
				</nav>
			</div>

			<!-- Main Content -->
			<div class="slbp-help-content">
				<?php if ( $current_category === 'videos' ) : ?>
					<!-- Video Tutorials Section -->
					<div class="slbp-help-section">
						<h2><?php esc_html_e( 'Video Tutorials', 'skylearn-billing-pro' ); ?></h2>
						<p><?php esc_html_e( 'Watch step-by-step video guides to learn how to use SkyLearn Billing Pro effectively.', 'skylearn-billing-pro' ); ?></p>
						
						<?php echo $video_manager->render_video_gallery( $videos, array(
							'columns' => 2,
							'show_title' => true,
							'show_description' => true,
							'show_duration' => true,
						) ); ?>
					</div>

				<?php elseif ( ! empty( $current_section ) ) : ?>
					<!-- Specific Section Content -->
					<?php 
					$section_data = $training_manager->get_section( $current_category, $current_section );
					if ( $section_data ) :
					?>
						<div class="slbp-help-section">
							<div class="slbp-help-breadcrumb">
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=slbp-help&category=' . $current_category ) ); ?>">
									<?php echo esc_html( $documentation[ $current_category ]['title'] ); ?>
								</a>
								<span class="separator">/</span>
								<span class="current"><?php echo esc_html( $section_data['title'] ); ?></span>
							</div>

							<h2><?php echo esc_html( $section_data['title'] ); ?></h2>
							
							<div class="slbp-help-content-body">
								<?php echo wp_kses_post( $section_data['content'] ); ?>
							</div>

							<div class="slbp-help-section-footer">
								<p class="slbp-help-feedback">
									<?php esc_html_e( 'Was this helpful?', 'skylearn-billing-pro' ); ?>
									<button type="button" class="slbp-feedback-vote" data-vote="yes">
										<span class="dashicons dashicons-thumbs-up"></span>
										<?php esc_html_e( 'Yes', 'skylearn-billing-pro' ); ?>
									</button>
									<button type="button" class="slbp-feedback-vote" data-vote="no">
										<span class="dashicons dashicons-thumbs-down"></span>
										<?php esc_html_e( 'No', 'skylearn-billing-pro' ); ?>
									</button>
								</p>
							</div>
						</div>
					<?php endif; ?>

				<?php else : ?>
					<!-- Category Overview -->
					<?php 
					$category_data = $documentation[ $current_category ] ?? null;
					if ( $category_data ) :
					?>
						<div class="slbp-help-section">
							<h2>
								<span class="dashicons dashicons-<?php echo esc_attr( $category_data['icon'] ); ?>"></span>
								<?php echo esc_html( $category_data['title'] ); ?>
							</h2>
							
							<div class="slbp-help-sections-grid">
								<?php foreach ( $category_data['sections'] as $section_key => $section ) : ?>
									<div class="slbp-help-section-card">
										<h3>
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=slbp-help&category=' . $current_category . '&section=' . $section_key ) ); ?>">
												<?php echo esc_html( $section['title'] ); ?>
											</a>
										</h3>
										<p><?php echo wp_kses_post( wp_trim_words( wp_strip_all_tags( $section['content'] ), 20 ) ); ?></p>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					<?php else : ?>
						<!-- Default Overview -->
						<div class="slbp-help-section">
							<h2><?php esc_html_e( 'Getting Started', 'skylearn-billing-pro' ); ?></h2>
							<p><?php esc_html_e( 'Welcome to SkyLearn Billing Pro! Choose a category from the sidebar to get started, or use the search function to find specific information.', 'skylearn-billing-pro' ); ?></p>
							
							<div class="slbp-help-quick-links">
								<div class="slbp-help-quick-link">
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=slbp-help&category=getting_started&section=quick_start' ) ); ?>">
										<span class="dashicons dashicons-lightbulb"></span>
										<strong><?php esc_html_e( 'Quick Start Guide', 'skylearn-billing-pro' ); ?></strong>
										<span><?php esc_html_e( 'Get up and running in minutes', 'skylearn-billing-pro' ); ?></span>
									</a>
								</div>
								
								<div class="slbp-help-quick-link">
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=slbp-help&category=videos' ) ); ?>">
										<span class="dashicons dashicons-video-alt3"></span>
										<strong><?php esc_html_e( 'Video Tutorials', 'skylearn-billing-pro' ); ?></strong>
										<span><?php esc_html_e( 'Watch step-by-step guides', 'skylearn-billing-pro' ); ?></span>
									</a>
								</div>
								
								<div class="slbp-help-quick-link">
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=slbp-help&category=troubleshooting' ) ); ?>">
										<span class="dashicons dashicons-admin-tools"></span>
										<strong><?php esc_html_e( 'Troubleshooting', 'skylearn-billing-pro' ); ?></strong>
										<span><?php esc_html_e( 'Solve common issues', 'skylearn-billing-pro' ); ?></span>
									</a>
								</div>
								
								<div class="slbp-help-quick-link">
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=slbp-help&category=faq' ) ); ?>">
										<span class="dashicons dashicons-editor-help"></span>
										<strong><?php esc_html_e( 'FAQ', 'skylearn-billing-pro' ); ?></strong>
										<span><?php esc_html_e( 'Find answers to common questions', 'skylearn-billing-pro' ); ?></span>
									</a>
								</div>
							</div>
						</div>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>

	<?php endif; ?>

	<!-- System Information Footer -->
	<div class="slbp-help-footer">
		<div class="slbp-system-info">
			<h3><?php esc_html_e( 'System Information', 'skylearn-billing-pro' ); ?></h3>
			<div class="slbp-system-info-grid">
				<div class="slbp-system-info-item">
					<strong><?php esc_html_e( 'Plugin Version:', 'skylearn-billing-pro' ); ?></strong>
					<span><?php echo esc_html( SLBP_VERSION ); ?></span>
				</div>
				<div class="slbp-system-info-item">
					<strong><?php esc_html_e( 'WordPress Version:', 'skylearn-billing-pro' ); ?></strong>
					<span><?php echo esc_html( get_bloginfo( 'version' ) ); ?></span>
				</div>
				<div class="slbp-system-info-item">
					<strong><?php esc_html_e( 'PHP Version:', 'skylearn-billing-pro' ); ?></strong>
					<span><?php echo esc_html( PHP_VERSION ); ?></span>
				</div>
				<div class="slbp-system-info-item">
					<strong><?php esc_html_e( 'LearnDash Active:', 'skylearn-billing-pro' ); ?></strong>
					<span><?php echo class_exists( 'SFWD_LMS' ) ? esc_html__( 'Yes', 'skylearn-billing-pro' ) : esc_html__( 'No', 'skylearn-billing-pro' ); ?></span>
				</div>
			</div>
		</div>

		<div class="slbp-support-info">
			<h3><?php esc_html_e( 'Need More Help?', 'skylearn-billing-pro' ); ?></h3>
			<p><?php esc_html_e( 'Our support team is here to help you succeed with SkyLearn Billing Pro.', 'skylearn-billing-pro' ); ?></p>
			<div class="slbp-support-links">
				<a href="https://skyianllc.com/support" target="_blank" class="button button-primary">
					<?php esc_html_e( 'Contact Support', 'skylearn-billing-pro' ); ?>
				</a>
				<a href="https://skyianllc.com/docs" target="_blank" class="button">
					<?php esc_html_e( 'Online Documentation', 'skylearn-billing-pro' ); ?>
				</a>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	// Initialize help system
	if (typeof window.SLBPHelpSystem !== 'undefined') {
		window.SLBPHelpSystem.init();
	}
	
	// Feedback voting
	$('.slbp-feedback-vote').on('click', function() {
		var vote = $(this).data('vote');
		var context = '<?php echo esc_js( $current_category . ( $current_section ? '/' . $current_section : '' ) ); ?>';
		
		// Send feedback (implement AJAX call)
		$.post(ajaxurl, {
			action: 'slbp_help_feedback',
			nonce: '<?php echo wp_create_nonce( 'slbp_help_feedback' ); ?>',
			context: context,
			vote: vote
		}, function(response) {
			if (response.success) {
				$(this).closest('.slbp-help-feedback').html('<p class="slbp-feedback-thanks"><?php esc_html_e( 'Thank you for your feedback!', 'skylearn-billing-pro' ); ?></p>');
			}
		}.bind(this));
		
		$(this).addClass('voted').siblings().removeClass('voted');
	});
	
	// Feedback button
	$('#slbp-feedback-button').on('click', function() {
		if (typeof window.SLBPHelpSystem !== 'undefined') {
			window.SLBPHelpSystem.showFeedbackModal();
		}
	});
});
</script>