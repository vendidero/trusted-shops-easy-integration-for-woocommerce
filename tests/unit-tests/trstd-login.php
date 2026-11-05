<?php

use Vendidero\TrustedShopsEasyIntegration\Package;
use Vendidero\TrustedShopsEasyIntegration\Shortcodes;

/**
 * Class TrstdLogin.
 */
class TrstdLogin extends Vendidero\TrustedShopsEasyIntegration\Tests\Framework\UnitTestCase {

	protected function get_config( $enabled = true ) {
		return array(
			'id'                 => 'int-123',
			'salesChannelRef'    => 'main',
			'eTrustedChannelRef' => 'chl-123',
			'configuration'      => array(
				'script'      => array(
					'tag'        => 'script',
					'attributes' => array(
						'type'                => array(
							'value'         => 'module',
							'attributeName' => 'type',
						),
						'src'                 => array(
							'value'         => 'https://widgets.trustedshops.com/integration/integration.js',
							'attributeName' => 'src',
						),
						'data-integration-id' => array(
							'value'         => 'int-123',
							'attributeName' => 'data-integration-id',
						),
						'data-unknown'        => 'plain',
					),
				),
				'integration' => array(
					'applicationType'   => 'trstd_login',
					'tag'               => 'trstd-login',
					'location'          => array(
						'id'   => 'trstd-loc-navbar',
						'name' => 'Header',
					),
					'trstdLoginEnabled' => $enabled,
				),
			),
		);
	}

	protected function store_config( $config ) {
		Package::update_setting(
			'channels',
			array(
				(object) array(
					'salesChannelRef'    => 'main',
					'eTrustedChannelRef' => 'chl-123',
				),
			)
		);

		Package::update_setting( 'trstd_login', (object) array( 'main_chl-123' => wp_json_encode( $config ) ) );
	}

	public function test_script_html() {
		$this->store_config( $this->get_config() );

		$this->assertTrue( Package::is_trstd_login_enabled() );
		$this->assertSame( '<script type="module" src="https://widgets.trustedshops.com/integration/integration.js" data-integration-id="int-123" data-unknown="plain"></script>', Package::get_trstd_login_script_html() );
	}

	public function test_disabled() {
		$this->store_config( $this->get_config( false ) );

		$this->assertFalse( Package::is_trstd_login_enabled() );
		$this->assertSame( '', Shortcodes::trstd_login() );
	}

	public function test_unconfigured() {
		Package::update_setting( 'trstd_login', array() );

		$this->assertFalse( Package::is_trstd_login_enabled() );
		$this->assertSame( '', Package::get_trstd_login_script_html() );
	}

	public function test_sanitize() {
		$config = $this->get_config();
		$config['unknown'] = 'drop';
		$config['configuration']['script']['tag'] = 'iframe';
		$config['configuration']['script']['attributes']['onload'] = 'alert(1)';
		$config['configuration']['integration']['tag'] = 'trstd login"><script>';

		$sanitized = \Vendidero\TrustedShopsEasyIntegration\Ajax::sanitize_trstd_login( $config );

		$this->assertArrayNotHasKey( 'unknown', $sanitized );
		$this->assertSame( 'script', $sanitized['configuration']['script']['tag'] );
		$this->assertArrayNotHasKey( 'onload', $sanitized['configuration']['script']['attributes'] );
		$this->assertSame( 'module', $sanitized['configuration']['script']['attributes']['type']['value'] );
		$this->assertSame( 'plain', $sanitized['configuration']['script']['attributes']['data-unknown']['value'] );
		$this->assertSame( 'trstd-login', $sanitized['configuration']['integration']['tag'] );
		$this->assertTrue( $sanitized['configuration']['integration']['trstdLoginEnabled'] );
	}

	public function test_sanitize_invalid_host() {
		$config = $this->get_config();
		$config['configuration']['script']['attributes']['src']['value'] = 'https://example.com/integration.js';

		$this->expectException( \Exception::class );
		\Vendidero\TrustedShopsEasyIntegration\Ajax::sanitize_trstd_login( $config );
	}
}
