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
		add_action( 'ts_easy_integration_single_product_rating_widgets', array( __CLASS__, 'single_product_rating_widgets' ) );
		add_action( 'ts_easy_integration_product_loop_rating_widgets', array( __CLASS__, 'product_loop_rating_widgets' ) );

		$theme = function_exists( 'wp_get_theme' ) ? wp_get_theme() : '';

		add_filter( 'woocommerce_product_tabs', array( __CLASS__, 'unregister_review_tab' ), 50, 1 );
		add_filter( 'woocommerce_product_tabs', array( __CLASS__, 'register_custom_review_tab' ), 50, 1 );
		add_action( 'ts_easy_integration_single_product_review_tab_widgets', array( __CLASS__, 'single_product_review_tab_widgets' ) );

		add_action( 'woocommerce_product_after_tabs', array( __CLASS__, 'register_single_product_description' ), 20 );
		add_action( 'ts_easy_integration_product_loop_inner_product_widgets', array( __CLASS__, 'single_product_description_widgets' ) );

		add_action( 'woocommerce_after_shop_loop_item', array( __CLASS__, 'register_product_loop_inner' ), 20 );
		add_action( 'ts_easy_integration_product_loop_inner_widgets', array( __CLASS__, 'product_loop_inner_widgets' ) );

		add_action( 'woocommerce_after_single_product', array( __CLASS__, 'register_single_product' ), 10 );
		add_action( 'ts_easy_integration_single_product_widgets', array( __CLASS__, 'single_product_widgets' ) );

		add_action( 'woocommerce_after_shop_loop', array( __CLASS__, 'register_product_loop' ), 50 );
		add_action( 'ts_easy_integration_product_loop_widgets', array( __CLASS__, 'product_loop_widgets' ) );

		add_action( 'dynamic_sidebar_before', array( __CLASS__, 'register_sidebar' ), 500 );
		add_action( 'ts_easy_integration_sidebar_widgets', array( __CLASS__, 'sidebar_widgets' ) );

		add_action( 'woocommerce_after_main_content', array( __CLASS__, 'register_homepage' ), 20 );
		add_action( 'ts_easy_integration_homepage_widgets', array( __CLASS__, 'homepage_widgets' ) );

		switch ( $theme->get_template() ) {
			case 'storefront':
				add_action( 'storefront_footer', array( __CLASS__, 'register_footer' ), 20 );
				break;
			case 'astra':
				add_action( 'astra_footer_after', array( __CLASS__, 'register_footer' ), 20 );
				break;
			default:
				add_action( 'wp_footer', array( __CLASS__, 'register_footer' ), 1 );
				break;
		}

		add_action( 'ts_easy_integration_footer_widgets', array( __CLASS__, 'footer_widgets' ) );

		add_action( 'wp_body_open', array( __CLASS__, 'register_header' ), 50 );
		add_action( 'ts_easy_integration_header_widgets', array( __CLASS__, 'header_widgets' ) );
	}

	public static function header_widgets() {
		foreach ( Package::get_widgets_by_location( 'wdg-loc-hd' ) as $ts_widget ) {
			self::render_single_widget( $ts_widget );
		}
	}

	public static function register_header() {
		do_action( 'ts_easy_integration_header_widgets' );
	}

	public static function footer_widgets() {
		foreach ( Package::get_widgets_by_location( 'wdg-loc-ft' ) as $ts_widget ) {
			self::render_single_widget( $ts_widget );
		}
	}

	public static function register_footer() {
		do_action( 'ts_easy_integration_footer_widgets' );
	}

	public static function homepage_widgets() {
		foreach ( Package::get_widgets_by_location( 'wdg-loc-hp' ) as $ts_widget ) {
			self::render_single_widget( $ts_widget );
		}
	}

	public static function register_homepage() {
		do_action( 'ts_easy_integration_homepage_widgets' );
	}

	public static function sidebar_widgets() {
		foreach ( Package::get_widgets_by_location( 'wdg-loc-lrm' ) as $ts_widget ) {
			self::render_single_widget( $ts_widget );
		}
	}

	public static function register_sidebar( $sidebar ) {
		if ( apply_filters( 'ts_easy_integration_is_main_sidebar', ( strstr( $sidebar, 'sidebar' ) ), $sidebar ) ) {
			do_action( 'ts_easy_integration_sidebar_widgets' );
		}
	}

	public static function register_product_loop() {
		do_action( 'ts_easy_integration_product_loop_widgets' );
	}

	public static function product_loop_widgets() {
		foreach ( Package::get_service_widgets_by_location( 'wdg-loc-pl' ) as $ts_widget ) {
			self::render_single_widget( $ts_widget );
		}
	}

	public static function register_single_product() {
		do_action( 'ts_easy_integration_single_product_widgets' );
	}

	public static function single_product_widgets() {
		foreach ( Package::get_service_widgets_by_location( 'wdg-loc-pp' ) as $ts_widget ) {
			self::render_single_widget( $ts_widget );
		}
	}

	public static function render_single_widget( $ts_widget ) {
		if ( isset( $ts_widget->attributes, $ts_widget->attributes->productIdentifier ) || 'etrusted-product-review-list-widget-product-star-extension' === $ts_widget->tag ) {
			Package::get_template( 'widgets/product-widget.php', array( 'ts_widget' => $ts_widget ) );
		} else {
			Package::get_template( 'widgets/service-widget.php', array( 'ts_widget' => $ts_widget ) );
		}

		wp_enqueue_script( 'ts-easy-integration-widgets' );
	}

	public static function register_product_loop_inner() {
		do_action( 'ts_easy_integration_product_loop_inner_widgets' );
	}

	public static function product_loop_inner_widgets() {
		foreach ( Package::get_product_widgets_by_location( 'wdg-loc-pl' ) as $ts_widget ) {
			/**
			 * Product star widget is being rendered in a separate location.
			 */
			if ( 'product_star' === $ts_widget->applicationType ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				continue;
			}

			self::render_single_widget( $ts_widget );
		}
	}

	public static function single_product_description_widgets() {
		foreach ( Package::get_widgets_by_location( 'wdg-loc-pd' ) as $ts_widget ) {
			self::render_single_widget( $ts_widget );
		}
	}

	public static function register_single_product_description() {
		do_action( 'ts_easy_integration_single_product_description_widgets' );
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
				'callback' => array( __CLASS__, 'register_review_tab' ),
			);
		}

		return $tabs;
	}

	public static function single_product_review_tab_widgets() {
		if ( $ts_widget = Package::get_widget_by_type( 'product_review_list' ) ) {
			self::render_single_widget( $ts_widget );
		}
	}

	public static function register_review_tab() {
		do_action( 'ts_easy_integration_single_product_review_tab_widgets' );
	}

	public static function product_loop_rating_widgets() {
		$product_star          = Package::get_widget_by_type( 'product_star', 'wdg-loc-pl' );
		$product_review_list   = Package::get_widget_by_type( 'product_review_list', 'wdg-loc-pl' );
		$product_rating_widget = false;

		if ( $product_review_list && isset( $product_review_list->extensions->product_star ) ) {
			$product_rating_widget = $product_review_list->extensions->product_star;
		} elseif ( $product_star ) {
			$product_rating_widget = $product_star;
		}

		if ( $product_rating_widget ) {
			self::render_single_widget( $product_rating_widget );
		}
	}

	public static function single_product_rating_widgets() {
		$product_star          = Package::get_widget_by_type( 'product_star' );
		$product_review_list   = Package::get_widget_by_type( 'product_review_list' );
		$product_rating_widget = false;

		/**
		 * On single product pages, do support anchors in product description too.
		 */
		if ( ! $product_review_list ) {
			$product_review_list = Package::get_widget_by_type( 'product_review_list', 'wdg-loc-pd' );
		}

		if ( $product_review_list && isset( $product_review_list->extensions->product_star ) ) {
			$product_rating_widget = $product_review_list->extensions->product_star;
		} elseif ( $product_star ) {
			$product_rating_widget = $product_star;
		}

		if ( $product_rating_widget ) {
			self::render_single_widget( $product_rating_widget );
		}
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
				if ( $is_single ) {
					$template = Package::get_path() . '/templates/widgets/single-product-rating.php';
				} else {
					$template = Package::get_path() . '/templates/widgets/product-loop-rating.php';
				}
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

					foreach ( $trustbadge->children[0]->attributes as $attribute ) {
						if ( in_array( $attribute->attributeName, array( 'src', 'async', 'defer' ), true ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
							continue;
						}

						$value = isset( $attribute->value ) ? $attribute->value : true;
						$value = is_bool( $value ) ? ( $value ? 'true' : 'false' ) : $value;

						$script_data .= ' ' . esc_attr( $attribute->attributeName ) . "='" . esc_attr( $value ) . "'"; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					}

					echo "<script src='//widgets.trustedshops.com/js/" . esc_attr( $ts_id ) . '.js?ver=' . esc_attr( Package::get_version() ) . "' id='ts-easy-integration-trustbadge-" . esc_attr( $sale_channel ) . "-js' asnyc{$script_data}></script>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.WP.EnqueuedResources.NonEnqueuedScript
				}
			}

			if ( wp_script_is( 'ts-easy-integration-widgets', 'enqueued' ) ) {
				echo "<script src='" . esc_url( Package::get_widget_integration_url() ) . '?ver=' . esc_attr( Package::get_version() ) . "' id='ts-easy-integration-widgets-js' asnyc defer></script>"; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
			}
		}
	}

	public static function filter_script_loader_tag( $tag, $handle ) {
		if ( strstr( $handle, 'ts-easy-integration-' ) ) {
			if ( wp_script_is( $handle, 'registered' ) ) {
				foreach ( wp_scripts()->registered[ $handle ]->extra as $attr => $value ) {
					if ( in_array( $attr, array( 'async', 'defer' ), true ) ) {
						$replacement = " $attr";
					} else {
						$replacement = ' ' . esc_attr( $attr ) . "='" . esc_attr( $value ) . "'";
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
		wp_register_script( 'ts-easy-integration-widgets', Package::get_widget_integration_url(), array(), Package::get_version(), true );
		wp_script_add_data( 'ts-easy-integration-widgets', 'defer', true );
		wp_script_add_data( 'ts-easy-integration-widgets', 'async', true );

		if ( $trustbadge = Package::get_trustbadge() ) {
			$sale_channel = Package::get_current_sale_channel();
			$ts_id        = $trustbadge->id;

			if ( ! empty( $ts_id ) ) {
				wp_register_script( "ts-easy-integration-trustbadge-{$sale_channel}", "//widgets.trustedshops.com/js/{$ts_id}.js", array(), Package::get_version(), true );
				wp_enqueue_script( "ts-easy-integration-trustbadge-{$sale_channel}" );

				foreach ( $trustbadge->children[0]->attributes as $attribute ) {
					if ( 'src' === $attribute->attributeName ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						continue;
					}

					$value = isset( $attribute->value ) ? $attribute->value : true;
					$value = is_bool( $value ) ? ( $value ? 'true' : 'false' ) : $value;

					wp_script_add_data( "ts-easy-integration-trustbadge-{$sale_channel}", $attribute->attributeName, $value ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				}
			}
		}
	}
}
