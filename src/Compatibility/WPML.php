<?php

namespace Vendidero\TrustedShopsEasyIntegration\Compatibility;

use Vendidero\TrustedShopsEasyIntegration\Interfaces\Compatibility;
use Vendidero\TrustedShopsEasyIntegration\OrderExporter;
use Vendidero\TrustedShopsEasyIntegration\Package;

defined( 'ABSPATH' ) || exit;

class WPML implements Compatibility {

	public static function is_active() {
		return class_exists( 'SitePress' );
	}

	public static function init() {
		add_filter( 'ts_sales_channels', array( __CLASS__, 'register_sales_channels' ) );
		add_filter( 'ts_easy_integration_current_sales_channel', array( __CLASS__, 'set_current_sales_channel' ) );
		add_filter( 'ts_easy_integration_sales_channel_by_order', array( __CLASS__, 'sales_channel_by_order' ), 10, 2 );
		add_filter( 'ts_easy_integration_order_locale', array( __CLASS__, 'order_locale' ), 10, 2 );

		add_action( 'ts_easy_integration_before_get_orders_for_export', array( __CLASS__, 'register_lang_meta_query' ) );
		add_action( 'ts_easy_integration_after_get_orders_for_export', array( __CLASS__, 'unregister_lang_meta_query' ) );
		add_filter( 'ts_easy_integration_order_export_args', array( __CLASS__, 'export_args' ), 10, 2 );
	}

	/**
	 * @param mixed $args
	 * @param OrderExporter $exporter
	 *
	 * @return mixed
	 */
	public static function export_args( $args, $exporter ) {
		global $sitepress;

		$sales_channel = $exporter->get_sales_channel();
		$wpml_lang     = '';

		if ( 'main' === $sales_channel ) {
			$wpml_lang = $sitepress->get_default_language();
		} else {
			$parts = explode( '-', $sales_channel );

			if ( count( $parts ) > 1 && 'wpml' === $parts[0] && ! empty( $parts[1] ) ) {
				$wpml_lang = $parts[1];
			}
		}

		if ( ! empty( $wpml_lang ) ) {
			$args['wpml_language'] = strtolower( $wpml_lang );
		}

		return $args;
	}

	public static function unregister_lang_meta_query() {
		remove_filter( 'woocommerce_order_data_store_cpt_get_orders_query', array( __CLASS__, 'wpml_lang_query' ), 10 );
	}

	public static function register_lang_meta_query() {
		add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', array( __CLASS__, 'wpml_lang_query' ), 10, 2 );
	}

	public static function wpml_lang_query( $query, $query_vars ) {
		global $sitepress;

		if ( isset( $query_vars['wpml_language'] ) && ! empty( $query_vars['wpml_language'] ) ) {
			if ( $query_vars['wpml_language'] === $sitepress->get_default_language() ) {
				$query['meta_query'][] = array(
					'relation' => 'OR',
					array(
						'key'     => 'wpml_language',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => 'wpml_language',
						'compare' => '=',
						'value'   => esc_html( $query_vars['wpml_language'] ),
					),
				);
			} else {
				$query['meta_query'][] = array(
					array(
						'key'     => 'wpml_language',
						'compare' => '=',
						'value'   => esc_html( $query_vars['wpml_language'] ),
					),
				);
			}
		}

		return $query;
	}

	public static function set_current_sales_channel( $current ) {
		global $sitepress;

		$current_sales_channel_id = self::get_sales_channel_id_by_language( $sitepress->get_current_language() );

		return $current_sales_channel_id;
	}

	/**
	 * @param string $sales_channel
	 * @param \WC_Order $order
	 *
	 * @return string
	 */
	public static function sales_channel_by_order( $sales_channel, $order ) {
		if ( $wpml_lang = $order->get_meta( 'wpml_language' ) ) {
			$sales_channel = self::get_sales_channel_id_by_language( $wpml_lang );
		}

		return $sales_channel;
	}

	/**
	 * @param string $locale
	 * @param \WC_Order $order
	 *
	 * @return string
	 */
	public static function order_locale( $locale, $order ) {
		global $sitepress;

		if ( $wpml_lang = $order->get_meta( 'wpml_language' ) ) {
			if ( isset( $sitepress ) && is_callable( array( $sitepress, 'get_locale' ) ) ) {
				return $sitepress->get_locale( $wpml_lang );
			}
		}

		return $locale;
	}

	protected static function get_sales_channel_id_by_language( $lang_code ) {
		global $sitepress;

		$default_language = $sitepress->get_default_language();

		if ( $default_language === $lang_code ) {
			return 'main';
		} else {
			return 'wpml-' . sanitize_key( $lang_code );
		}
	}

	public static function register_sales_channels( $sales_channels ) {
		global $sitepress;

		$active_languages = $sitepress->get_active_languages();
		$default_language = $sitepress->get_default_language();

		foreach ( $active_languages as $language_data ) {
			$language_data = wp_parse_args(
				$language_data,
				array(
					'default_locale' => '',
					'native_name'    => '',
					'url'            => '',
					'code'           => '',
				)
			);

			if ( $language_data['code'] === $default_language ) {
				$sales_channels['main']['locale'] = Package::get_ts_locale( $language_data['default_locale'] );
			} else {
				$sales_channel_id = self::get_sales_channel_id_by_language( $language_data['code'] );

				$sales_channels[ $sales_channel_id ] = array(
					'id'     => $sales_channel_id,
					'name'   => sprintf( '%1$s (%2$s)', html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $language_data['native_name'] ),
					'url'    => empty( $language_data['url'] ) ? get_bloginfo( 'url' ) : $language_data['url'],
					'locale' => Package::get_ts_locale( $language_data['default_locale'] ),
				);
			}
		}

		return $sales_channels;
	}
}
