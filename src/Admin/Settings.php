<?php

namespace Vendidero\TrustedShopsEasyIntegration\Admin;

defined( 'ABSPATH' ) || exit;

class Settings {

	public static function get_sections() {
		return array(
			'' => _x( 'General', 'trusted-shops', 'trusted-shops-easy-integration' ),
		);
	}

	public static function get_description() {
		return '';
	}

	public static function get_help_url() {
		return '';
	}

	public static function output() {
		$GLOBALS['hide_save_button'] = true;
		?>
		<div id="app">
			<div id="eTrusted-connector"></div>
		</div>
		<?php
	}

	public static function get_settings( $current_section = '' ) {
		return array();
	}

	public static function before_save() {

	}

	public static function after_save() {

	}

	public static function get_settings_url() {
		return admin_url( 'admin.php?page=wc-settings&tab=trusted-shops-easy-integration' );
	}
}
