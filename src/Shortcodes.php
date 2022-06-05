<?php

namespace Vendidero\TrustedShopsEasyIntegration;

defined( 'ABSPATH' ) || exit;

class Shortcodes {

	public static function init() {
		$shortcodes = array(
			'ts_widget' => __CLASS__ . '::widget',
		);

		foreach ( $shortcodes as $shortcode => $function ) {
			/**
			 * Filter the shortcode tag.
			 *
			 * @param string $shortcode The shortcode name.
			 *
			 * @since 1.0.0
			 *
			 */
			add_shortcode( $shortcode, $function );
		}
	}

	public static function widget( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'sale_channel'       => '',
				'id'                 => '',
				'product_identifier' => '',
			)
		);

		if ( ! empty( $args['product_identifier'] ) ) {
			if ( ! strstr( $args['product_identifier'], 'data-' ) ) {
				$args['product_identifier'] = 'data-' . $args['product_identifier'];
			}
		}

		$ts_widget = array(
			'tag'        => 'etrusted-widget',
			'attributes' => array(
				'id' => array(
					'value' => $args['id'],
				),
			),
		);

		if ( ! empty( $args['product_identifier'] ) ) {
			$ts_widget['attributes']['productIdentifier'] = array(
				'attributeName' => $args['product_identifier'],
			);
		}

		$ts_widget = json_decode( wp_json_encode( $ts_widget ), false );

		if ( ! empty( $ts_widget->attributes->id->value ) ) {
			Hooks::render_single_widget( $ts_widget );
		}
	}
}
