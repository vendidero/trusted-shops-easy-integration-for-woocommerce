<?php

namespace Vendidero\TrustedShopsEasyIntegration;

defined( 'ABSPATH' ) || exit;

class Hooks {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_scripts' ) );
		add_filter( 'script_loader_tag', array( __CLASS__, 'filter_script_loader_tag' ), 1500, 2 );

		add_action( 'wp_footer', array( __CLASS__, 'fallback_scripts' ), 500 );

		add_action( 'woocommerce_thankyou', array( __CLASS__, 'thankyou' ), 10, 1 );
	}

	public static function thankyou( $order_id ) {
		if ( Package::get_trustbadge_id() && ( $order = wc_get_order( $order_id ) ) ) {
			Package::get_template( 'checkout/trustcard.php', array( 'order' => $order ) );
		}
	}

	public static function fallback_scripts() {
		if ( apply_filters( 'ts_easy_integration_enable_fallback_script_embed', false ) ) {
			if ( $trustbadge = Package::get_trustbadge() ) {
				$sale_channel = Package::get_current_sale_channel();
				$ts_id        = $trustbadge->id;

				if ( ! empty( $ts_id ) ) {
					$script_data = '';

					foreach( $trustbadge->children[0]->attributes as $attribute ) {
						if ( in_array( $attribute->attributeName, array( 'src', 'async' ) ) ) {
							continue;
						}

						$value = isset( $attribute->value ) ? $attribute->value : true;
						$value = is_bool( $value ) ? ( $value ? 'true' : 'false' ) : $value;

						$script_data .= " " . esc_attr( $attribute->attributeName ) . "='" . esc_attr( $value ) . "'";
					}

					echo "<script src='//widgets.trustedshops.com/js/{$ts_id}.js?ver=" . esc_attr( Package::get_version() ) . "' id='ts-easy-integration-trustbadge-{$sale_channel}-js' asnyc{$script_data}></script>";
				}
			}
		}
	}

	public static function filter_script_loader_tag( $tag, $handle ) {
		if ( strstr( $handle, 'ts-easy-integration-trustbadge-' ) ) {
			if ( wp_script_is( $handle, 'registered' ) ) {
				foreach( wp_scripts()->registered[ $handle ]->extra	as $attr => $value ) {
					if ( 'async' === $attr ) {
						$replacement = " $attr";
					} else {
						$replacement = " " . esc_attr( $attr ) . "='" . esc_attr( $value ) . "'";
					}

					// Prevent adding attribute when already added.
					if ( ! preg_match( ":\s$attr(=|>|\s):", $tag ) ) {
						$tag = preg_replace( ':(?=></script>):', $replacement, $tag, 1 );
					}
				}
			}
		}

		return $tag;
	}

	public static function register_scripts() {
		if ( $trustbadge = Package::get_trustbadge() ) {
			$sale_channel = Package::get_current_sale_channel();
			$ts_id        = $trustbadge->id;

			if ( ! empty( $ts_id ) ) {
				wp_register_script( "ts-easy-integration-trustbadge-{$sale_channel}", "//widgets.trustedshops.com/js/{$ts_id}.js", array(), Package::get_version(), true );
				wp_enqueue_script( "ts-easy-integration-trustbadge-{$sale_channel}" );

				foreach( $trustbadge->children[0]->attributes as $attribute ) {
					if ( 'src' === $attribute->attributeName ) {
						continue;
					}

					$value = isset( $attribute->value ) ? $attribute->value : true;
					$value = is_bool( $value ) ? ( $value ? 'true' : 'false' ) : $value;

					wp_script_add_data( "ts-easy-integration-trustbadge-{$sale_channel}", $attribute->attributeName , $value );
				}
			}
		}
	}
}