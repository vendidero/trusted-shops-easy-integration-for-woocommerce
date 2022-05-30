<?php

namespace Vendidero\TrustedShopsEasyIntegration;

class Ajax {

	/**
	 * Hook in ajax handlers.
	 */
	public static function init() {
		self::add_ajax_events();
	}

	/**
	 * Hook in methods - uses WordPress ajax handlers (admin-ajax).
	 */
	public static function add_ajax_events() {
		$ajax_events = array(
			'update_settings',
			'get_settings',
			'disconnect'
		);

		foreach ( $ajax_events as $ajax_event ) {
			add_action( 'wp_ajax_ts_easy_integration_' . $ajax_event, array( __CLASS__, 'suppress_errors' ), 5 );
			add_action( 'wp_ajax_ts_easy_integration_' . $ajax_event, array( __CLASS__, $ajax_event ) );
		}
	}

	/**
	 * Suppress warnings during AJAX requests.
	 *
	 * @return void
	 */
	public static function suppress_errors() {
		if ( ! WP_DEBUG || ( WP_DEBUG && ! WP_DEBUG_DISPLAY ) ) {
			@ini_set( 'display_errors', 0 ); // Turn off display_errors during AJAX events to prevent malformed JSON.
		}

		$GLOBALS['wpdb']->hide_errors();
	}

	/**
	 * Returns unsanitized JSON request data.
	 *
	 * @return mixed
	 */
	private static function get_request_data() {
		$data = json_decode( file_get_contents( 'php://input' ) );

		return $data;
	}

	/**
	 * Update TS settings.
	 *
	 * @return void
	 */
	public static function update_settings() {
		check_ajax_referer( 'ts-update-settings', 'security' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( -1 );
		}

		$settings = self::get_request_data();
		$result   = true;

		foreach( $settings as $setting_name => $value ) {
			$value = wc_clean( $value );

			try {
				if ( 'trustbadges' === $setting_name ) {
					$value = (array) $value;

					foreach( $value as $sale_channel => $trustbadge ) {
						/**
						 * Do not allow storing invalid trustbadges.
						 */
						if ( ! isset( $trustbadge->id ) || ! isset( $trustbadge->children ) || ! isset( $trustbadge->children[0]->attributes ) ) {
							unset( $value[ $sale_channel ] );

							throw new \Exception( _x( 'Invalid trustbadge detected.', 'trusted-shops', 'trusted-shops-easy-integration' ), 'trustbadge-invalid' );
						}
					}
				}

				$result = Package::update_setting( $setting_name, $value );
			} catch( \Exception $e ) {
				$result = new \WP_Error( $e->getCode(), $e->getMessage() );
			}

			/**
			 * If an error occurs during saving option, stop here.
			 */
			if ( is_wp_error( $result ) ) {
				break;
			}
		}

		if ( is_wp_error( $result ) ) {
			$response = array(
				'success'  => false,
				'message'  => $result->get_error_messages(),
				'settings' => Package::get_settings()
			);
		} else {
			$response = array(
				'success'  => true,
				'message'  => '',
				'settings' => Package::get_settings()
			);
		}

		wp_send_json( $response );
	}

	/**
	 * Get TS settings.
	 *
	 * @return void
	 */
	public static function get_settings() {
		check_ajax_referer( 'ts-get-settings', 'security' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( -1 );
		}

		header( "Access-Control-Allow-Headers: [Client-ID, Client-Secret]" );
		header( "Client-Id:" . base64_encode( Package::get_setting( 'client_id', '' ) ) );
		header( "Client-Secret:" . base64_encode( Package::get_setting( 'client_secret', '' ) ) );

		$response = array(
			'success'  => true,
			'message'  => '',
			'settings' => Package::get_settings()
		);

		wp_send_json( $response );
	}

	/**
	 * Disconnect TS. REMOVES ALL SETTINGS.
	 *
	 * @return void
	 */
	public static function disconnect() {
		check_ajax_referer( 'ts-disconnect', 'security' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( -1 );
		}

		Package::delete_settings();

		$response = array(
			'success'  => true,
			'message'  => '',
			'settings' => array(),
		);

		wp_send_json( $response );
	}
}