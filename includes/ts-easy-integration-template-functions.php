<?php

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'woocommerce_template_single_rating' ) ) {

	/**
	 * Output the product rating.
	 */
	function woocommerce_template_single_rating() {
		wc_get_template( 'single-product/rating.php' );
	}
}
