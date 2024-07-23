<?php
/**
 * Plugin Name: Trusted Shops Easy Integration for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/trusted-shops-easy-integration-for-woocommerce
 * Description: Trusted Shops Easy Integration for WooCommerce.
 * Author: vendidero
 * Author URI: https://vendidero.de
 * Version: 2.0.3
 * Requires PHP: 5.6
 * License: GPLv3
 * Requires at least: 4.9
 * WC requires at least: 3.9
 * WC tested up to: 9.1
 *
 * Text Domain: trusted-shops-easy-integration-for-woocommerce
 * Domain Path: /i18n/languages/
 */

defined( 'ABSPATH' ) || exit;

if ( version_compare( PHP_VERSION, '5.6.0', '<' ) ) {
	return;
}

/**
 * Autoload packages.
 *
 * The package autoloader includes version information which prevents classes in this feature plugin
 * conflicting with WooCommerce core.
 *
 * We want to fail gracefully if `composer install` has not been executed yet, so we are checking for the autoloader.
 * If the autoloader is not present, let's log the failure and display a nice admin notice.
 */
$autoloader = __DIR__ . '/vendor/autoload_packages.php';

if ( is_readable( $autoloader ) ) {
	require $autoloader;
} else {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log(  // phpcs:ignore
			sprintf(
				esc_html( 'Your installation of the Trusted Shops Easy Integration for WooCommerce plugin is incomplete. Please run %1$s within the %2$s directory.' ),
				'`composer install`',
				'`' . esc_html( str_replace( ABSPATH, '', __DIR__ ) ) . '`'
			)
		);
	}
	/**
	 * Outputs an admin notice if composer install has not been ran.
	 */
	add_action(
		'admin_notices',
		function() {
			?>
			<div class="notice notice-error">
				<p>
					<?php
					printf(
					/* translators: 1: composer command. 2: plugin directory */
						esc_html( 'Your installation of the Trusted Shops Easy Integration for WooCommerce plugin is incomplete. Please run %1$s within the %2$s directory.' ),
						'<code>composer install</code>',
						'<code>' . esc_html( str_replace( ABSPATH, '', __DIR__ ) ) . '</code>'
					);
					?>
				</p>
			</div>
			<?php
		}
	);
	return;
}

register_activation_hook( __FILE__, array( '\Vendidero\TrustedShopsEasyIntegration\Package', 'install' ) );
register_uninstall_hook( __FILE__, array( '\Vendidero\TrustedShopsEasyIntegration\Package', 'uninstall' ) );

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( '\Vendidero\TrustedShopsEasyIntegration\Package', 'action_links' ) );
add_action( 'plugins_loaded', array( '\Vendidero\TrustedShopsEasyIntegration\Package', 'init' ) );
