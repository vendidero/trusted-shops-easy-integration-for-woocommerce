<?php
/**
 * The Template is used to embed a service widget.
 *
 * @package TS/Templates
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! is_a( $product, 'WC_Product' ) ) {
	return;
}

do_action( 'ts_easy_integration_product_loop_rating_widgets' );
?>