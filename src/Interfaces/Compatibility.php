<?php

namespace Vendidero\TrustedShopsEasyIntegration\Interfaces;

defined( 'ABSPATH' ) || exit;

interface Compatibility {

	public static function is_active();

	public static function init();
}
