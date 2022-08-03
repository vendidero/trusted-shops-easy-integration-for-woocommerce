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
			self::update();
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

	private static function update() {

	}

	private static function add_options() {

	}
}
