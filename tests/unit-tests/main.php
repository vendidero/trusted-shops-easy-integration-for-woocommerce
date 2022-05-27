<?php

/**
 * Class WC_Tests_Install.
 * @package WooCommerce\Tests\Util
 */
class Main extends Vendidero\TrustedShopsEasyIntegration\Tests\Framework\UnitTestCase {

	function test_check_version() {
		$this->assertTrue( get_option( 'ts_easy_integration_woocommerce_version' ) === \Vendidero\TrustedShopsEasyIntegration\Package::get_version() );
	}
}