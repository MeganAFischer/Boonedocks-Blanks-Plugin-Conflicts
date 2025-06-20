<?php
/**
 * The gallery tab functionality of this plugin.
 *
 * Defines the sections of gallery tab.
 *
 * @package    Woo_Gallery_Slider
 * @subpackage Woo_Gallery_Slider/admin
 * @author     Shapedplugin <support@shapedplugin.com>
 */

/**
 * WCGS Lightbox class
 */
class WCGS_Lightbox {
	/**
	 * Specify the Gallery tab for the WooGallery.
	 *
	 * @since    1.0.0
	 * @param string $prefix Define prefix wcgs_settings.
	 */
	public static function section( $prefix ) {
		WCGS::createSection(
			$prefix,
			array(
				'name'   => 'lightbox',
				'icon'   => 'sp_wgs-icon-lightbox-tab',
				'title'  => __( 'Lightbox', 'gallery-slider-for-woocommerce' ),
				'fields' => array(
					array(
						'id'         => 'lightbox',
						'type'       => 'switcher',
						'title'      => esc_html__( 'Enable Lightbox', 'gallery-slider-for-woocommerce' ),
						'text_on'    => __( 'Enabled', 'gallery-slider-for-woocommerce' ),
						'text_off'   => __( 'Disabled', 'gallery-slider-for-woocommerce' ),
						'text_width' => 96,
						'default'    => true,
					),
					array(
						'id'         => 'lightbox_sliding_effect',
						'type'       => 'select',

						'title'      => __( 'Lightbox Sliding Effect', 'gallery-slider-for-woocommerce' ),
						'options'    => array(
							'slide'        => __( 'Slide', 'gallery-slider-for-woocommerce' ),
							'fade'         => __( 'Fade (Pro)', 'gallery-slider-for-woocommerce' ),
							'classic'      => __( 'Classic', 'gallery-slider-for-woocommerce' ),
							'crossfade'    => __( 'Crossfade', 'gallery-slider-for-woocommerce' ),
							'no_animation' => __( 'No Animation', 'gallery-slider-for-woocommerce' ),
						),
						'default'    => 'slide',
						'dependency' => array( 'lightbox', '==', true ),
					),
					array(
						'id'         => 'lightbox_icon_position',
						'class'      => 'lightbox_icon_position',
						'type'       => 'image_select',
						'title_help' => '<div class="wcgs-info-label">' . __( 'Lightbox Icon Display Position', 'gallery-slider-for-woocommerce' ) . '</div><div class="wcgs-short-content">' . __( 'Choose where you want to place the lightbox icon over the product thumbnail.', 'gallery-slider-for-woocommerce' ) . '</div><a class="wcgs-open-docs" href="https://woogallery.io/docs/how-to-customize-the-lightbox-icon-display-position/" target="_blank">' . __( 'Open Docs', 'gallery-slider-for-woocommerce' ) . '</a><a class="wcgs-open-live-demo" href="https://demo.woogallery.io/lightbox-popup-icon-position/" target="_blank">' . __( 'Live Demo', 'gallery-slider-for-woocommerce' ) . '</a>',
						'title'      => __( 'Lightbox Icon Display Position', 'gallery-slider-for-woocommerce' ),
						'options'    => array(
							'top_right'    => array(
								'image'       => plugin_dir_url( __DIR__ ) . '../img/top-right.svg',
								'option_name' => __( 'Top Right', 'gallery-slider-for-woocommerce' ),
							),
							'top_left'     => array(
								'image'       => plugin_dir_url( __DIR__ ) . '../img/top-left.svg',
								'option_name' => __( 'Top Left', 'gallery-slider-for-woocommerce' ),
							),
							'bottom_right' => array(
								'image'       => plugin_dir_url( __DIR__ ) . '../img/bottom-right.svg',
								'option_name' => __( 'Bottom Right', 'gallery-slider-for-woocommerce' ),
							),
							'bottom_left'  => array(
								'image'       => plugin_dir_url( __DIR__ ) . '../img/bottom-left.svg',
								'option_name' => __( 'Bottom Left', 'gallery-slider-for-woocommerce' ),
							),
							'middle'       => array(
								'image'       => plugin_dir_url( __DIR__ ) . '../img/middle.svg',
								'option_name' => __( 'Middle', 'gallery-slider-for-woocommerce' ),
							),
						),
						'default'    => 'top_right',
						'dependency' => array( 'lightbox', '==', true ),
					),

					array(
						'id'         => 'lightbox_icon',
						'type'       => 'button_set',
						'class'      => 'btn_icon',
						'title'      => esc_html__( 'Lightbox Icon Style', 'gallery-slider-for-woocommerce' ),
						'options'    => array(
							'search'          => array(
								'option_name' => '<i class="sp_wgs-icon-search"></i>',
							),
							'search-plus'     => array(
								'option_name' => '<i class="sp_wgs-icon-zoom-in-1"></i>',
								'pro_only'    => true,
							),
							'zoom-in'         => array(
								'option_name' => '<i class="sp_wgs-icon-zoom-in"></i>',
								'pro_only'    => true,
							),
							'expand'          => array(
								'option_name' => '<i class="sp_wgs-icon-resize-full"></i>',
								'pro_only'    => true,
							),
							'arrows-alt'      => array(
								'option_name' => '<i class="sp_wgs-icon-resize-full-2"></i>',
								'pro_only'    => true,
							),
							'resize-full-1'   => array(
								'option_name' => '<i class="sp_wgs-icon-resize-full-1"></i>',
								'pro_only'    => true,
							),
							'resize-full-alt' => array(
								'option_name' => '<i class="sp_wgs-icon-resize-full-alt"></i>',
								'pro_only'    => true,
							),
							'eye'             => array(
								'option_name' => '<i class="sp_wgs-icon-eye"></i>',
								'pro_only'    => true,
							),
							'eye-1'           => array(
								'option_name' => '<i class="sp_wgs-icon-eye-1"></i>',
								'pro_only'    => true,
							),
							'plus'            => array(
								'option_name' => '<i class="sp_wgs-icon-plus"></i>',
								'pro_only'    => true,
							),
							'plus-1'          => array(
								'option_name' => '<i class="sp_wgs-icon-plus-1"></i>',
								'pro_only'    => true,
							),
							'info'            => array(
								'option_name' => '<i class="sp_wgs-icon-info"></i>',
								'pro_only'    => true,
							),
							'lightbox-open'            => array(
								'option_name' => '<i class="sp_wgs-icon-lightbox-open"></i>',
								'pro_only'    => true,
							),
						),
						'default'    => 'search',
						'dependency' => array( 'lightbox', '==', true ),
					),
					array(
						'id'         => 'lightbox_icon_size',
						'class'      => 'pro_only_field',
						'type'       => 'spinner',
						'title'      => esc_html__( 'Lightbox Icon Size', 'gallery-slider-for-woocommerce' ),
						'dependency' => array( 'lightbox', '==', true ),
						'default'    => 13,
						'unit'       => 'px',
					),
					array(
						'id'         => 'lightbox_icon_color_group',
						'type'       => 'color_group',
						'title'      => esc_html__( 'Lightbox Icon Color', 'gallery-slider-for-woocommerce' ),
						'options'    => array(
							'color'          => esc_html__( 'Color', 'gallery-slider-for-woocommerce' ),
							'hover_color'    => esc_html__( 'Hover Color', 'gallery-slider-for-woocommerce' ),
							'bg_color'       => esc_html__( 'Background', 'gallery-slider-for-woocommerce' ),
							'hover_bg_color' => esc_html__( 'Hover Background', 'gallery-slider-for-woocommerce' ),
						),
						'default'    => array(
							'color'          => '#fff',
							'hover_color'    => '#fff',
							'bg_color'       => 'rgba(0, 0, 0, 0.5)',
							'hover_bg_color' => 'rgba(0, 0, 0, 0.8)',
						),
						'dependency' => array( 'lightbox', '==', true ),
					),
					array(
						'id'         => 'lightbox_caption',
						'type'       => 'switcher',
						'title'      => esc_html__( 'Lightbox Caption', 'gallery-slider-for-woocommerce' ),
						'text_on'    => esc_html__( 'Show', 'gallery-slider-for-woocommerce' ),
						'text_off'   => esc_html__( 'Hide', 'gallery-slider-for-woocommerce' ),
						'text_width' => 80,
						'default'    => true,
						'dependency' => array( 'lightbox', '==', true ),
					),
					array(
						'id'             => 'lightbox_caption_size',
						'type'           => 'spinner',
						'title'          => esc_html__( 'Lightbox Caption Size', 'gallery-slider-for-woocommerce' ),
						// 'subtitle'   => esc_html__( 'Set lightbox caption size.', 'gallery-slider-for-woocommerce' ),
							'dependency' => array( 'lightbox|lightbox_caption', '==|==', 'true|true' ),
						'default'        => 14,
						'unit'           => 'px',

					),
					array(
						'id'         => 'caption_color',
						'type'       => 'color',
						'title'      => esc_html__( 'Caption Color', 'gallery-slider-for-woocommerce' ),
						'default'    => '#ffffff',
						'dependency' => array( 'lightbox|lightbox_caption', '==|==', 'true|true' ),
					),
					array(
						'id'         => 'l_img_counter',
						'type'       => 'switcher',
						'title'      => esc_html__( 'Image Counter', 'gallery-slider-for-woocommerce' ),
						'text_on'    => esc_html__( 'Show', 'gallery-slider-for-woocommerce' ),
						'text_off'   => esc_html__( 'Hide', 'gallery-slider-for-woocommerce' ),
						'text_width' => 80,
						'default'    => true,
						'dependency' => array( 'lightbox', '==', true ),
					),
					array(
						'id'         => 'slide_play_btn',
						'type'       => 'switcher',
						'class'      => 'pro_switcher',
						'title'      => __( 'Slideshow Play Button', 'gallery-slider-for-woocommerce' ),
						'text_on'    => esc_html__( 'Show', 'gallery-slider-for-woocommerce' ),
						'text_off'   => esc_html__( 'Hide', 'gallery-slider-for-woocommerce' ),
						'text_width' => 80,
						'default'    => false,
						'dependency' => array( 'lightbox', '==', true ),
					),
					array(
						'id'         => 'thumb_gallery_show',
						'type'       => 'switcher',
						'class'      => 'pro_switcher',
						'title'      => esc_html__( 'Thumbnails Gallery Visibility', 'gallery-slider-for-woocommerce' ),
						'text_on'    => esc_html__( 'Show', 'gallery-slider-for-woocommerce' ),
						'text_off'   => esc_html__( 'Hide', 'gallery-slider-for-woocommerce' ),
						'title_help' => '<div class="wcgs-img-tag"><img src="' . plugin_dir_url( __DIR__ ) . '/shapedplugin-framework/assets/images/help-visuals/thumbnails-side-gallery-visibility.svg" alt=""></div><div class="wcgs-info-label">' . __( 'Thumbnails Gallery', 'gallery-slider-for-woocommerce' ) . '</div>',
						'default'    => false,
						'text_width' => 80,
						'dependency' => array( 'lightbox', '==', true ),
					),
					array(
						'id'         => 'side_gallery_btn',
						'class'      => 'pro_switcher',
						'type'       => 'switcher',
						'title'      => esc_html__( 'Thumbnails Gallery Button', 'gallery-slider-for-woocommerce' ),
						'text_on'    => esc_html__( 'Show', 'gallery-slider-for-woocommerce' ),
						'text_off'   => esc_html__( 'Hide', 'gallery-slider-for-woocommerce' ),
						'text_width' => 80,
						'default'    => false,
						'dependency' => array( 'lightbox', '==', true ),
					),

					array(
						'id'         => 'lightbox_thumb_style',
						'type'       => 'select',
						'class'       => 'pro_select',
						'title'      => __( 'Thumbnails Style', 'gallery-slider-for-woocommerce' ),
						'text_on'    => __( 'Show', 'gallery-slider-for-woocommerce' ),
						'text_off'   => __( 'Hide', 'gallery-slider-for-woocommerce' ),
						'options'    => array(
							'classic' => __( 'Classic (Pro)', 'gallery-slider-for-woocommerce' ),
							'modern'  => __( 'Modern (Pro)', 'gallery-slider-for-woocommerce' ),
						),
						'default'    => 'classic',
						'dependency' => array( 'lightbox', '==', 'true' ),
					),
					// array(
					// 	'id'         => 'gallery_share',
					// 	'type'       => 'switcher',
					// 	'title'      => esc_html__( 'Social Share Button', 'gallery-slider-for-woocommerce' ),
					// 	'text_on'    => esc_html__( 'Show', 'gallery-slider-for-woocommerce' ),
					// 	'text_off'   => esc_html__( 'Hide', 'gallery-slider-for-woocommerce' ),
					// 	'text_width' => 80,
					// 	'default'    => false,
					// 	'dependency' => array( 'lightbox', '==', true ),
					// ),
					// array(
					// 	'id'         => 'gallery_dl_btn',
					// 	'type'       => 'switcher',
					// 	'class'      => 'pro_switcher',
					// 	'title'      => esc_html__( 'Download Button', 'gallery-slider-for-woocommerce' ),
					// 	'text_on'    => esc_html__( 'Show', 'gallery-slider-for-woocommerce' ),
					// 	'text_off'   => esc_html__( 'Hide', 'gallery-slider-for-woocommerce' ),
					// 	'text_width' => 80,
					// 	'default'    => false,
					// 	'dependency' => array( 'lightbox', '==', true ),
					// ),
					array(
						'id'         => 'gallery_fs_btn',
						'type'       => 'switcher',
						'title'      => esc_html__( 'Full Screen Button', 'gallery-slider-for-woocommerce' ),
						'text_on'    => esc_html__( 'Show', 'gallery-slider-for-woocommerce' ),
						'text_off'   => esc_html__( 'Hide', 'gallery-slider-for-woocommerce' ),
						'text_width' => 80,
						'default'    => false,
						'dependency' => array( 'lightbox', '==', true ),
					),

					array(
						'id'         => 'gallery_middle_btn',
						'type'       => 'switcher',
						'class'      => 'pro_switcher',
						'title'      => __( 'Transform Controls', 'gallery-slider-for-woocommerce' ),
						'title_help' => '<div class="wcgs-img-tag"><img src="' . plugin_dir_url( __DIR__ ) . '/shapedplugin-framework/assets/images/help-visuals/transform_controls.svg" alt=""></div><div class="wcgs-info-label">' . __( 'Transform Controls', 'gallery-slider-for-woocommerce' ) . '</div>',
						'text_on'    => __( 'Show', 'gallery-slider-for-woocommerce' ),
						'text_off'   => __( 'Hide', 'gallery-slider-for-woocommerce' ),
						'text_width' => 80,
						'default'    => false,
						'dependency' => array( 'lightbox', '==', true ),
					),
					array(
						'id'         => 'shoppage_video_notice',
						'type'       => 'notice',
						'style'      => 'normal',
						'class'      => 'wcgs-light-notice',
						'content'    => sprintf(
							/* translators: 1: start link and strong tag, 2: close link and strong tag, 3: start strong tag, 4: close strong tag. 5: start link and strong tag, 6: close link and strong tag. */
							__( 'Want to unlock the full potential of %1$s Advanced Lightbox%2$s features and %3$sSkyrocket%4$s conversions? %5$sUpgrade To Pro!%6$s', 'gallery-slider-for-woocommerce' ),
							'<a href="https://demo.woogallery.io/lightbox-sliding-effects/" target="_blank" class="btn"> <strong>',
							'</strong></a>',
							'<strong>',
							'</strong>',
							'<a href="https://woogallery.io/pricing/?ref=143" target="_blank" class="btn"><strong>',
							'</strong></a>'
						),
						'dependency' => array( 'lightbox', '==', true ),
					),
				),
			)
		);
	}
}
