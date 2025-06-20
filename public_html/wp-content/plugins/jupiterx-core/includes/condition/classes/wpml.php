<?php

/**
 * JupiterX WPML Conditions.
 *
 * @since 4.9.2
 */
class Jupiterx_WPML_Condition {

	/**
	 * Check WPML conditions if match current WordPress page.
	 *
	 * @return boolean
	 */
	public function sub_condition( $condition ) {
		if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
			return false;
		}

		$current_language = apply_filters( 'wpml_current_language', null );

		if ( isset( $condition[2][0] ) && 'all' === $condition[2][0] ) {
			return true;
		}

		if ( isset( $condition[2][0] ) && $condition[2][0] === $current_language ) {
			return true;
		}

		return false;

	}
}
