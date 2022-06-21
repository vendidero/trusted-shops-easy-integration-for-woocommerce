<?php
/**
 * The Template is used to embed a service widget.
 *
 * @package TS/Templates
 * @version 1.0.0
 *
 * @var stdClass $ts_widget
 */

defined( 'ABSPATH' ) || exit;

$ts_widget = isset( $ts_widget ) ? $ts_widget : null;

if ( ! $ts_widget ) {
	return;
}
?>
<!-- added by Trusted Shops app: Start -->
<div class="trustedShopsWidget trustedShopsServiceWidget">
	<etrusted-widget data-etrusted-widget-id="<?php echo esc_attr( $ts_widget->attributes->id->value ); ?>"></etrusted-widget>
</div>
<!-- End -->
