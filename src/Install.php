<?php

namespace Vendidero\TrustedShopsEasyIntegration;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Install {

	public static function install() {
		$current_version = get_option( 'ts_easy_integration_version', null );
		update_option( 'ts_easy_integration_version', Package::get_version() );

		if ( ! Package::is_integration() && ! Package::has_dependencies() ) {
			ob_start();
			Package::dependency_notice();
			$notice = ob_get_clean();
			wp_die( wp_kses_post( $notice ) );
		}

		if ( $current_version && version_compare( $current_version, Package::get_version(), '<' ) ) {
			self::update( $current_version );
		}

		if ( ! Package::is_integration() ) {
			if ( SecretsHelper::supports_auto_insert() && ! SecretsHelper::has_valid_encryption_key() ) {
				$result = SecretsHelper::maybe_insert_missing_key();
			}

			self::add_options();
		}
	}

	public static function uninstall() {
		Package::delete_settings();

		delete_option( 'ts_easy_integration_version' );
	}

	private static function update( $current_version ) {
		/**
		 * Fix bool values in trustbadges
		 */
		if ( version_compare( $current_version, '2.0.7', '<' ) ) {
			$trustbadges  = Package::get_trustbadges();
			$trustbadges  = json_decode( wp_json_encode( $trustbadges ), true );
			$needs_update = false;

			foreach ( $trustbadges as $channel => $trustbadge ) {
				$trustbadge = wp_parse_args(
					$trustbadge,
					array(
						'children' => array(),
					)
				);

				foreach ( (array) $trustbadge['children'] as $child_key => $child ) {
					$child = wp_parse_args(
						$child,
						array(
							'attributes' => array(),
						)
					);

					foreach ( (array) $child['attributes'] as $attribute_key => $attribute ) {
						$attribute = wp_parse_args(
							$attribute,
							array(
								'attributeName' => '',
							)
						);

						if ( isset( $attribute['value'] ) ) {
							if ( Package::is_bool_attribute( $attribute['attributeName'] ) ) {
								if ( '' === $attribute['value'] || '1' === $attribute['value'] || '0' === $attribute['value'] ) {
									$needs_update = true;
									$trustbadges[ $channel ]['children'][ $child_key ]['attributes'][ $attribute_key ]['value'] = ( $attribute['value'] ? true : false );
								}
							}
						}
					}
				}
			}

			if ( $needs_update ) {
				$trustbadges = json_decode( wp_json_encode( $trustbadges ) );
				Package::update_setting( 'trustbadges', $trustbadges );
			}
		}
	}

	private static function add_options() {
	}
}
