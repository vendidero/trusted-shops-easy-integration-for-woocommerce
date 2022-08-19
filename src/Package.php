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
	const VERSION = '1.0.1';

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

		do_action( 'ts_easy_integration_init' );
	}

    public static function action_links( $links ) {
	    return array_merge(
		    array(
			    '<a href="' . esc_url( self::is_integration() ? admin_url( 'admin.php?page=wc-settings&tab=germanized-trusted_shops_easy_integration' ) : admin_url( 'admin.php?page=wc-settings&tab=trusted_shops_easy_integration' ) ) . '">' . _x( 'Settings', 'trusted-shops', 'trusted-shops-easy-integration' ) . '</a>',
		    ),
		    $links
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
		return class_exists( 'WooCommerce_Germanized' ) && version_compare( get_option( 'woocommerce_gzd_version', '1.0.0' ), '3.11.0', '>=' ) ? true : false;
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
	public static function get_sales_channels() {
		return apply_filters(
			'ts_sales_channels',
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

	public static function get_current_sales_channel() {
		return apply_filters( 'ts_easy_integration_current_sales_channel', 'main' );
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

		if ( array_key_exists( $id, $channels ) ) {
			return $channels[ $id ];
		}

		return false;
	}

	public static function get_sales_channels_map( $force_refresh = false ) {
		if ( is_null( self::$sales_channels_map ) || $force_refresh ) {
			self::get_channels();
		}

		return self::$sales_channels_map;
	}

	public static function get_etrusted_channel_ref( $sales_channel = '' ) {
		$sales_channel     = '' === $sales_channel ? self::get_current_sales_channel() : $sales_channel;
		$sales_channel_map = self::get_sales_channels_map();

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

		return array_key_exists( $sales_channel, self::get_sales_channels_map() );
	}

	public static function get_trustbadges() {
		$trustbadges = array_filter( (array) self::get_setting( 'trustbadges', array() ) );

		return $trustbadges;
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

	public static function get_enable_review_invites() {
		$invites = array_filter( (array) self::get_setting( 'enable_invites', array() ) );

		return $invites;
	}

	public static function enable_review_invites( $sales_channel = '' ) {
		$setting_key = self::get_setting_key( $sales_channel );
		$invites     = self::get_enable_review_invites();

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
			$sku = $product->get_id();
		}

		return $sku;
	}

	/**
	 * @param \WC_Product|integer $product
	 *
	 * @return string
	 */
	public static function get_product_gtin( $product, $force_parent = true ) {
		return self::get_product_data( $product, '_ts_gtin', $force_parent );
	}

	/**
	 * @param \WC_Product|integer $product
	 *
	 * @return string
	 */
	public static function get_product_mpn( $product, $force_parent = true ) {
		return self::get_product_data( $product, '_ts_mpn', $force_parent );
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

		$brand_attribute_name = apply_filters( 'ts_easy_integration_product_brand_attribute_name', _x( 'Brand', 'trusted-shops-brand-attribute', 'trusted-shops-easy-integration' ), $product );
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

			foreach ( self::get_settings() as $name => $value ) {
				$option_name = "ts_easy_integration_{$name}";
				delete_option( $option_name );
			}
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
