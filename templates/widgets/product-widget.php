<?php
/**
 * The Template is used to embed a widget.
 *
 * @package TS/Templates
 * @version 1.0.0
 *
 * @var stdClass $ts_widget
 */
use Vendidero\TrustedShopsEasyIntegration\Package;

defined( 'ABSPATH' ) || exit;

$ts_widget = isset( $ts_widget ) ? $ts_widget : null;

global $product;

if ( ! $ts_widget || ! is_a( $product, 'WC_Product' ) ) {
	return;
}
$identifier = Package::get_product_identifier_name( ( isset( $ts_widget->attributes, $ts_widget->attributes->productIdentifier ) ? $ts_widget->attributes->productIdentifier->attributeName : '' ) );
?>
<!-- added by Trusted Shops app: Start -->
<div class="trustedShopsWidget trustedShopsProductWidget">
	<?php if ( 'etrusted-product-review-list-widget-product-star-extension' === $ts_widget->tag ) : ?>
		<etrusted-product-review-list-widget-product-star-extension></etrusted-product-review-list-widget-product-star-extension>
	<?php else : ?>
		<etrusted-widget data-etrusted-widget-id="<?php echo esc_attr( $ts_widget->attributes->id->value ); ?>" <?php echo esc_attr( 'data-' . $identifier ); ?>="<?php echo esc_attr( Package::get_product_identifier( $product, $identifier ) ); ?>"></etrusted-widget>
	<?php endif; ?>
</div>
<!-- End -->
