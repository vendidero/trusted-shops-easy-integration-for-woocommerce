<?php

namespace Vendidero\TrustedShopsEasyIntegration\Admin;

use Vendidero\TrustedShopsEasyIntegration\Package;

defined( 'ABSPATH' ) || exit;

class Helper {

	/**
	 * Constructor.
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_scripts' ), 15 );

		add_filter( 'woocommerce_get_settings_pages', array( __CLASS__, 'register_settings' ) );

		add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'product_options' ), 9 );
		add_action( 'woocommerce_product_after_variable_attributes', array( __CLASS__, 'product_options_variable' ), 20, 3 );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save_product' ), 10, 1 );
		add_action( 'woocommerce_admin_process_variation_object', array( __CLASS__, 'save_variation' ), 10, 2 );
	}

	public static function save_variation( $variation, $i ) {
		if ( Package::is_integration() ) {
			return;
		}

		if ( $variation ) {
			if ( isset( $_POST['variable_ts_gtin'][ $i ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$variation->update_meta_data( '_ts_gtin', trim( wc_clean( wp_unslash( $_POST['variable_ts_gtin'][ $i ] ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			}

			if ( isset( $_POST['variable_ts_mpn'][ $i ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$variation->update_meta_data( '_ts_mpn', trim( wc_clean( wp_unslash( $_POST['variable_ts_mpn'][ $i ] ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			}
		}
	}

	public static function save_product( $product ) {
		if ( Package::is_integration() ) {
			return;
		}

		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );
		}

		if ( isset( $_POST['_ts_gtin'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$product->update_meta_data( '_ts_gtin', trim( wc_clean( wp_unslash( $_POST['_ts_gtin'] ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}

		if ( isset( $_POST['_ts_mpn'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$product->update_meta_data( '_ts_mpn', trim( wc_clean( wp_unslash( $_POST['_ts_mpn'] ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}
	}

	public static function product_options_variable( $loop, $variation_data, $variation ) {
		if ( Package::is_integration() ) {
			return;
		}

		$_product = wc_get_product( $variation );
		$_parent  = wc_get_product( $_product->get_parent_id() );

		echo '<div class="variable_gzd_ts_labels">';

		woocommerce_wp_text_input(
			array(
				'wrapper_class' => 'form-row form-row-first',
				'id'            => "variable_ts_gtin{$loop}",
				'name'          => "variable_ts_gtin[{$loop}]",
				'placeholder'   => $_parent->get_meta( '_ts_gtin' ) ? $_parent->get_meta( '_ts_gtin' ) : '',
				'value'         => $_product->get_meta( '_ts_gtin' ) ? $_product->get_meta( '_ts_gtin' ) : '',
				'label'         => _x( 'GTIN', 'trusted-shops', 'trusted-shops-easy-integration' ),
				'data_type'     => 'text',
				'desc_tip'      => true,
				'description'   => _x( 'ID that allows your products to be identified worldwide. If you want to display your Trusted Shops Product Reviews in Google Shopping and paid Google adverts, Google needs the GTIN.', 'trusted-shops', 'trusted-shops-easy-integration' ),
			)
		);

		woocommerce_wp_text_input(
			array(
				'wrapper_class' => 'form-row form-row-last',
				'id'            => "variable_ts_mpn{$loop}",
				'name'          => "variable_ts_mpn[{$loop}]",
				'placeholder'   => $_parent->get_meta( '_ts_mpn' ) ? $_parent->get_meta( '_ts_mpn' ) : '',
				'value'         => $_product->get_meta( '_ts_mpn' ) ? $_product->get_meta( '_ts_mpn' ) : '',
				'label'         => _x( 'MPN', 'trusted-shops', 'trusted-shops-easy-integration' ),
				'data_type'     => 'text',
				'desc_tip'      => true,
				'description'   => _x( 'If you don\'t have a GTIN for your products, you can pass the brand name and the MPN on to Google to use the Trusted Shops Google Integration.', 'trusted-shops', 'trusted-shops-easy-integration' ),
			)
		);

		echo '</div>';
	}

	public static function product_options() {
		if ( Package::is_integration() ) {
			return;
		}

		echo '<div class="options_group show_if_simple show_if_external show_if_variable">';

		woocommerce_wp_text_input(
			array(
				'id'          => '_ts_gtin',
				'label'       => _x( 'GTIN', 'trusted-shops', 'trusted-shops-easy-integration' ),
				'data_type'   => 'text',
				'desc_tip'    => true,
				'description' => _x( 'ID that allows your products to be identified worldwide. If you want to display your Trusted Shops Product Reviews in Google Shopping and paid Google adverts, Google needs the GTIN.', 'trusted-shops', 'trusted-shops-easy-integration' ),
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'          => '_ts_mpn',
				'label'       => _x( 'MPN', 'trusted-shops', 'trusted-shops-easy-integration' ),
				'data_type'   => 'text',
				'desc_tip'    => true,
				'description' => _x( 'If you don\'t have a GTIN for your products, you can pass the brand name and the MPN on to Google to use the Trusted Shops Google Integration.', 'trusted-shops', 'trusted-shops-easy-integration' ),
			)
		);

		echo '</div>';
	}

	public static function register_settings( $settings ) {
		if ( ! Package::is_integration() ) {
			$settings[] = new SettingsPage();
		}

		return $settings;
	}

	public static function get_screen_ids() {
		$screen_ids = array( 'woocommerce_page_wc-settings' );

		return $screen_ids;
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

		if ( in_array( $screen_id, self::get_screen_ids(), true ) && isset( $_GET['tab'] ) && strstr( wc_clean( wp_unslash( $_GET['tab'] ) ), 'trusted_shops_easy_integration' ) && current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return true;
		}

		return false;
	}
}
