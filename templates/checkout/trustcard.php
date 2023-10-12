<?php
/**
 * The Template is used to embed the trustcard.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/trustcard.php.
 *
 * HOWEVER, on occasion TS will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package TS/Templates
 * @version 1.1.0
 *
 * @var WC_Order $order
 * @var string $ts_sales_channel
 *
 */

use Vendidero\TrustedShopsEasyIntegration\Package;

defined( 'ABSPATH' ) || exit;

$ts_sales_channel = isset( $ts_sales_channel ) ? $ts_sales_channel : '';
?>
<!-- added by Trusted Shops app: Start -->
<div id="trustedShopsCheckout" style="display: none;">
	<span id="tsCheckoutOrderNr"><?php echo esc_html( $order->get_order_number() ); ?></span>
	<span id="tsCheckoutBuyerEmail"><?php echo esc_html( $order->get_billing_email() ); ?></span>
	<span id="tsCheckoutOrderAmount"><?php echo esc_html( $order->get_total() ); ?></span>
	<span id="tsCheckoutOrderCurrency"><?php echo esc_html( $order->get_currency() ); ?></span>
	<span id="tsCheckoutOrderPaymentType"><?php echo esc_html( Package::get_order_payment_method( $order ) ); ?></span>
	<!-- product reviews start -->
	<?php
	$product_parent_map = array();

	foreach ( $order->get_items() as $item_id => $item ) :
		if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
			continue;
		}

		// Get forced parent product
		$parent_product = wc_get_product( $item->get_product_id() );

		// Skip additional variations of the same parent product
		if ( ! $parent_product || in_array( $item->get_product_id(), $product_parent_map, true ) ) {
			continue;
		}

		$product_parent_map[] = $item->get_product_id();
		?>
		<span class="tsCheckoutProductItem">
			<span class="tsCheckoutProductUrl"><?php echo esc_url( $parent_product->get_permalink() ); ?></span>
			<span class="tsCheckoutProductImageUrl"><?php echo esc_url( Package::get_product_image_src( $parent_product ) ); ?></span>
			<span class="tsCheckoutProductName"><?php echo esc_html( $parent_product->get_title() ); ?></span>
			<span class="tsCheckoutProductSKU"><?php echo esc_html( Package::get_product_sku( $parent_product ) ); ?></span>
			<span class="tsCheckoutProductGTIN"><?php echo esc_html( Package::get_product_gtin( $parent_product ) ); ?></span>
			<span class="tsCheckoutProductBrand"><?php echo esc_html( Package::get_product_brand( $parent_product ) ); ?></span>
			<span class="tsCheckoutProductMPN"><?php echo esc_html( Package::get_product_mpn( $parent_product ) ); ?></span>
		</span>
	<?php endforeach; ?>
	<!-- product reviews end -->
</div>
<!-- End -->
