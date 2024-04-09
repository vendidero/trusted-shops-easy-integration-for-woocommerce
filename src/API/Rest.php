<?php

namespace Vendidero\TrustedShopsEasyIntegration\API;

use Vendidero\TrustedShopsEasyIntegration\Package;

defined( 'ABSPATH' ) || exit;

class Rest {

	protected function get_url() {
		return '';
	}

	protected function has_auth() {
		$access_token = $this->get_access_token();

		if ( ! empty( $access_token ) ) {
			return true;
		}

		return false;
	}

	protected function get_access_token() {
		$access_token = Package::get_setting( 'access_token' );

		return $access_token;
	}

	protected function auth() {
		$client_id     = Package::get_setting( 'client_id', '' );
		$client_secret = Package::get_setting( 'client_secret', '' );
		$args          = array(
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
			'grant_type'    => 'client_credentials',
			'audience'      => 'https://api.etrusted.com',
		);

		$result = $this->post( 'https://login.etrusted.com/oauth/token', $args, array( 'Content-Type' => 'application/x-www-form-urlencoded' ) );

		if ( ! is_wp_error( $result ) ) {
			$body = $result->get_body();

			if ( isset( $body['access_token'], $body['expires_in'] ) ) {
				$access_token = wc_clean( $body['access_token'] );

				Package::update_setting( 'access_token', $access_token );
			}
		}
	}

	protected function get_basic_auth() {
		if ( $this->has_auth() ) {
			return 'Bearer ' . $this->get_access_token();
		}

		return false;
	}

	protected function get_timeout( $request_type = 'GET' ) {
		return 'GET' === $request_type ? 30 : 2000;
	}

	protected function get_content_type() {
		return 'application/json';
	}

	protected function maybe_encode_body( $body_args, $content_type = '' ) {
		if ( empty( $content_type ) ) {
			$content_type = $this->get_content_type();
		}

		if ( 'application/json' === $content_type ) {
			return wp_json_encode( $body_args, JSON_PRETTY_PRINT );
		} elseif ( 'application/x-www-form-urlencoded' === $content_type ) {
			return http_build_query( $body_args );
		}

		return $body_args;
	}

	protected function get_response( $url, $type = 'GET', $body_args = array(), $header = array() ) {
		$is_auth_call = strstr( $url, 'login.etrusted.com' );

		if ( ! $is_auth_call && ! $this->has_auth() ) {
			$this->auth();
		}

		$header   = $this->get_header( $header );
		$response = false;

		if ( 'GET' === $type ) {
			$response = wp_remote_get(
				esc_url_raw( $url ),
				array(
					'headers' => $header,
					'timeout' => $this->get_timeout( $type ),
				)
			);
		} elseif ( 'POST' === $type ) {
			$response = wp_remote_post(
				esc_url_raw( $url ),
				array(
					'headers' => $header,
					'timeout' => $this->get_timeout( $type ),
					'body'    => $this->maybe_encode_body( $body_args, $header['Content-Type'] ),
				)
			);
		} elseif ( 'PUT' === $type ) {
			$response = wp_remote_request(
				esc_url_raw( $url ),
				array(
					'headers' => $header,
					'timeout' => $this->get_timeout( $type ),
					'body'    => $this->maybe_encode_body( $body_args, $header['Content-Type'] ),
					'method'  => 'PUT',
				)
			);
		}

		if ( false !== $response ) {
			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$code = wp_remote_retrieve_response_code( $response );

			if ( ! $is_auth_call && $this->has_auth() && ! isset( $header['auth-retry'] ) ) {
				if ( 401 === absint( $code ) ) {
					delete_option( 'ts_easy_integration_access_token' );
					$header['auth-retry'] = true;
					unset( $header['Authorization'] );

					return $this->get_response( $url, $type, $body_args, $header );
				}
			}

			$body = wp_remote_retrieve_body( $response );

			return new RestResponse( $code, $body, $type );
		}

		return new \WP_Error( 'rest-error', sprintf( 'Error while trying to perform REST request to %s', $url ) );
	}

	protected function get_request_url( $endpoint = '', $query_args = array() ) {
		if ( strpos( $endpoint, 'http://' ) === false && strpos( $endpoint, 'https://' ) === false ) {
			$endpoint = trailingslashit( $this->get_url() ) . $endpoint;
		}

		return add_query_arg( $query_args, $endpoint );
	}

	/**
	 * @param string $endpoint
	 * @param array  $query_args
	 *
	 * @return RestResponse|\WP_Error
	 */
	public function get( $endpoint = '', $query_args = array(), $header = array() ) {
		return $this->get_response( $this->get_request_url( $endpoint, $query_args ), 'GET', array(), $header );
	}

	/**
	 * @param string $endpoint
	 * @param array  $query_args
	 *
	 * @return RestResponse|\WP_Error
	 */
	public function post( $endpoint = '', $body_args = array(), $header = array() ) {
		return $this->get_response( $this->get_request_url( $endpoint ), 'POST', $body_args, $header );
	}

	/**
	 * @param string $endpoint
	 * @param array  $query_args
	 *
	 * @return RestResponse|\WP_Error
	 */
	public function put( $endpoint = '', $body_args = array(), $header = array() ) {
		return $this->get_response( $this->get_request_url( $endpoint ), 'PUT', $body_args, $header );
	}

	protected function get_header( $header = array() ) {
		$headers = array();

		$headers['Content-Type'] = $this->get_content_type();
		$headers['Accept']       = 'application/json';

		if ( $this->get_basic_auth() ) {
			$headers['Authorization'] = $this->get_basic_auth();
		}

		$headers['User-Agent'] = 'WooCommerce/' . Package::get_version();

		/**
		 * Optionally replace request headers lazily.
		 */
		$headers = array_replace_recursive( $headers, $header );

		return $headers;
	}
}
