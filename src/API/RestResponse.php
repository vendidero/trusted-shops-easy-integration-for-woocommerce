<?php

namespace Vendidero\TrustedShopsEasyIntegration\API;

defined( 'ABSPATH' ) || exit;

class RestResponse {

	protected $body = '';

	protected $code = '';

	protected $type = 'GET';

	protected $headers = array();

	public function __construct( $code, $body, $type = 'GET', $headers = array() ) {
		$this->code    = absint( $code );
		$this->body    = $body;
		$this->type    = $type;
		$this->headers = is_a( $headers, '\WpOrg\Requests\Utility\CaseInsensitiveDictionary' ) ? $headers->getAll() : (array) $headers;
	}

	public function get_body_raw() {
		return $this->body;
	}

	public function get_body() {
		return json_decode( $this->get_body_raw(), true );
	}

	public function get( $prop ) {
		$body = $this->get_body();

		return isset( $body[ $prop ] ) ? $body[ $prop ] : null;
	}

	public function get_code() {
		return $this->code;
	}

	public function get_type() {
		return $this->type;
	}

	public function get_headers() {
		return $this->headers;
	}

	public function is_error() {
		return $this->get_code() >= 300;
	}
}
