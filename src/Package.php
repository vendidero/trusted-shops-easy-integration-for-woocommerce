<?php

namespace Vendidero\TrustedShopsEasyIntegration;

use Vendidero\TrustedShopsEasyIntegration\Admin\Helper;
use Vendidero\TrustedShopsEasyIntegration\API\Events;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Package {

	/**
	 * Version.
	 *
	 * @var string
	 */
	const VERSION = '2.0.3';

	protected static $events_api = null;

	protected static $sales_channels_map = null;

	public static function init() {
		if ( ! self::has_dependencies() ) {
			if ( ! self::is_integration() ) {
				add_action( 'admin_notices', array( __CLASS__, 'dependency_notice' ) );
			}

			return;
		}

		self::init_hooks();

		if ( is_admin() ) {
			Helper::init();
			Ajax::init();
		}

		if ( self::is_frontend() ) {
			if ( did_action( 'woocommerce_loaded' ) ) {
				Hooks::init();
			} else {
				add_action( 'woocommerce_loaded', array( '\Vendidero\TrustedShopsEasyIntegration\Hooks', 'init' ) );
			}
		}

		self::load_compatibilities();
		self::register_order_hooks();

		add_action( 'before_woocommerce_init', array( __CLASS__, 'declare_feature_compatibility' ) );

		do_action( 'ts_easy_integration_init' );
	}

	public static function declare_feature_compatibility() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', trailingslashit( self::get_path() ) . 'trusted-shops-easy-integration-for-woocommerce.php', true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', trailingslashit( self::get_path() ) . 'trusted-shops-easy-integration-for-woocommerce.php', true );
		}
	}

	/**
	 * @return Events
	 */
	public static function get_events_api() {
		if ( is_null( self::$events_api ) ) {
			self::$events_api = new Events();
		}

		return self::$events_api;
	}

	public static function action_links( $links ) {
		return array_merge(
			array(
				'<a href="' . esc_url( self::is_integration() ? admin_url( 'admin.php?page=wc-settings&tab=germanized-trusted_shops_easy_integration' ) : admin_url( 'admin.php?page=wc-settings&tab=trusted_shops_easy_integration' ) ) . '">' . _x( 'Settings', 'trusted-shops', 'trusted-shops-easy-integration-for-woocommerce' ) . '</a>',
			),
			$links
		);
	}

	protected static function register_order_hooks() {
		add_action(
			'woocommerce_order_status_changed',
			function( $order_id, $old_status, $new_status ) {
				if ( $order = wc_get_order( $order_id ) ) {
					$sales_channel = self::get_sales_channel_by_order( $order );

					if ( ! self::is_configured( $sales_channel ) ) {
						return;
					}

					$order_statuses = self::get_woo_order_statuses( $sales_channel );
					$channel_ref    = self::get_etrusted_channel_ref( $sales_channel );

					if ( $order_statuses ) {
						if ( array_key_exists( $new_status, $order_statuses ) ) {
							$event_type = $order_statuses[ $new_status ];

							self::log( sprintf( 'Starting event trigger for order #%1$s as it transitioned from %2$s to %3$s. Sale channel detected: %4$s.', $order->get_order_number(), $old_status, $new_status, $sales_channel ) );
							self::get_events_api()->trigger( $order, $channel_ref, $event_type );
						}
					}
				}
			},
			10,
			3
		);
	}

	protected static function load_compatibilities() {
		$compatibilities = array(
			'wpml' => '\Vendidero\TrustedShopsEasyIntegration\Compatibility\WPML',
		);

		foreach ( $compatibilities as $compatibility ) {
			if ( is_a( $compatibility, '\Vendidero\TrustedShopsEasyIntegration\Interfaces\Compatibility', true ) ) {
				if ( $compatibility::is_active() ) {
					$compatibility::init();
				}
			}
		}
	}

	protected static function is_frontend() {
		return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) && ! self::is_rest_api_request();
	}

	protected static function is_rest_api_request() {
		if ( function_exists( 'WC' ) ) {
			$wc = WC();

			if ( is_callable( array( $wc, 'is_rest_api_request' ) ) ) {
				return $wc->is_rest_api_request();
			}
		}

		return false;
	}

	public static function dependency_notice() {
		?>
		<div class="error notice notice-error"><p><?php echo esc_html_x( 'To use the Trusted Shops Easy Integration for WooCommerce plugin please make sure that WooCommerce is installed and activated.', 'trusted-shops', 'trusted-shops-easy-integration-for-woocommerce' ); ?></p></div>
		<?php
	}

	public static function has_dependencies() {
		return ( class_exists( 'WooCommerce' ) );
	}

	protected static function init_hooks() {
		if ( ! self::is_integration() ) {
			add_action( 'init', array( __CLASS__, 'load_plugin_textdomain' ) );
		}

		add_action( 'init', array( '\Vendidero\TrustedShopsEasyIntegration\Shortcodes', 'init' ) );
	}

	public static function load_plugin_textdomain() {
		add_filter( 'plugin_locale', array( __CLASS__, 'support_german_language_variants' ), 10, 2 );
		add_filter( 'load_translation_file', array( __CLASS__, 'force_load_german_language_variant' ), 10, 2 );

		if ( function_exists( 'determine_locale' ) ) {
			$locale = determine_locale();
		} else {
			$locale = is_admin() ? get_user_locale() : get_locale();
		}

		$locale = apply_filters( 'plugin_locale', $locale, 'trusted-shops-easy-integration-for-woocommerce' );

		load_textdomain( 'trusted-shops-easy-integration-for-woocommerce', trailingslashit( WP_LANG_DIR ) . 'trusted-shops-easy-integration-for-woocommerce/trusted-shops-easy-integration-for-woocommerce-' . $locale . '.mo' );
		load_plugin_textdomain( 'trusted-shops-easy-integration-for-woocommerce', false, plugin_basename( self::get_path() ) . '/i18n/languages/' );
	}

	public static function support_german_language_variants( $locale, $domain ) {
		if ( 'trusted-shops-easy-integration-for-woocommerce' === $domain ) {
			$locale = self::get_german_language_variant( $locale );
		}

		return $locale;
	}

	/**
	 * Use a tweak to force loading german language variants in WP 6.5
	 * as WP does not allow using the plugin_locale filter to load a plugin-specific locale any longer.
	 *
	 * @param $file
	 * @param $domain
	 *
	 * @return mixed
	 */
	public static function force_load_german_language_variant( $file, $domain ) {
		if ( 'trusted-shops-easy-integration-for-woocommerce' === $domain && function_exists( 'determine_locale' ) && class_exists( 'WP_Translation_Controller' ) ) {
			$locale     = determine_locale();
			$new_locale = self::get_german_language_variant( $locale );

			if ( $new_locale !== $locale ) {
				$i18n_controller = \WP_Translation_Controller::get_instance();
				$i18n_controller->load_file( $file, $domain, $locale ); // Force loading the determined file in the original locale.
			}
		}

		return $file;
	}

	protected static function get_german_language_variant( $locale ) {
		if ( apply_filters( 'ts_easy_integration_force_de_language', in_array( $locale, array( 'de_CH', 'de_CH_informal', 'de_AT' ), true ) ) ) {
			$locale = apply_filters( 'ts_easy_integration_german_language_variant_locale', 'de_DE' );
		}

		return $locale;
	}

	public static function install() {
		self::init();
		Install::install();
	}

	public static function uninstall() {
		self::init();
		Install::uninstall();
	}

	/**
	 * Whether debug mode is enabled or not.
	 *
	 * @return bool
	 */
	public static function is_debug_mode() {
		return defined( 'TS_EASY_INTEGRATION_IS_DEBUG_MODE' ) ? TS_EASY_INTEGRATION_IS_DEBUG_MODE : false;
	}

	/**
	 * Whether this installation is an integration (e.g. bundled within Germanized) or standalone.
	 *
	 * @return false
	 */
	public static function is_integration() {
		return class_exists( 'WooCommerce_Germanized' ) && version_compare( get_option( 'woocommerce_gzd_version', '1.0.0' ), '3.10.4', '>=' ) ? true : false;
	}

	/**
	 * Log via the Woo file logger if WP_DEBUG is enabled.
	 *
	 * @param $message
	 * @param $type
	 *
	 * @return bool
	 */
	public static function log( $message, $type = 'info' ) {
		$enable_logging = defined( 'WP_DEBUG' ) && WP_DEBUG ? true : false;

		if ( ! apply_filters( 'ts_easy_integration_enable_logging', $enable_logging ) ) {
			return false;
		}

		$logger = wc_get_logger();

		if ( ! $logger ) {
			return false;
		}

		if ( ! is_callable( array( $logger, $type ) ) ) {
			$type = 'info';
		}

		$logger->{$type}( $message, array( 'source' => 'ts-easy-integration' ) );

		return true;
	}

	private static function is_frontend_request() {
		return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
	}

	public static function get_system_version() {
		return self::is_integration() ? get_option( 'woocommerce_gzd_version' ) : get_option( 'woocommerce_version' );
	}

	/**
	 * Return the version of the package.
	 *
	 * @return string
	 */
	public static function get_version() {
		return self::VERSION;
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_path() {
		return dirname( __DIR__ );
	}

	public static function get_template( $template, $args = array() ) {
		wc_get_template( $template, $args, '', self::get_path() . '/templates/' );
	}

	public static function get_template_html( $template, $args = array() ) {
		return wc_get_template_html( $template, $args, '', self::get_path() . '/templates/' );
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_url() {
		return plugins_url( '', __DIR__ );
	}

	public static function get_assets_url() {
		return self::get_url() . '/dist';
	}

	public static function get_lib_assets_url() {
		return 'https://static-app.connect' . ( self::is_debug_mode() ? '-qa' : '' ) . '.trustedshops.com';
	}

	public static function get_widget_integration_url() {
		return 'https://integrations.etrusted.' . ( self::is_debug_mode() ? 'site' : 'com' ) . '/applications/widget.js/v2';
	}

	/**
	 * @param \WC_Order $order
	 *
	 * @return string
	 */
	public static function get_order_locale( $order ) {
		$locale        = apply_filters( 'ts_easy_integration_order_locale', get_locale(), $order );
		$ts_locale     = strtolower( substr( $locale, 0, 2 ) );
		$new_ts_locale = self::get_ts_locale( $locale );

		if ( $new_ts_locale !== $ts_locale ) {
			$locale = 'en_GB';
		}

		if ( 'en' === $ts_locale ) {
			$locale = 'en_GB';
		}

		return $locale;
	}

	public static function get_ts_locale( $locale ) {
		$ts_locale = strtolower( substr( $locale, 0, 2 ) );

		if ( ! in_array(
			$ts_locale,
			array(
				'de',
				'en',
				'es',
				'fr',
				'it',
				'nl',
				'pt',
				'pl',
			),
			true
		) ) {
			$ts_locale = 'en';
		}

		return $ts_locale;
	}

	/**
	 * Returns available sale channels.
	 *
	 * @return array[]
	 */
	public static function get_sales_channels() {
		$sales_channels = apply_filters(
			'ts_sales_channels',
			array(
				'main' => array(
					'id'     => 'main',
					'name'   => html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
					'url'    => get_bloginfo( 'url' ),
					'locale' => self::get_ts_locale( get_locale() ),
				),
			)
		);

		return $sales_channels;
	}

	/**
	 * @return bool
	 */
	public static function is_connected() {
		$client_id     = self::get_setting( 'client_id', '' );
		$client_secret = self::get_setting( 'client_secret', '' );

		return ! empty( $client_id ) && ! empty( $client_secret );
	}

	public static function get_current_sales_channel() {
		return apply_filters( 'ts_easy_integration_current_sales_channel', 'main' );
	}

	/**
	 * @param \WC_Order $order
	 *
	 * @return string
	 */
	public static function get_sales_channel_by_order( $order ) {
		return apply_filters( 'ts_easy_integration_sales_channel_by_order', 'main', $order );
	}

	/**
	 * Retrieves a sale channel by id.
	 *
	 * @param $id
	 *
	 * @return array|false
	 */
	public static function get_sales_channel( $id ) {
		$channels = self::get_sales_channels();

		if ( array_key_exists( (string) $id, $channels ) ) {
			return $channels[ (string) $id ];
		}

		return false;
	}

	/**
	 * @param $force_refresh
	 *
	 * @return array
	 */
	public static function get_sales_channels_map( $force_refresh = false ) {
		if ( is_null( self::$sales_channels_map ) || $force_refresh ) {
			self::get_channels();
		}

		return self::$sales_channels_map;
	}

	public static function get_etrusted_channel_ref( $sales_channel = '' ) {
		$sales_channel     = '' === $sales_channel ? self::get_current_sales_channel() : $sales_channel;
		$sales_channel_map = self::get_sales_channels_map();
		$sales_channel     = (string) $sales_channel;

		if ( array_key_exists( $sales_channel, $sales_channel_map ) ) {
			return $sales_channel_map[ $sales_channel ];
		} else {
			return false;
		}
	}

	protected static function get_setting_key( $sales_channel = '' ) {
		$sales_channel = '' === $sales_channel ? self::get_current_sales_channel() : $sales_channel;

		if ( $etrusted_channel_ref = self::get_etrusted_channel_ref( $sales_channel ) ) {
			$setting_key = $sales_channel . '_' . $etrusted_channel_ref;
		} else {
			$setting_key = '';
		}

		return $setting_key;
	}

	/**
	 * Get TS mapped channels.
	 *
	 * @return array
	 */
	public static function get_channels() {
		$channels                 = array_filter( (array) self::get_setting( 'channels', array() ) );
		self::$sales_channels_map = array();

		if ( ! empty( $channels ) ) {
			foreach ( $channels as $key => $channel ) {
				/**
				 * Merge channel data with current (subject to change) sale channel data.
				 * Do only include channels with are currently available as a sales channel.
				 */
				if ( $sales_channel = self::get_sales_channel( $channel->salesChannelRef ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$channels[ $key ]->salesChannelLocale = $sales_channel['locale'];
					$channels[ $key ]->salesChannelName   = $sales_channel['name'];
					$channels[ $key ]->salesChannelUrl    = $sales_channel['url'];

					self::$sales_channels_map[ $channel->salesChannelRef ] = $channel->eTrustedChannelRef; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				} else {
					unset( $channels[ $key ] );
				}
			}
		}

		return $channels;
	}

	public static function has_mapped_channel() {
		$mapped = self::get_sales_channels_map();

		return ( ! empty( $mapped ) ? true : false );
	}

	public static function is_configured( $sales_channel = '' ) {
		$sales_channel = '' === $sales_channel ? self::get_current_sales_channel() : $sales_channel;

		if ( self::sales_channel_is_mapped( $sales_channel ) ) {
			return true;
		}

		return false;
	}

	public static function sales_channel_is_mapped( $sales_channel = '' ) {
		$sales_channel = '' === $sales_channel ? self::get_current_sales_channel() : $sales_channel;

		return array_key_exists( (string) $sales_channel, self::get_sales_channels_map() );
	}

	public static function get_trustbadges() {
		$trustbadges = array_filter( (array) self::get_setting( 'trustbadges', array() ) );

		return $trustbadges;
	}

	public static function get_available_order_statuses( $sales_channel = '' ) {
		$sales_channel  = '' === $sales_channel ? self::get_current_sales_channel() : $sales_channel;
		$order_statuses = array();

		foreach ( wc_get_order_statuses() as $order_status_id => $order_status_name ) {
			$order_statuses[] = array(
				'ID'   => $order_status_id,
				'name' => $order_status_name,
			);
		}

		return $order_statuses;
	}

	public static function get_trustbadge( $sales_channel = '' ) {
		$sales_channel = '' === $sales_channel ? self::get_current_sales_channel() : $sales_channel;
		$setting_key   = self::get_setting_key( $sales_channel );
		$trustbadges   = self::get_trustbadges();
		$trustbadge    = ( self::is_configured( $sales_channel ) && array_key_exists( $setting_key, $trustbadges ) ) ? $trustbadges[ $setting_key ] : false;

		return $trustbadge;
	}

	public static function get_trustbadge_id( $sales_channel = '' ) {
		if ( $trustbadge = self::get_trustbadge( $sales_channel ) ) {
			return ! empty( $trustbadge->id ) ? $trustbadge->id : false;
		}

		return false;
	}

	public static function has_valid_trustbadge( $sales_channel = '' ) {
		if ( $trustbadge = self::get_trustbadge( $sales_channel ) ) {
			return ! empty( $trustbadge->id ) ? true : false;
		}

		return false;
	}

	public static function get_used_order_statuses() {
		$used_order_statuses = array_filter( (array) self::get_setting( 'used_order_statuses', array() ) );

		return $used_order_statuses;
	}

	public static function get_woo_order_statuses( $sales_channel = '' ) {
		$sales_channel       = '' === $sales_channel ? self::get_current_sales_channel() : $sales_channel;
		$setting_key         = self::get_setting_key( $sales_channel );
		$used_order_statuses = self::get_used_order_statuses();

		if ( array_key_exists( $setting_key, $used_order_statuses ) ) {
			$used_order_status = wp_parse_args(
				$used_order_statuses[ $setting_key ],
				array(
					'product' => array(),
					'service' => array(),
				)
			);

			$default = array(
				'name'       => '',
				'ID'         => '',
				'event_type' => '',
			);

			$used_order_status['product'] = wp_parse_args( (array) $used_order_status['product'], $default );
			$used_order_status['service'] = wp_parse_args( (array) $used_order_status['service'], $default );

			$used_order_status['product']['ID'] = 'wc-' === substr( $used_order_status['product']['ID'], 0, 3 ) ? substr( $used_order_status['product']['ID'], 3 ) : $used_order_status['product']['ID'];
			$used_order_status['service']['ID'] = 'wc-' === substr( $used_order_status['service']['ID'], 0, 3 ) ? substr( $used_order_status['service']['ID'], 3 ) : $used_order_status['service']['ID'];

			$order_statuses = array_unique(
				array(
					$used_order_status['product']['ID'] => $used_order_status['product']['event_type'],
					$used_order_status['service']['ID'] => $used_order_status['service']['event_type'],
				)
			);

			$order_statuses = array_filter(
				$order_statuses,
				function( $status ) {
					if ( 'checkout' === $status ) {
						return false;
					}

					return true;
				},
				ARRAY_FILTER_USE_KEY
			);
			$order_statuses = array_filter( $order_statuses );
			$order_statuses = array_filter( $order_statuses );

			return empty( $order_statuses ) ? false : $order_statuses;
		} else {
			return false;
		}
	}

	/**
	 * @param \WC_Order $order
	 *
	 * @return string
	 */
	public static function get_order_payment_method( $order ) {
		return $order->get_payment_method_title();
	}

	/**
	 * @param \WC_Product|integer $product
	 * @param string $attribute
	 *
	 * @return string
	 */
	protected static function get_product_data( $product, $attribute, $force_parent = false ) {
		$product = is_numeric( $product ) ? wc_get_product( $product ) : $product;

		if ( ! $product ) {
			return '';
		}

		if ( $force_parent && $product->get_parent_id() ) {
			if ( $parent = wc_get_product( $product->get_parent_id() ) ) {
				return self::get_product_data( $parent, $attribute );
			}
		}

		$getter = "get_{$attribute}";

		if ( '_' !== substr( $attribute, 0, 1 ) && is_callable( array( $product, $getter ) ) ) {
			$data = $product->{$getter}();
		} else {
			$data = $product->get_meta( $attribute, true );
		}

		if ( '' === $data && $product->get_parent_id() ) {
			if ( $parent = wc_get_product( $product->get_parent_id() ) ) {
				return self::get_product_data( $parent, $attribute );
			}
		}

		return apply_filters( 'ts_easy_integration_product_data', $data, $product, $attribute, $force_parent );
	}

	/**
	 * @param \WC_Product|integer $product
	 *
	 * @return string
	 */
	public static function get_product_sku( $product, $force_parent = true ) {
		$sku = self::get_product_data( $product, 'sku', $force_parent );

		if ( empty( $sku ) ) {
			$sku = self::get_product_data( $product, 'id', $force_parent );
		}

		return apply_filters( 'ts_easy_integration_product_sku', $sku, $product, $force_parent );
	}

	/**
	 * @param \WC_Product|integer $product
	 *
	 * @return string
	 */
	public static function get_product_gtin( $product, $force_parent = true ) {
		return apply_filters( 'ts_easy_integration_product_gtin', self::get_product_data( $product, '_ts_gtin', $force_parent ), $product, $force_parent );
	}

	/**
	 * @param \WC_Product|integer $product
	 *
	 * @return string
	 */
	public static function get_product_mpn( $product, $force_parent = true ) {
		return apply_filters( 'ts_easy_integration_product_mpn', self::get_product_data( $product, '_ts_mpn', $force_parent ), $product, $force_parent );
	}

	/**
	 * @param \WC_Product|integer $product
	 *
	 * @return string
	 */
	public static function get_product_image_src( $product ) {
		$product = is_numeric( $product ) ? wc_get_product( $product ) : $product;

		if ( ! $product ) {
			return false;
		}

		$image  = '';
		$images = array();

		if ( $product->get_image_id() ) {
			$images = wp_get_attachment_image_src( $product->get_image_id(), 'woocommerce_thumbnail', false );
		} elseif ( $product->get_parent_id() ) {
			$parent_product = wc_get_product( $product->get_parent_id() );

			if ( $parent_product && $parent_product->get_image_id() ) {
				$images = wp_get_attachment_image_src( $parent_product->get_image_id(), 'woocommerce_thumbnail', false );
			}
		}

		if ( ! empty( $images ) ) {
			$image = $images[0];
		}

		return $image;
	}

	/**
	 * @param \WC_Product|integer $product
	 *
	 * @return string
	 */
	public static function get_product_brand( $product ) {
		$product = is_numeric( $product ) ? wc_get_product( $product ) : $product;

		// Force parent product brand
		if ( $product && $product->get_parent_id() ) {
			$product = wc_get_product( $product->get_parent_id() );
		}

		if ( ! $product ) {
			return '';
		}

		$brand_attribute_name = apply_filters( 'ts_easy_integration_product_brand_attribute_name', _x( 'Brand', 'trusted-shops-brand-attribute', 'trusted-shops-easy-integration-for-woocommerce' ), $product );
		$brand                = '';

		if ( ! empty( $brand_attribute_name ) ) {
			$brand = $product->get_attribute( $brand_attribute_name );
		}

		return apply_filters( 'ts_easy_integration_product_brand', $brand, $product );
	}

	/**
	 * @param \WC_Product|integer $product
	 * @param string $type
	 *
	 * @return string
	 */
	public static function get_product_identifier( $product, $type = 'sku' ) {
		$identifier_sku = self::get_product_sku( $product );

		if ( empty( $identifier_sku ) ) {
			$identifier_sku = self::get_product_data( $product, 'id', true );
		}

		$identifier = $identifier_sku;

		if ( 'gtin' === $type ) {
			$identifier = self::get_product_gtin( $product );
		} elseif ( 'mpn' === $type ) {
			$identifier = self::get_product_mpn( $product );
		}

		/**
		 * Fallback to SKU
		 */
		if ( empty( $identifier ) ) {
			$identifier = $identifier_sku;
		}

		return $identifier;
	}

	public static function get_product_identifier_name( $widget_product_identifier ) {
		$identifier = 'sku';

		if ( 'data-gtin' === $widget_product_identifier ) {
			$identifier = 'gtin';
		} elseif ( 'data-mpn' === $widget_product_identifier ) {
			$identifier = 'mpn';
		}

		return $identifier;
	}

	public static function get_widget_by_type( $type, $location = 'wdg-loc-pp', $sales_channel = '' ) {
		$widgets = self::get_widgets( $sales_channel );
		$widget  = false;

		foreach ( $widgets as $inner_widget ) {
			if ( $type === $inner_widget->applicationType && $inner_widget->widgetLocation->id === $location ) {  // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$widget = $inner_widget;
				break;
			}
		}

		return $widget;
	}

	public static function get_widgets_by_location( $location = 'wdg-loc-pp', $sales_channel = '' ) {
		$widgets            = self::get_widgets( $sales_channel );
		$widgets_applicable = array();

		foreach ( $widgets as $inner_widget ) {
			if ( $inner_widget->widgetLocation->id === $location ) {  // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$widgets_applicable[] = $inner_widget;
			}
		}

		return $widgets_applicable;
	}

	public static function get_product_widgets_by_location( $location = 'wdg-loc-pp', $sales_channel = '' ) {
		$widgets            = self::get_widgets( $sales_channel );
		$widgets_applicable = array();

		foreach ( $widgets as $inner_widget ) {
			if ( $inner_widget->widgetLocation->id === $location ) {  // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				if ( isset( $inner_widget->attributes, $inner_widget->attributes->productIdentifier ) ) {
					$widgets_applicable[] = $inner_widget;
				}
			}
		}

		return $widgets_applicable;
	}

	public static function get_service_widgets_by_location( $location = 'wdg-loc-pp', $sales_channel = '' ) {
		$widgets            = self::get_widgets( $sales_channel );
		$widgets_applicable = array();

		foreach ( $widgets as $inner_widget ) {
			if ( $inner_widget->widgetLocation->id === $location ) {  // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				if ( ! isset( $inner_widget->attributes ) || ! isset( $inner_widget->attributes->productIdentifier ) ) {
					$widgets_applicable[] = $inner_widget;
				}
			}
		}

		return $widgets_applicable;
	}

	public static function get_widgets( $sales_channel = '' ) {
		$sales_channel = '' === $sales_channel ? self::get_current_sales_channel() : $sales_channel;
		$setting_key   = self::get_setting_key( $sales_channel );
		$widgets       = array_filter( (array) self::get_setting( 'widgets', array() ) );

		if ( false !== $sales_channel && ! self::is_configured( $sales_channel ) ) {
			return array();
		}

		if ( ! empty( $setting_key ) ) {
			$widgets = array_key_exists( $setting_key, $widgets ) ? $widgets[ $setting_key ] : array();

			if ( ! empty( $widgets ) ) {
				$widgets = isset( $widgets->children ) ? $widgets->children[0]->children : array();
			}
		}

		return $widgets;
	}

	/**
	 * Returns TS settings.
	 *
	 * @param $name
	 * @param $default
	 *
	 * @return mixed
	 */
	public static function get_setting( $name, $default = false ) {
		$option_name  = "ts_easy_integration_{$name}";
		$option_value = get_option( $option_name, $default );

		if ( ! empty( $option_value ) && in_array( $name, array( 'client_id', 'client_secret', 'access_token' ), true ) ) {
			$option_value = SecretsHelper::decrypt( $option_value );

			if ( is_wp_error( $option_value ) ) {
				$option_value = '';
			}
		}

		return $option_value;
	}

	/**
	 * Get TS settings.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$settings = array(
			'channels'            => self::get_channels(),
			'trustbadges'         => self::get_trustbadges(),
			'widgets'             => self::get_widgets( false ),
			'used_order_statuses' => self::get_used_order_statuses(),
		);

		return $settings;
	}

	/**
	 * Update a specific TS setting.
	 *
	 * @param $name
	 * @param $value
	 *
	 * @return bool|\WP_Error
	 */
	public static function update_setting( $name, $value ) {
		$option_name = "ts_easy_integration_{$name}";

		if ( ! empty( $value ) && in_array( $name, array( 'client_id', 'client_secret', 'access_token' ), true ) ) {
			$value = SecretsHelper::encrypt( $value );

			/**
			 * In case encryption fails, returns false.
			 */
			if ( is_wp_error( $value ) ) {
				return $value;
			}

			do_action( 'ts_easy_integration_connected' );
		}

		/**
		 * Somehow there seems to be a bug/issue with the cache (tested at least for the channels option)
		 * which might lead to wrong serialized data being returned.
		 * Delete the option before updating to overcome the issue.
		 */
		delete_option( $option_name );
		update_option( $option_name, $value );
		wp_cache_delete( $option_name, 'options' );

		/**
		 * Clear sale channel mapping cache.
		 */
		if ( 'channels' === $name ) {
			self::$sales_channels_map = null;
		}

		return true;
	}

	/**
	 * Deletes all TS settings stored in DB.
	 *
	 * @return bool
	 */
	public static function delete_settings( $channel_key = '' ) {
		if ( empty( $channel_key ) ) {
			delete_option( 'ts_easy_integration_client_id' );
			delete_option( 'ts_easy_integration_client_secret' );
			delete_option( 'ts_easy_integration_access_token' );

			foreach ( self::get_settings() as $name => $value ) {
				$option_name = "ts_easy_integration_{$name}";
				delete_option( $option_name );
			}

			do_action( 'ts_easy_integration_disconnected' );
		} else {
			foreach ( self::get_settings() as $name => $value ) {
				$option_value = get_option( "ts_easy_integration_{$name}", array() );

				if ( $option_value && is_array( $option_value ) ) {
					if ( array_key_exists( $channel_key, $option_value ) ) {
						unset( $option_value[ $channel_key ] );
					} elseif ( in_array( $channel_key, $option_value ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
						$option_value = array_diff( $channel_key, $option_value );
					}

					update_option( "ts_easy_integration_{$name}", $option_value );
				}
			}
		}

		return true;
	}
}
