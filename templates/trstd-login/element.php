<?php
/**
 * The Template is used to embed the #trstd login element, e.g. within the shop header.
 * The element is empty by design - the Trusted Shops integration script fills it at runtime.
 *
 * @package TS/Templates
 * @version 1.0.0
 *
 * @var string $element_tag The custom element tag name, e.g. trstd-login.
 * @var string $wrapper_attributes Wrapper HTML attributes, sanitized via wp_kses() within this template.
 */

defined( 'ABSPATH' ) || exit;

$element_tag        = isset( $element_tag ) ? $element_tag : 'trstd-login';
$wrapper_attributes = isset( $wrapper_attributes ) ? $wrapper_attributes : 'class="ts-trstd-login-hd-wrapper" data-app="TrustedShopsApp"';
?>
<!-- added by Trusted Shops app: Start -->
<?php
echo wp_kses(
	'<div ' . $wrapper_attributes . '>',
	array(
		'div' => array(
			'class'                   => true,
			'style'                   => true,
			'id'                      => true,
			'aria-label'              => true,
			'data-*'                  => true,
			// Explicitly allow interactivity directives containing double dashes for older versions of the data-* wildcard.
			'data-wp-class--is-ready' => true,
		),
	)
);
?>
<div class="ts-trstd-login-hd-wrapper__inner"><<?php echo esc_html( $element_tag ); ?>></<?php echo esc_html( $element_tag ); ?>></div>
</div>
<!-- End -->
