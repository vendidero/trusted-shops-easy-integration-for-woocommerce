<?php

namespace Vendidero\TrustedShopsEasyIntegration\Admin;

use Vendidero\TrustedShopsEasyIntegration\Package;

defined( 'ABSPATH' ) || exit;

class Helper {

	/**
	 * Constructor.
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_styles' ), 15 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_scripts' ), 15 );

		add_filter( 'woocommerce_get_settings_pages', array( __CLASS__, 'register_settings' ) );
	}

	public static function register_settings( $settings ) {
		if ( ! Package::is_integration() ) {
			$settings[] = new SettingsPage();
		}

		return $settings;
	}

	public static function get_screen_ids() {
		$screen_ids = array( "woocommerce_page_wc-settings" );

		return $screen_ids;
	}

	public static function admin_styles() {
		wp_register_style( 'ts-easy-integration-admin-style', Package::get_assets_url() . '/admin/base-layer.css', array(), Package::get_version() );

		if ( self::is_settings_request() ) {
			//wp_enqueue_style( 'ts-easy-integration-admin-style' );
		}
	}

	public static function get_current_admin_locale() {
		return Package::get_ts_locale( get_user_locale() );
	}

	public static function admin_scripts() {
		wp_register_script( 'ts-easy-integration-admin-events-external', Package::get_lib_assets_url() . '/events/eventsLib.js', array(), Package::get_version(), true );
		wp_register_script( 'ts-easy-integration-admin', Package::get_assets_url() . '/base-layer.js', array( 'ts-easy-integration-admin-events-external' ), Package::get_version(), true );
		wp_register_script( 'ts-easy-integration-admin-connector-external', Package::get_lib_assets_url() . '/connector/connector.umd.js', array( 'ts-easy-integration-admin' ), Package::get_version(), true );

		if ( self::is_settings_request() ) {
			wp_enqueue_script( 'ts-easy-integration-admin-connector-external' );

			wp_localize_script(
				'ts-easy-integration-admin',
				'ts_easy_integration_params',
				array(
					'ajax_url'              => admin_url( 'admin-ajax.php' ),
					'update_settings_nonce' => wp_create_nonce( 'ts-update-settings' ),
					'get_settings_nonce'    => wp_create_nonce( 'ts-get-settings' ),
					'disconnect_nonce'      => wp_create_nonce( 'ts-disconnect' ),
					'locale'                => self::get_current_admin_locale(),
					'name_of_system'        => 'WooCommerce',
					'version_of_system'     => Package::get_system_version(),
					'version'               => Package::get_version(),
					'sale_channels'         => array_values( Package::get_sale_channels() ),
					'widget_locations'      => array(),
				)
			);
		}
	}

	protected static function is_settings_request() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		if ( in_array( $screen_id, self::get_screen_ids() ) && isset( $_GET['tab'] ) && strstr( $_GET['tab'], 'trusted_shops_easy_integration' ) && current_user_can( 'manage_woocommerce' ) ) {
			return true;
		}

		return false;
	}
}