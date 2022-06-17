<?php

namespace Vendidero\TrustedShopsEasyIntegration\Compatibility;

use Vendidero\TrustedShopsEasyIntegration\Interfaces\Compatibility;
use Vendidero\TrustedShopsEasyIntegration\Package;

defined( 'ABSPATH' ) || exit;

class WPML implements Compatibility {

	public static function is_active() {
		return class_exists( 'SitePress' );
	}

	public static function init() {
		add_filter( 'ts_sales_channels', array( __CLASS__, 'register_sales_channels' ) );
		add_filter( 'ts_easy_integration_current_sales_channel', array( __CLASS__, 'set_current_sales_channel' ) );
	}

	public static function set_current_sales_channel( $current ) {
		global $sitepress;

		$current_sales_channel_id = self::get_sales_channel_id_by_language( $sitepress->get_current_language() );

		return $current_sales_channel_id;
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
					'name'   => sprintf( '%1$s (%2$s)', get_bloginfo( 'name' ), $language_data['native_name'] ),
					'url'    => empty( $language_data['url'] ) ? get_bloginfo( 'url' ) : $language_data['url'],
					'locale' => Package::get_ts_locale( $language_data['default_locale'] ),
				);
			}
		}

		return $sales_channels;
	}
}
