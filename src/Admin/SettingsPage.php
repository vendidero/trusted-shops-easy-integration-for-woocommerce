<?php

namespace Vendidero\TrustedShopsEasyIntegration\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Settings_Emails.
 */
class SettingsPage extends \WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'trusted_shops_easy_integration';
		$this->label = _x( 'Trusted Shops New', 'trusted-shops', 'trusted-shops-easy-integration-for-woocommerce' );

		parent::__construct();
	}

	public function output() {
		global $current_section;

		if ( '' === $current_section ) {
			Settings::output();
		} else {
			parent::output();
		}
	}

	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = Settings::get_sections();

		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
	}

	public function save() {
		Settings::before_save();
		parent::save();
		Settings::after_save();
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {
		$settings = Settings::get_settings( $current_section );

		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings );
	}

	public function get_settings_for_section_core( $section_id ) {
		return Settings::get_settings( $section_id );
	}
}
