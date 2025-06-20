<?php
/**
 * The product images.
 *
 * @package    Woo_Gallery_Slider
 * @subpackage Woo_Gallery_Slider/public
 */

/**
 * WCGS Public Style class
 */
class WCGS_Public_Style extends WCGS_Public_Settings {

	/**
	 * Dynamic css
	 *
	 * @var string
	 */
	private static $dynamic_css;
	/**
	 * Additional css
	 *
	 * @var string
	 */
	private static $additional_css;
	/**
	 * Settings
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * The constructor of the class.
	 *
	 * @param array $settings settings option.
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;
		parent::__construct( $settings );
		$this->wcgs_css();
		$this->wcgs_custom_css();
	}

	/**
	 * Wcgs css.
	 *
	 * @return void
	 */
	public function wcgs_css() {
		$settings                          = $this->settings;
		$gallery_bottom_gap                = isset( $settings['gallery_bottom_gap'] ) ? $settings['gallery_bottom_gap'] : 30;
		$caption_color                     = isset( $settings['caption_color'] ) ? $settings['caption_color'] : '#ffffff';
		$thumbnails_space                  = isset( $settings['thumbnails_space'] ) ? $settings['thumbnails_space'] / 2 : 3;
		$thumbnails_top                    = isset( $settings['thumbnails_space'] ) ? $settings['thumbnails_space'] : 6;
		$border_normal_width_for_thumbnail = isset( $settings['border_normal_width_for_thumbnail'] ) ? $settings['border_normal_width_for_thumbnail'] : '';
		$vertical_thumbs_width             = isset( $settings['vertical_thumbs_width'] ) ? $settings['vertical_thumbs_width'] : '20';
		$navigation_visibility             = isset( $settings['navigation_visibility'] ) ? $settings['navigation_visibility'] : '';
		$normal_thumbnail_border_color     = isset( $border_normal_width_for_thumbnail['color'] ) ? $border_normal_width_for_thumbnail['color'] : '';
		$normal_thumbnail_border_size      = isset( $border_normal_width_for_thumbnail['all'] ) ? $border_normal_width_for_thumbnail['all'] : '2';

		$hover_thumbnail_border_color   = isset( $border_normal_width_for_thumbnail['color3'] ) ? $border_normal_width_for_thumbnail['color3'] : '#0085BA';
		$normal_thumbnail_border_radius = isset( $border_normal_width_for_thumbnail['radius'] ) ? $border_normal_width_for_thumbnail['radius'] : '0';
		$thumbnail_border               = isset( $settings['border_width_for_active_thumbnail'] ) ? $settings['border_width_for_active_thumbnail'] : '';
		// $active_thumbnail_border_color     = isset( $thumbnail_border['color'] ) ? $thumbnail_border['color'] : '#dddddd';

		// $active_thumbnail_border_color3    = isset( $thumbnail_border['color3'] ) ? $thumbnail_border['color3'] : '#5EABC1';

		$active_thumbnail_border_color2 = isset( $thumbnail_border['color2'] ) ? $thumbnail_border['color2'] : '#0085BA';
		$active_thumbnail_border_size   = isset( $thumbnail_border['all'] ) ? $thumbnail_border['all'] : '0';
		$thumb_position                 = '2';
		$video_icon_type                = isset( $settings['video_icon'] ) ? $settings['video_icon'] : 'play-01';
		$thumbnails_top                 = $this->thumbnails_sliders_height;
		$thumbnails_left                = $this->thumbnails_sliders_width;
		$thumb_slider_margin            = "margin-top: {$thumbnails_top}px;";
		$vr_slide_padding               = 0;
		if ( $normal_thumbnail_border_size > 0 ) {
			$vr_slide_padding = $normal_thumbnail_border_size + 2;
		}
		$vide_play_icon = '\e823';
		switch ( $video_icon_type ) {
			case 'play-01':
				$vide_play_icon = '\e823';
				break;
			case 'play-02':
				$vide_play_icon = '\e837';
				break;
			case 'play-03':
					$vide_play_icon = '\e838';
				break;
			case 'play-04':
				$vide_play_icon = '\e839';
				break;

			case 'play-05':
				$vide_play_icon = '\e83a';
				break;
			default:
				$vide_play_icon = '\e823';
				break;
		}
		if ( ( 'horizontal_top' === $this->gallery_layout ) ) {
			$thumb_position      = '-1';
			$thumb_slider_margin = "margin-bottom: {$thumbnails_top}px;";
		}
		if ( ( 'vertical_right' === $this->gallery_layout ) ) {
			$thumb_slider_margin = "margin-left: {$thumbnails_left}px;";
		}
		$gallery_width = isset( $settings['gallery_width'] ) ? $settings['gallery_width'] : 50;
		$dynamic_css   = '';
		if ( $gallery_width < 100 ) {
			$summary_width_with_unit = 'calc(' . ( 100 - $gallery_width ) . '% - 50px)';
			$dynamic_css            .= '@media screen and (min-width:992px ){
				#wpgs-gallery.wcgs-woocommerce-product-gallery {
					max-width: 100%;
				}
				#wpgs-gallery.wcgs-woocommerce-product-gallery:has( + .summary ) {
					max-width: ' . $gallery_width . '%;
				}
                #wpgs-gallery.wcgs-woocommerce-product-gallery+.summary {
                    max-width: ' . $summary_width_with_unit . ';
                }
            }';
		}
		$dynamic_css .= '#wpgs-gallery .wcgs-video-icon:after {
				content: "' . $vide_play_icon . '";
		}
		#wpgs-gallery .gallery-navigation-carousel-wrapper {
			-ms-flex-order: ' . $thumb_position . ' !important;
			order: ' . $thumb_position . ' !important;
			' . $thumb_slider_margin . ';
		}
		.rtl  #wpgs-gallery.wcgs-vertical-right .gallery-navigation-carousel-wrapper {
			margin-right: ' . $thumbnails_left . 'px;
			margin-left: 0;
		}
		#wpgs-gallery .wcgs-carousel .wcgs-spswiper-arrow {
			font-size: ' . $this->navigation_icon_size . 'px;
		}
		#wpgs-gallery .wcgs-carousel .wcgs-spswiper-arrow:before,
		#wpgs-gallery .wcgs-carousel .wcgs-spswiper-arrow:before {
			font-size: ' . $this->navigation_icon_size . 'px;
			color: ' . $this->navigation_icon_color . ';
			line-height: unset;
		}
		#wpgs-gallery.wcgs-woocommerce-product-gallery .wcgs-carousel .wcgs-slider-image {
			border-radius: ' . $this->image_border_radius . 'px;
		}
		#wpgs-gallery .wcgs-carousel .wcgs-spswiper-arrow,
		#wpgs-gallery .wcgs-carousel .wcgs-spswiper-arrow{
			background-color: ' . $this->navigation_icon_bg_color . ';
			border-radius: ' . $this->navigation_icon_radius . 'px;

		}
		#wpgs-gallery .wcgs-carousel .wcgs-spswiper-arrow:hover, #wpgs-gallery .wcgs-carousel .wcgs-spswiper-arrow:hover {
			background-color: ' . $this->navigation_icon_hover_bg_color . ';
		}
		#wpgs-gallery .wcgs-carousel .wcgs-spswiper-arrow:hover::before, #wpgs-gallery .wcgs-carousel .wcgs-spswiper-arrow:hover::before{
            color: ' . $this->navigation_icon_hover_color . ';
		}
		#wpgs-gallery .spswiper-pagination .spswiper-pagination-bullet {
			background-color: ' . $this->pagination_icon_color . ';
		}
		#wpgs-gallery .spswiper-pagination .spswiper-pagination-bullet.spswiper-pagination-bullet-active {
			background-color: ' . $this->pagination_icon_active_color . ';
		}
		#wpgs-gallery .wcgs-lightbox .sp_wgs-lightbox {
			color: ' . $this->lightbox_icon_color . ';
			background-color: ' . $this->lightbox_icon_bg_color . ';
			font-size: ' . $this->lightbox_icon_size . 'px;
		}
		#wpgs-gallery .wcgs-lightbox .sp_wgs-lightbox:hover {
			color: ' . $this->lightbox_icon_hover_color . ';
			background-color: ' . $this->lightbox_icon_hover_bg_color . ';
		}
		#wpgs-gallery .gallery-navigation-carousel .wcgs-spswiper-arrow {
			background-color: ' . $this->thumbnailnavigation_icon_bg_color . ';
		}
		#wpgs-gallery .gallery-navigation-carousel .wcgs-spswiper-arrow:before{
			font-size: ' . $this->thumbnailnavigation_icon_size . 'px;
			color: ' . $this->thumbnailnavigation_icon_color . ';
		}
		#wpgs-gallery .gallery-navigation-carousel .wcgs-spswiper-arrow:hover {
			background-color: ' . $this->thumbnailnavigation_icon_hover_bg_color . ';
		}
		#wpgs-gallery .gallery-navigation-carousel .wcgs-spswiper-arrow:hover::before{
			color: ' . $this->thumbnailnavigation_icon_hover_color . ';
		}
		#wpgs-gallery .wcgs-thumb.spswiper-slide-thumb-active.wcgs-thumb img {
			border: ' . $active_thumbnail_border_size . 'px solid ' . $active_thumbnail_border_color2 . ';
		}
		#wpgs-gallery .wcgs-thumb.spswiper-slide:hover img,
		#wpgs-gallery .wcgs-thumb.spswiper-slide-thumb-active.wcgs-thumb:hover img {
			border-color: ' . $hover_thumbnail_border_color . ';
		}
		#wpgs-gallery .wcgs-thumb.spswiper-slide img {
			border: ' . $normal_thumbnail_border_size . 'px solid ' . $normal_thumbnail_border_color . ';
			border-radius: ' . $normal_thumbnail_border_radius . 'px;
		}
		#wpgs-gallery {
			margin-bottom: ' . $gallery_bottom_gap . 'px;
			max-width: 50%;
		}
		.fancybox-caption__body {
			color: ' . $caption_color . ';
			font-size: ' . $this->lightbox_caption_size . 'px;
		}
		.fancybox-bg {
			background: #1e1e1e !important;
		}';
		if ( 'hover' === $navigation_visibility ) {
			$dynamic_css .= '#wpgs-gallery .wcgs-carousel .wcgs-spswiper-arrow, #wpgs-gallery .wcgs-carousel .wcgs-spswiper-arrow {
				opacity: 0;
			}';
		}
		if ( ( 'hide_thumb' === $this->gallery_layout ) ) {
			$dynamic_css .= '#wpgs-gallery .gallery-navigation-carousel-wrapper {
				display: none;
			}';
		}
		if ( ( 'vertical_right' === $this->gallery_layout ) ) {
			$dynamic_css .= '#wpgs-gallery.vertical .gallery-navigation-carousel-wrapper:not(.wcgs-hidden) {
				width: ' . $vertical_thumbs_width . '%;
			}#wpgs-gallery.vertical.wcgs-woocommerce-product-gallery .wcgs-carousel{
				width: calc(100% - ' . $vertical_thumbs_width . '%);
			}';
		}

		self::$dynamic_css = $dynamic_css;
	}
	/**
	 * Wcgs custom css
	 *
	 * @return void
	 */
	public function wcgs_custom_css() {
		self::$additional_css = trim( html_entity_decode( $this->wcgs_additional_css ) );
	}

	/**
	 * The dynamic stylesheet include by this function for frontend.
	 *
	 * @return dynamic_styles.
	 */
	public static function wcgs_stylesheet_include() {
		return self::$dynamic_css . self::$additional_css;
	}

	/**
	 * Wcgs stylesheet include
	 *
	 * @return void
	 */
	// public static function wcgs_stylesheet_include() {
	// if ( is_singular( 'product' ) ) {
	// wp_enqueue_style( 'wcgs_custom-style', plugin_dir_url( dirname( __DIR__ ) ) . 'css/dynamic.css', '1.0.0', 'all' );
	// wp_add_inline_style( 'wcgs_custom-style', self::$dynamic_css );
	// wp_add_inline_style( 'wcgs_custom-style', self::$additional_css );
	// }
	// }
}
