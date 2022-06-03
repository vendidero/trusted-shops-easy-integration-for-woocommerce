<?php

namespace Vendidero\TrustedShopsEasyIntegration;

defined( 'ABSPATH' ) || exit;

class Hooks {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_scripts' ) );
		add_filter( 'script_loader_tag', array( __CLASS__, 'filter_script_loader_tag' ), 1500, 2 );

		add_action( 'wp_footer', array( __CLASS__, 'fallback_scripts' ), 500 );

		add_action( 'woocommerce_thankyou', array( __CLASS__, 'thankyou' ), 10, 1 );

		add_filter( 'woocommerce_locate_template', array( __CLASS__, 'locate_template_filter' ), 10, 3 );
		add_filter( 'woocommerce_product_tabs', array( __CLASS__, 'unregister_review_tab' ), 50, 1 );
		add_filter( 'woocommerce_product_tabs', array( __CLASS__, 'register_custom_review_tab' ), 50, 1 );

		add_action( 'woocommerce_product_after_tabs', array( __CLASS__, 'single_product_description' ), 20 );
		add_action( 'woocommerce_after_shop_loop_item', array( __CLASS__, 'loop_inner_product' ), 20 );
	}

	protected static function render_single_widget( $ts_widget ) {
		if ( isset( $ts_widget->attributes, $ts_widget->attributes->productIdentifier ) ) {
			Package::get_template( 'widgets/product-widget.php', array( 'ts_widget' => $ts_widget ) );
		} else {
			Package::get_template( 'widgets/service-widget.php', array( 'ts_widget' => $ts_widget ) );
		}
	}

	public static function loop_inner_product() {
		foreach( Package::get_product_widgets_by_location( 'wdg-loc-pl' ) as $ts_widget ) {
			/**
			 * Product star widget is being rendered in a separate location.
			 */
			if ( 'product_star' === $ts_widget->applicationType ) {
				continue;
			}

			Package::get_template( 'widgets/product-widget.php', array( 'ts_widget' => $ts_widget ) );
		}
	}

	public static function single_product_description() {
		foreach( Package::get_widgets_by_location( 'wdg-loc-pd' ) as $ts_widget ) {
			self::render_single_widget( $ts_widget );
		}
	}

	public static function unregister_review_tab( $tabs ) {
		if ( isset( $tabs['reviews'] ) ) {
			unset( $tabs['reviews'] );
		}

		return $tabs;
	}

	public static function register_custom_review_tab( $tabs ) {
		if ( Package::get_widget_by_type( 'product_review_list' ) ) {
			$tabs['ts_reviews'] = array(
				'title'    => _x( 'Reviews', 'trusted-shops', 'trusted-shops-easy-integration' ),
				'priority' => 30,
				'callback' => array( __CLASS__, 'review_tab' ),
			);
		}

		return $tabs;
	}

	public static function review_tab() {
		Package::get_template( 'widgets/product-widget.php', array( 'ts_widget' => Package::get_widget_by_type( 'product_review_list' ) ) );
	}

	public static function embed_widget_script() {
		?>
		<script src="<?php echo esc_url( Package::get_widget_integration_url() ); ?>" async defer></script>
		<?php
	}

	public static function locate_template_filter( $template, $template_name, $template_path ) {
		$is_loop   = 'loop/rating.php' === $template_name;
		$is_single = 'single-product/rating.php' === $template_name;

		if ( $is_single || $is_loop ) {
			$product_star          = Package::get_widget_by_type( 'product_star', $is_single ? 'wdg-loc-pp' : 'wdg-loc-pl' );
			$product_review_list   = Package::get_widget_by_type( 'product_review_list', $is_single ? 'wdg-loc-pp' : 'wdg-loc-pl' );
			$product_rating_widget = false;

			/**
			 * On single product pages, do support anchors in product description too.
			 */
			if ( $is_single && ! $product_review_list ) {
				$product_review_list = Package::get_widget_by_type( 'product_review_list', 'wdg-loc-pd' );
			}

			if ( $product_review_list && isset( $product_review_list->extensions->product_star ) ) {
				$product_rating_widget = $product_review_list->extensions->product_star;
			} elseif ( $product_star ) {
				$product_rating_widget = $product_star;
			}

			if ( $product_rating_widget ) {
				global $ts_widget;
				$ts_widget = $product_rating_widget;

				add_action( 'wp_footer', array( __CLASS__, 'embed_widget_script' ), 500 );
				$template = Package::get_path() . '/templates/widgets/product-widget.php';
			}
		}

		return $template;
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