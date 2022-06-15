<?php

namespace Vendidero\TrustedShopsEasyIntegration;

use Vendidero\TrustedShopsEasyIntegration\Admin\Helper;

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
	const VERSION = '1.0.1-alpha';

	protected static $sale_channels_map = null;

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

		do_action( 'ts_easy_integration_init' );
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
		<div class="error notice notice-error"><p><?php echo esc_html_x( 'To use the Trusted Shops Easy Integration for WooCommerce plugin please make sure that WooCommerce is installed and activated.', 'trusted-shops', 'trusted-shops-easy-integration' ); ?></p></div>
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
		if ( function_exists( 'determine_locale' ) ) {
			$locale = determine_locale();
		} else {
			$locale = is_admin() ? get_user_locale() : get_locale();
		}

		$locale = apply_filters( 'plugin_locale', $locale, 'trusted-shops-easy-integration' );

		unload_textdomain( 'trusted-shops-easy-integration' );
		load_textdomain( 'trusted-shops-easy-integration', trailingslashit( WP_LANG_DIR ) . 'trusted-shops-easy-integration/trusted-shops-easy-integration-' . $locale . '.mo' );
		load_plugin_textdomain( 'trusted-shops-easy-integration', false, plugin_basename( dirname( __FILE__ ) ) . '/i18n/languages/' );
	}

	public static function install() {
		self::init();
		Install::install();
	}

	/**
	 * Whether debug mode is enabled or not.
	 *
	 * @TODO Disable before release.
	 *
	 * @return bool
	 */
	public static function is_debug_mode() {
		return true;
	}

	/**
	 * Whether this installation is an integration (e.g. bundled within Germanized) or standalone.
	 *
	 * @TODO Need to check for Germanized existence.
	 *
	 * @return false
	 */
	public static function is_integration() {
		return false;

		return class_exists( 'WooCommerce_Germanized' ) ? true : false;
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
	public static function get_sale_channels() {
		return apply_filters(
			'ts_sale_channels',
			array(
				'main' => array(
					'id'     => 'main',
					'name'   => get_bloginfo( 'name' ),
					'url'    => get_bloginfo( 'url' ),
					'locale' => self::get_ts_locale( get_locale() ),
				),
			)
		);
	}

	public static function get_current_sale_channel() {
		return apply_filters( 'ts_easy_integration_current_sale_channel', 'main' );
	}

	/**
	 * Retrieves a sale channel by id.
	 *
	 * @param $id
	 *
	 * @return array|false
	 */
	public static function get_sale_channel( $id ) {
		$channels = self::get_sale_channels();

		if ( array_key_exists( $id, $channels ) ) {
			return $channels[ $id ];
		}

		return false;
	}

	public static function get_etrusted_channel_ref( $sale_channel = '' ) {
		$sale_channel = '' === $sale_channel ? self::get_current_sale_channel() : $sale_channel;

		if ( is_null( self::$sale_channels_map ) ) {
			self::get_channels();
		}

		if ( array_key_exists( $sale_channel, self::$sale_channels_map ) ) {
			return self::$sale_channels_map[ $sale_channel ];
		} else {
			return false;
		}
	}

	protected static function get_setting_key( $sale_channel = '' ) {
		$sale_channel = '' === $sale_channel ? self::get_current_sale_channel() : $sale_channel;

		if ( $etrusted_channel_ref = self::get_etrusted_channel_ref( $sale_channel ) ) {
			$setting_key = $sale_channel . '_' . $etrusted_channel_ref;
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
		$channels                = array_filter( (array) self::get_setting( 'channels', array() ) );
		self::$sale_channels_map = array();

		if ( ! empty( $channels ) ) {
			foreach ( $channels as $key => $channel ) {
				/**
				 * Merge channel data with current (subject to change) sale channel data.
				 */
				if ( $sale_channel = self::get_sale_channel( $channel->salesChannelRef ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$channels[ $key ]->salesChannelLocale = $sale_channel['locale'];
					$channels[ $key ]->salesChannelName   = $sale_channel['name'];
					$channels[ $key ]->salesChannelUrl    = $sale_channel['url'];
				}

				self::$sale_channels_map[ $channel->salesChannelRef ] = $channel->eTrustedChannelRef;
			}
		}

		return $channels;
	}

	public static function get_trustbadges() {
		$trustbadges = array_filter( (array) self::get_setting( 'trustbadges', array() ) );

		return $trustbadges;
	}

	public static function get_trustbadge( $sale_channel = '' ) {
		$setting_key  = self::get_setting_key( $sale_channel );
		$trustbadges  = self::get_trustbadges();
		$trustbadge   = array_key_exists( $setting_key, $trustbadges ) ? $trustbadges[ $setting_key ] : false;

		return $trustbadge;
	}

	public static function get_trustbadge_id( $sale_channel = '' ) {
		if ( $trustbadge = self::get_trustbadge( $sale_channel ) ) {
			return ! empty( $trustbadge->id ) ? $trustbadge->id : false;
		}

		return false;
	}

	public static function has_valid_trustbadge( $sale_channel = '' ) {
		if ( $trustbadge = self::get_trustbadge( $sale_channel ) ) {
			return ! empty( $trustbadge->id ) ? true : false;
		}

		return false;
	}

	public static function get_enable_review_invites() {
		$invites = array_filter( (array) self::get_setting( 'enable_invites', array() ) );

		return $invites;
	}

	public static function enable_review_invites( $sale_channel = '' ) {
		$setting_key  = self::get_setting_key( $sale_channel );
		$invites      = self::get_enable_review_invites();

		return in_array( $setting_key, $invites, true ) ? true : false;
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
	 * @param \WC_Product $product
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
	 * @param \WC_Product $product
	 *
	 * @return string
	 */
	public static function get_product_sku( $product, $force_parent = true ) {
		$sku = self::get_product_data( $product, 'sku', $force_parent );

		if ( empty( $sku ) ) {
			$sku = $product->get_id();
		}

		return $sku;
	}

	/**
	 * @param \WC_Product $product
	 *
	 * @return string
	 */
	public static function get_product_gtin( $product, $force_parent = true ) {
		return self::get_product_data( $product, '_ts_gtin', $force_parent );
	}

	/**
	 * @param \WC_Product $product
	 *
	 * @return string
	 */
	public static function get_product_mpn( $product, $force_parent = true ) {
		return self::get_product_data( $product, '_ts_mpn', $force_parent );
	}

	/**
	 * @param \WC_Product $product
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
	 * @param \WC_Product $product
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

		$brand_attribute_name = apply_filters( 'ts_easy_integration_product_brand_attribute_name', '', $product );
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

	public static function get_widget_by_type( $type, $location = 'wdg-loc-pp', $sale_channel = '' ) {
		$widgets = self::get_widgets( $sale_channel );
		$widget  = false;

		foreach ( $widgets as $inner_widget ) {
			if ( $type === $inner_widget->applicationType && $inner_widget->widgetLocation->id === $location ) {  // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$widget = $inner_widget;
				break;
			}
		}

		return $widget;
	}

	public static function get_widgets_by_location( $location = 'wdg-loc-pp', $sale_channel = '' ) {
		$widgets            = self::get_widgets( $sale_channel );
		$widgets_applicable = array();

		foreach ( $widgets as $inner_widget ) {
			if ( $inner_widget->widgetLocation->id === $location ) {  // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$widgets_applicable[] = $inner_widget;
			}
		}

		return $widgets_applicable;
	}

	public static function get_product_widgets_by_location( $location = 'wdg-loc-pp', $sale_channel = '' ) {
		$widgets            = self::get_widgets( $sale_channel );
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

	public static function get_service_widgets_by_location( $location = 'wdg-loc-pp', $sale_channel = '' ) {
		$widgets            = self::get_widgets( $sale_channel );
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

	public static function get_widgets( $sale_channel = '' ) {
		$setting_key  = self::get_setting_key( $sale_channel );
		$widgets      = array_filter( (array) self::get_setting( 'widgets', array() ) );

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

		if ( ! empty( $option_value ) && in_array( $name, array( 'client_id', 'client_secret' ), true ) ) {
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
			'channels'       => self::get_channels(),
			'trustbadges'    => self::get_trustbadges(),
			'widgets'        => self::get_widgets( false ),
			'enable_invites' => self::get_enable_review_invites(),
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

		if ( ! empty( $value ) && in_array( $name, array( 'client_id', 'client_secret' ), true ) ) {
			$value = SecretsHelper::encrypt( $value );

			/**
			 * In case encryption fails, returns false.
			 */
			if ( is_wp_error( $value ) ) {
				return $value;
			}
		}

		update_option( $option_name, $value );

		/**
		 * Clear sale channel mapping cache.
		 */
		if ( 'channels' === $name ) {
			self::$sale_channels_map = null;
		}

		return true;
	}

	/**
	 * Deletes all TS settings stored in DB.
	 *
	 * @return bool
	 */
	public static function delete_settings() {
		delete_option( 'ts_easy_integration_client_id' );
		delete_option( 'ts_easy_integration_client_secret' );

		foreach ( self::get_settings() as $name => $value ) {
			$option_name = "ts_easy_integration_{$name}";
			delete_option( $option_name );
		}

		return true;
	}
}
