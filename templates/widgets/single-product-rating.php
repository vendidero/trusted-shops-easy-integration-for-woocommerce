<?php
/**
 * The Template is used to embed a service widget.
 *
 * @package TS/Templates
 * @version 1.0.0
 *
 * @var stdClass $ts_widget
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! is_a( $product, 'WC_Product' ) ) {
	return;
}

do_action( 'ts_easy_integration_single_product_rating_widgets' );
?>