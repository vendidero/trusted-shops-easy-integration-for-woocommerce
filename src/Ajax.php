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
			'disconnect',
			'export_orders',
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
			@ini_set( 'display_errors', 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.IniSet.display_errors_Blacklisted, WordPress.PHP.IniSet.display_errors_Disallowed
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
	 * @param $attribute
	 *
	 * @return array
	 * @throws \Exception
	 */
	protected static function parse_attribute( $attribute ) {
		$attribute = wp_parse_args(
			$attribute,
			array(
				'attributeName' => '',
			)
		);

		$valid_attributes           = array( 'attributeName' );
		$attribute['attributeName'] = wc_clean( $attribute['attributeName'] );

		if ( ! Package::is_allowed_script_attribute( $attribute['attributeName'] ) ) {
			throw new \Exception( 'Invalid attribute name: ' . esc_html( $attribute['attributeName'] ), 500 );
		}

		if ( isset( $attribute['value'] ) ) {
			$valid_attributes[] = 'value';

			$attribute['value'] = wc_clean( $attribute['value'] );

			if ( ! empty( $attribute['value'] ) ) {
				if ( 'src' === $attribute['attributeName'] ) {
					if ( ! Package::is_allowed_trusted_shops_url( $attribute['value'] ) ) {
						throw new \Exception( 'Invalid attribute host detected.', 500 );
					}
				} elseif ( filter_var( $attribute['value'], FILTER_VALIDATE_URL ) && ! Package::is_allowed_trusted_shops_url( $attribute['value'] ) ) {
					throw new \Exception( 'Invalid attribute host detected.', 500 );
				}
			}
		}

		$attribute = array_intersect_key( $attribute, array_flip( $valid_attributes ) );

		return $attribute;
	}

	protected static function parse_defaults( $value, $defaults = array() ) {
		$value = wp_parse_args( $value, $defaults );
		$value = array_intersect_key( $value, $defaults );

		return $value;
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

		$settings          = self::get_request_data();
		$original_settings = Package::get_settings();
		$result            = true;
		$settings_allowed  = array_merge( array( 'client_id', 'client_secret' ), array_keys( $original_settings ) );

		try {
			foreach ( $settings as $setting_name => $value ) {
				if ( ! in_array( $setting_name, $settings_allowed, true ) ) {
					continue;
				}

				$value = json_decode( wp_json_encode( $value ), true );
				$value = wc_clean( $value );

				if ( 'trustbadges' === $setting_name ) {
					$value = (array) $value;

					foreach ( $value as $trustbadge_key => $trustbadge ) {
						$trustbadge = self::parse_defaults(
							$trustbadge,
							array(
								'id'                 => '',
								'eTrustedChannelRef' => '',
								'children'           => array(),
								'salesChannelRef'    => '',
							)
						);

						if ( empty( $trustbadge['id'] ) || empty( $trustbadge['children'] ) || ! isset( $trustbadge['children'][0]['attributes'] ) ) {
							throw new \Exception( 'Invalid trustbadge detected.', 500 );
						}

						$trustbadge = wc_clean( $trustbadge );

						foreach ( (array) $trustbadge['children'] as $child_key => $child ) {
							$child = self::parse_defaults(
								$child,
								array(
									'tag'        => '',
									'attributes' => array(),
								)
							);

							$child = wc_clean( $child );

							foreach ( (array) $child['attributes'] as $attribute_key => $attribute ) {
								$child['attributes'][ $attribute_key ] = self::parse_attribute( $attribute );
							}

							$trustbadge['children'][ $child_key ] = $child;
						}

						$value[ $trustbadge_key ] = $trustbadge;
					}
				} elseif ( 'channels' === $setting_name ) {
					$value = (array) $value;

					foreach ( $value as $channel_key => $channel ) {
						$value[ $channel_key ] = wc_clean( $channel );
					}
				} elseif ( 'used_order_statuses' === $setting_name ) {
					$value = (array) $value;

					foreach ( $value as $channel_key => $used_order_status ) {
						foreach ( $used_order_status as $status_type => $status ) {
							$status                                = self::parse_defaults(
								$status,
								array(
									'name'       => '',
									'ID'         => '',
									'event_type' => '',
								)
							);
							$value[ $channel_key ][ $status_type ] = wc_clean( $status );
						}
					}
				} elseif ( 'widgets' === $setting_name ) {
					$value = (array) $value;

					foreach ( $value as $channel_key => $widget ) {
						$widget = self::parse_defaults(
							$widget,
							array(
								'id'                 => '',
								'eTrustedChannelRef' => '',
								'children'           => array(),
								'salesChannelRef'    => '',
							)
						);

						if ( empty( $widget['id'] ) || empty( $widget['children'] ) || ! isset( $widget['children'][0]['attributes'] ) ) {
							throw new \Exception( 'Invalid widget detected.', 500 );
						}

						$widget = wc_clean( $widget );

						foreach ( (array) $widget['children'] as $child_key => $child ) {
							$child = self::parse_defaults(
								$child,
								array(
									'tag'        => '',
									'attributes' => array(),
									'children'   => array(),
								)
							);

							$child = wc_clean( $child );

							foreach ( (array) $child['attributes'] as $attribute_key => $attribute ) {
								$child['attributes'][ $attribute_key ] = self::parse_attribute( $attribute );
							}

							foreach ( (array) $child['children'] as $widget_child_key => $widget_child ) {
								$widget_child = self::parse_defaults(
									$widget_child,
									array(
										'applicationType' => '',
										'attributes'      => array(),
										'extensions'      => array(),
										'tag'             => '',
										'widgetId'        => '',
										'widgetLocation'  => array(),
									)
								);

								$widget_child = wc_clean( $widget_child );

								foreach ( (array) $widget_child['attributes'] as $attribute_key => $attribute ) {
									$widget_child['attributes'][ $attribute_key ] = self::parse_attribute( $attribute );
								}

								foreach ( (array) $widget_child['extensions'] as $extension_key => $extension ) {
									$widget_child['extensions'][ $extension_key ] = self::parse_defaults(
										$extension,
										array(
											'tag' => '',
										)
									);
								}

								$widget_child['widgetLocation'] = self::parse_defaults(
									$widget_child['widgetLocation'],
									array(
										'id' => '',
									)
								);

								$child['children'][ $widget_child_key ] = $widget_child;
							}

							$widget['children'][ $child_key ] = $child;
						}

						$value[ $channel_key ] = $widget;
					}
				}

				// Convert to std class.
				$value  = json_decode( wp_json_encode( $value ) );
				$result = Package::update_setting( $setting_name, $value );

				if ( is_wp_error( $result ) ) {
					throw new \Exception( $result->get_error_message(), 500 );
				}
			}
		} catch ( \Exception $e ) {
			$result = new \WP_Error( $e->getCode(), $e->getMessage() );
		}

		if ( is_wp_error( $result ) ) {
			$response = array(
				'success'  => false,
				'message'  => $result->get_error_messages(),
				'settings' => Package::get_settings(),
			);
		} else {
			$response = array(
				'success'  => true,
				'message'  => '',
				'settings' => Package::get_settings(),
			);
		}

		wp_send_json( $response );
	}

	protected static function generate_file_suffix( $sales_channel ) {
		$suffix = wp_generate_password( 6, false );

		if ( ! empty( $sales_channel ) ) {
			$suffix .= '-' . $sales_channel;
		}

		$suffix = wc_clean( $suffix );

		return $suffix;
	}

	/**
	 * Export orders.
	 *
	 * @return void
	 */
	public static function export_orders() {
		check_ajax_referer( 'ts-export-orders', 'security' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( - 1 );
		}

		$request_data = self::get_request_data();

		if ( ! isset( $request_data->step, $request_data->number_of_days, $request_data->include_product_data ) ) {
			wp_die( -1 );
		}

		$step                 = absint( wp_unslash( $request_data->step ) );
		$number_of_days       = absint( wp_unslash( $request_data->number_of_days ) );
		$include_product_data = wc_string_to_bool( wp_unslash( $request_data->include_product_data ) );

		$sales_channel   = ! empty( $request_data->sales_channel ) ? wc_clean( wp_unslash( $request_data->sales_channel ) ) : Package::get_current_sales_channel();
		$filename_suffix = ! empty( $request_data->filename_suffix ) ? wc_clean( wp_unslash( $request_data->filename_suffix ) ) : self::generate_file_suffix( $sales_channel );

		$exporter = new OrderExporter(
			array(
				'days_to_export'       => $number_of_days,
				'sales_channel'        => $sales_channel,
				'filename_suffix'      => $filename_suffix,
				'page'                 => $step,
				'include_product_data' => $include_product_data,
			)
		);

		$exporter->generate_file();

		if ( $exporter->get_percent_complete() >= 100 ) {
			$query_args = array(
				'nonce'  => wp_create_nonce( 'ts-download-order-export' ),
				'action' => 'ts-download-order-export',
				'suffix' => $filename_suffix,
			);

			$step_args = array(
				'step'                 => 'done',
				'percentage'           => 100,
				'url'                  => add_query_arg( $query_args, admin_url( 'admin-post.php' ) ),
				'sales_channel'        => $sales_channel,
				'number_of_days'       => $number_of_days,
				'include_product_data' => $include_product_data,
				'filename_suffix'      => $filename_suffix,
			);
		} else {
			$step_args = array(
				'step'                 => ++$step,
				'percentage'           => $exporter->get_percent_complete(),
				'sales_channel'        => $sales_channel,
				'number_of_days'       => $number_of_days,
				'include_product_data' => $include_product_data,
				'filename_suffix'      => $filename_suffix,
			);
		}

		wp_send_json( $step_args );
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

		header( 'Access-Control-Allow-Headers: [Client-ID, Client-Secret]' );
		header( 'Client-Id:' . base64_encode( Package::get_setting( 'client_id', '' ) ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		header( 'Client-Secret:' . base64_encode( Package::get_setting( 'client_secret', '' ) ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

		$response = array(
			'success'  => true,
			'message'  => '',
			'settings' => Package::get_settings(),
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
