<?php
/**
 * Image alt keywords check.
 *
 * @since   3.4.0
 * @package SmartCrawl
 */

namespace SmartCrawl\Checks;

use SmartCrawl\Html;

/**
 * Class Smartcrawl_Check_Imgalts_Keywords
 */
class Imgalts_Keywords extends Check {

	/**
	 * State of the check.
	 *
	 * @var bool
	 */
	private $state;

	/**
	 * Images count.
	 *
	 * @var int $image_count
	 */
	private $image_count = 0;

	/**
	 * Count of images with focus keywords in alt.
	 *
	 * @var int $images_with_focus_count
	 */
	private $images_with_focus_count = 0;

	/**
	 * Retrieves status text.
	 *
	 * @return string
	 */
	public function get_status_msg() {
		$image_count = $this->image_count ? $this->image_count : 0;

		if ( $this->state ) {
			$message = esc_html__( 'A good balance of images contain the focus keyphrase(s) in their alt attribute text', 'wds' );
		} elseif ( 0 === $image_count && ! $this->has_featured_image() ) {
			$message = esc_html__( 'You have included a focus keyphrase in the alternative text of too many images in your content', 'wds' );
		} else {
			$percentage = $this->get_percentage();
			if ( $percentage > 75 ) {
				$message = esc_html__( 'Too many of your image alt texts contain the focus keyphrase(s)', 'wds' );
			} elseif ( 0 === $percentage ) {
				$message = esc_html__( 'None of your image alt texts contain the focus keyphrase(s)', 'wds' );
			} else {
				$message = esc_html__( 'Too few of your image alt texts contain the focus keyphrase(s)', 'wds' );
			}
		}

		return $message;
	}

	/**
	 * Applies the check to subject.
	 *
	 * @return bool
	 */
	public function apply() {
		$subjects          = Html::find( 'img', $this->get_markup() );
		$this->image_count = count( $subjects );
		if ( empty( $subjects ) ) {
			return false;
		}

		foreach ( $subjects as $subject ) {
			$alt = $subject->getAttribute( 'alt' );

			$this->images_with_focus_count += (int) $this->has_focus( $alt );
		}

		$this->state = $this->is_check_successful();

		return ! ! $this->state;
	}

	/**
	 * Check if check is successful.
	 *
	 * @return bool
	 */
	private function is_check_successful() {
		if ( $this->image_count < 5 ) {
			return (bool) $this->images_with_focus_count;
		} else {
			$percentage = $this->get_percentage();

			return $percentage >= 30 && $percentage <= 75;
		}
	}

	/**
	 * Check if current post has a featured image.
	 *
	 * @since 3.4.0
	 *
	 * @return boolean
	 */
	private function has_featured_image() {
		$post_id = $this->get_post_id();
		// Get parent ID if post revision.
		$post_parent = wp_is_post_revision( $post_id );
		// If it's a revision use parent post ID.
		if ( $post_parent ) {
			$post_id = $post_parent;
		}

		return has_post_thumbnail( $post_id );
	}

	/**
	 * Retrieves percentage of images with focus keywords.
	 *
	 * @return float|int
	 */
	private function get_percentage() {
		$image_count = $this->image_count;
		if ( ! $image_count ) {
			return 0;
		}

		$images_with_focus = $this->images_with_focus_count;

		return $images_with_focus / $image_count * 100;
	}

	/**
	 * Retrieves check result.
	 *
	 * @since 3.4.0
	 *
	 * @return array
	 */
	public function get_result() {
		return array(
			'state'         => $this->state,
			'percent'       => $this->get_percentage(),
			'has_featured'  => $this->has_featured_image(),
			'img_cnt'       => $this->image_count,
			'focus_img_cnt' => $this->images_with_focus_count,
		);
	}
}