<?php
/**
 * Video Tutorial Manager
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/training
 */

/**
 * Video Tutorial Manager Class
 *
 * Manages video tutorials and embedded video content.
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/training
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Video_Manager {

	/**
	 * Supported video platforms.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $platforms    Supported video platforms.
	 */
	private $platforms;

	/**
	 * Initialize the video manager.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->init_platforms();
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_video_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_video_assets' ) );
		add_shortcode( 'slbp_video', array( $this, 'video_shortcode' ) );
	}

	/**
	 * Initialize supported video platforms.
	 *
	 * @since    1.0.0
	 */
	private function init_platforms() {
		$this->platforms = array(
			'youtube' => array(
				'name' => 'YouTube',
				'embed_pattern' => '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/',
				'embed_url' => 'https://www.youtube.com/embed/%s',
				'privacy_url' => 'https://www.youtube-nocookie.com/embed/%s',
			),
			'vimeo' => array(
				'name' => 'Vimeo',
				'embed_pattern' => '/vimeo\.com\/([0-9]+)/',
				'embed_url' => 'https://player.vimeo.com/video/%s',
				'privacy_url' => 'https://player.vimeo.com/video/%s?dnt=1',
			),
			'wistia' => array(
				'name' => 'Wistia',
				'embed_pattern' => '/wistia\.com\/medias\/([a-zA-Z0-9]+)/',
				'embed_url' => 'https://fast.wistia.net/embed/iframe/%s',
				'privacy_url' => 'https://fast.wistia.net/embed/iframe/%s?dnt=1',
			),
		);
	}

	/**
	 * Enqueue video-related assets.
	 *
	 * @since    1.0.0
	 * @param    string    $hook    Current page hook (for admin).
	 */
	public function enqueue_video_assets( $hook = '' ) {
		wp_enqueue_script(
			'slbp-video-player',
			SLBP_PLUGIN_URL . 'public/js/video-player.js',
			array( 'jquery' ),
			SLBP_VERSION,
			true
		);

		wp_enqueue_style(
			'slbp-video-player',
			SLBP_PLUGIN_URL . 'public/css/video-player.css',
			array(),
			SLBP_VERSION
		);

		wp_localize_script(
			'slbp-video-player',
			'slbpVideo',
			array(
				'strings' => array(
					'loading' => __( 'Loading video...', 'skylearn-billing-pro' ),
					'error' => __( 'Error loading video. Please try again.', 'skylearn-billing-pro' ),
					'play' => __( 'Play Video', 'skylearn-billing-pro' ),
					'pause' => __( 'Pause Video', 'skylearn-billing-pro' ),
				),
			)
		);
	}

	/**
	 * Video shortcode handler.
	 *
	 * @since    1.0.0
	 * @param    array     $atts    Shortcode attributes.
	 * @param    string    $content Shortcode content.
	 * @return   string            Video embed HTML.
	 */
	public function video_shortcode( $atts, $content = '' ) {
		$atts = shortcode_atts(
			array(
				'id' => '',
				'url' => '',
				'platform' => 'youtube',
				'width' => '560',
				'height' => '315',
				'autoplay' => '0',
				'privacy' => '1',
				'title' => '',
				'description' => '',
				'thumbnail' => '',
			),
			$atts,
			'slbp_video'
		);

		if ( empty( $atts['id'] ) && empty( $atts['url'] ) ) {
			return '';
		}

		$video_id = $atts['id'];
		if ( empty( $video_id ) && ! empty( $atts['url'] ) ) {
			$video_id = $this->extract_video_id( $atts['url'], $atts['platform'] );
		}

		if ( empty( $video_id ) ) {
			return '';
		}

		return $this->render_video_player( $video_id, $atts );
	}

	/**
	 * Extract video ID from URL.
	 *
	 * @since    1.0.0
	 * @param    string    $url      Video URL.
	 * @param    string    $platform Video platform.
	 * @return   string             Video ID or empty string.
	 */
	public function extract_video_id( $url, $platform ) {
		if ( ! isset( $this->platforms[ $platform ] ) ) {
			return '';
		}

		$pattern = $this->platforms[ $platform ]['embed_pattern'];
		if ( preg_match( $pattern, $url, $matches ) ) {
			return $matches[1];
		}

		return '';
	}

	/**
	 * Get embed URL for video.
	 *
	 * @since    1.0.0
	 * @param    string    $video_id Video ID.
	 * @param    string    $platform Video platform.
	 * @param    bool      $privacy  Use privacy-enhanced mode.
	 * @return   string             Embed URL.
	 */
	public function get_embed_url( $video_id, $platform, $privacy = true ) {
		if ( ! isset( $this->platforms[ $platform ] ) ) {
			return '';
		}

		$platform_config = $this->platforms[ $platform ];
		$url_template = $privacy && isset( $platform_config['privacy_url'] ) 
			? $platform_config['privacy_url'] 
			: $platform_config['embed_url'];

		return sprintf( $url_template, $video_id );
	}

	/**
	 * Render video player.
	 *
	 * @since    1.0.0
	 * @param    string    $video_id Video ID.
	 * @param    array     $args     Player arguments.
	 * @return   string             Video player HTML.
	 */
	public function render_video_player( $video_id, $args = array() ) {
		$defaults = array(
			'platform' => 'youtube',
			'width' => 560,
			'height' => 315,
			'autoplay' => 0,
			'privacy' => 1,
			'title' => '',
			'description' => '',
			'thumbnail' => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$embed_url = $this->get_embed_url( 
			$video_id, 
			$args['platform'], 
			(bool) $args['privacy'] 
		);

		if ( empty( $embed_url ) ) {
			return '';
		}

		// Add autoplay parameter if needed
		if ( $args['autoplay'] ) {
			$embed_url .= strpos( $embed_url, '?' ) !== false ? '&' : '?';
			$embed_url .= 'autoplay=1';
		}

		$container_class = 'slbp-video-container';
		$container_id = 'slbp-video-' . $video_id;

		$html = '<div class="' . esc_attr( $container_class ) . '" id="' . esc_attr( $container_id ) . '">';

		// Add title if provided
		if ( ! empty( $args['title'] ) ) {
			$html .= '<h3 class="slbp-video-title">' . esc_html( $args['title'] ) . '</h3>';
		}

		// Video wrapper for responsive design
		$html .= '<div class="slbp-video-wrapper" style="position: relative; padding-bottom: ' . esc_attr( ( $args['height'] / $args['width'] ) * 100 ) . '%; height: 0; overflow: hidden;">';

		// Thumbnail overlay (for privacy and performance)
		if ( ! empty( $args['thumbnail'] ) ) {
			$html .= '<div class="slbp-video-thumbnail" data-video-url="' . esc_attr( $embed_url ) . '">';
			$html .= '<img src="' . esc_url( $args['thumbnail'] ) . '" alt="' . esc_attr( $args['title'] ) . '" style="width: 100%; height: 100%; object-fit: cover;">';
			$html .= '<button class="slbp-video-play-button" aria-label="' . esc_attr__( 'Play Video', 'skylearn-billing-pro' ) . '">';
			$html .= '<span class="dashicons dashicons-controls-play"></span>';
			$html .= '</button>';
			$html .= '</div>';
		} else {
			// Direct embed
			$html .= '<iframe 
				src="' . esc_url( $embed_url ) . '" 
				width="' . esc_attr( $args['width'] ) . '" 
				height="' . esc_attr( $args['height'] ) . '" 
				style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"
				frameborder="0" 
				allowfullscreen 
				title="' . esc_attr( $args['title'] ) . '">
			</iframe>';
		}

		$html .= '</div>'; // .slbp-video-wrapper

		// Add description if provided
		if ( ! empty( $args['description'] ) ) {
			$html .= '<div class="slbp-video-description">' . wp_kses_post( $args['description'] ) . '</div>';
		}

		$html .= '</div>'; // .slbp-video-container

		return $html;
	}

	/**
	 * Render video gallery.
	 *
	 * @since    1.0.0
	 * @param    array    $videos Video list.
	 * @param    array    $args   Gallery arguments.
	 * @return   string          Gallery HTML.
	 */
	public function render_video_gallery( $videos, $args = array() ) {
		$defaults = array(
			'columns' => 3,
			'show_title' => true,
			'show_description' => true,
			'show_duration' => true,
		);

		$args = wp_parse_args( $args, $defaults );

		if ( empty( $videos ) ) {
			return '';
		}

		$html = '<div class="slbp-video-gallery slbp-video-gallery-columns-' . esc_attr( $args['columns'] ) . '">';

		foreach ( $videos as $video_id => $video ) {
			$html .= '<div class="slbp-video-gallery-item">';
			
			// Video thumbnail/player
			$html .= '<div class="slbp-video-gallery-player">';
			$html .= $this->render_video_player( $video_id, array(
				'platform' => $video['platform'] ?? 'youtube',
				'title' => $video['title'] ?? '',
				'thumbnail' => $video['thumbnail'] ?? '',
				'width' => 400,
				'height' => 225,
			) );
			$html .= '</div>';

			// Video info
			$html .= '<div class="slbp-video-gallery-info">';
			
			if ( $args['show_title'] && ! empty( $video['title'] ) ) {
				$html .= '<h4 class="slbp-video-gallery-title">' . esc_html( $video['title'] ) . '</h4>';
			}
			
			if ( $args['show_description'] && ! empty( $video['description'] ) ) {
				$html .= '<p class="slbp-video-gallery-description">' . esc_html( $video['description'] ) . '</p>';
			}
			
			if ( $args['show_duration'] && ! empty( $video['duration'] ) ) {
				$html .= '<span class="slbp-video-gallery-duration">' . esc_html( $video['duration'] ) . '</span>';
			}
			
			$html .= '</div>'; // .slbp-video-gallery-info
			$html .= '</div>'; // .slbp-video-gallery-item
		}

		$html .= '</div>'; // .slbp-video-gallery

		return $html;
	}

	/**
	 * Get video thumbnail URL.
	 *
	 * @since    1.0.0
	 * @param    string    $video_id Video ID.
	 * @param    string    $platform Video platform.
	 * @return   string             Thumbnail URL.
	 */
	public function get_video_thumbnail( $video_id, $platform ) {
		switch ( $platform ) {
			case 'youtube':
				return "https://img.youtube.com/vi/{$video_id}/maxresdefault.jpg";
			
			case 'vimeo':
				// Vimeo requires API call for thumbnail, return placeholder
				return SLBP_PLUGIN_URL . 'assets/images/video-placeholder.jpg';
			
			case 'wistia':
				return "https://embed-ssl.wistia.com/deliveries/{$video_id}.jpg";
			
			default:
				return SLBP_PLUGIN_URL . 'assets/images/video-placeholder.jpg';
		}
	}

	/**
	 * Get supported video platforms.
	 *
	 * @since    1.0.0
	 * @return   array    Supported platforms.
	 */
	public function get_platforms() {
		return $this->platforms;
	}
}