<?php

namespace Vendidero\TrustedShopsEasyIntegration\API;

use Vendidero\TrustedShopsEasyIntegration\Ajax;
use Vendidero\TrustedShopsEasyIntegration\Package;

defined( 'ABSPATH' ) || exit;

final class JsonLd extends Rest {

	protected function get_url() {
		return Package::is_debug_mode() ? 'https://widgets-qa.trustedshops.com/js/' : 'https://widgets-qa.trustedshops.com/js/';
	}

	protected function has_auth() {
		return false;
	}

	protected function needs_auth() {
		return false;
	}

	public function update_schema( $sales_channel = '' ) {
		$markup  = Package::get_structured_markup( $sales_channel );
		$channel = Package::get_sales_channel( $sales_channel );

		if ( ! $markup || ! $channel ) {
			Package::log( 'Channel or structured markup is missing', 'error' );

			return false;
		}

		$existing_json = Package::get_json_ld( $sales_channel );
		$ts_id         = $markup->tsId; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$channel_url   = $channel['url'];

		$headers = array(
			'Origin' => $channel_url,
		);

		if ( $existing_json ) {
			$headers['If-None-Match'] = $existing_json['etag'];
		}

		$result = $this->get( "{$ts_id}-jsonld.json", array(), $headers );

		if ( is_wp_error( $result ) || $result->is_error() ) {
			if ( is_wp_error( $result ) ) {
				$code    = $result->get_error_code();
				$message = $result->get_error_message();
			} else {
				$code    = $result->get_code();
				$message = esc_html( $result->get_body_raw() );
			}

			if ( 404 === $code ) {
				Package::log( sprintf( 'Error %1$s detected while retrieving JSON LD for %2$s: %3$s', $code, esc_html( $ts_id ), 'Download refused due to origin: ' . esc_url_raw( $channel_url ) ), 'error' );
			} elseif ( 400 === $code ) {
				Package::log( sprintf( 'Error %1$s detected while retrieving JSON LD for %2$s: %3$s', $code, esc_html( $ts_id ), 'No markup is currently published for this TSID' ), 'error' );
			} elseif ( 304 === $code ) {
				Package::log( sprintf( 'Skipping JSON LD schema update due to etag match for %1$s', $ts_id ) );
			} else {
				Package::log( sprintf( 'Error %1$s detected while retrieving JSON LD for %2$s: %3$s', $code, esc_html( $ts_id ), $message ), 'error' );
			}
		} else {
			$headers        = $result->get_headers();
			$json_ld_schema = Ajax::wc_clean( $result->get_body(), true );
			$schema_as_json = wp_json_encode( $json_ld_schema, JSON_UNESCAPED_SLASHES );
			$result         = false;

			if ( false !== $schema_as_json ) {
				$result = array(
					'json'       => $schema_as_json,
					'fetched_at' => strtotime( 'now' ),
					'etag'       => wc_clean( isset( $headers['etag'] ) ? $headers['etag'] : '' ),
				);

				$needs_cache_update = true;

				if ( $existing_json && $result['json'] == $existing_json['json'] ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
					$needs_cache_update = false;
				}

				Package::update_json_ld( $result, $sales_channel );

				if ( $needs_cache_update ) {
					wp_cache_flush();
				}

				Package::log( sprintf( 'Updated JSON LD schema for %1$s: %2$s', $ts_id, wc_print_r( $result, true ) ) );
			}
		}

		return $result;
	}
}
