<?php

namespace Vendidero\TrustedShopsEasyIntegration\API;

use Vendidero\TrustedShopsEasyIntegration\Package;

defined( 'ABSPATH' ) || exit;

final class Events extends Rest {

	protected function get_url() {
		return 'https://api.etrusted.com';
	}

	/**
	 * @param \WC_Order $order
	 * @param string $channel
	 * @param string $event_type
	 *
	 * @return RestResponse|\WP_Error
	 */
	public function trigger( $order, $channel, $event_type ) {
		$locale = Package::get_order_locale( $order );

		Package::log( sprintf( 'TS locale detected for order #%1$s: %2$s', $order->get_order_number(), $locale ) );

		$request = array(
			'type'          => $event_type,
			'customer'      => array(
				'firstName' => $order->get_billing_first_name(),
				'lastName'  => $order->get_billing_last_name(),
				'email'     => $order->get_billing_email(),
				'reference' => (string) $order->get_customer_id(),
			),
			'channel'       => array(
				'id'   => $channel,
				'type' => 'etrusted',
			),
			'transaction'   => array(
				'reference' => $order->get_order_number(),
				'date'      => $order->get_date_created()->format( \DateTime::ATOM ),
			),
			'system'        => 'WooCommerce',
			'systemVersion' => Package::get_version(),
			'products'      => array(),
		);

		$product_parent_map = array();

		foreach ( $order->get_items() as $item_id => $item ) {
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

			$request['products'][] = array(
				'gtin'     => esc_html( Package::get_product_gtin( $parent_product ) ),
				'imageUrl' => esc_url( Package::get_product_image_src( $parent_product ) ),
				'name'     => esc_html( $parent_product->get_title() ),
				'mpn'      => esc_html( Package::get_product_mpn( $parent_product ) ),
				'sku'      => esc_html( Package::get_product_sku( $parent_product ) ),
				'brand'    => esc_html( Package::get_product_brand( $parent_product ) ),
				'url'      => esc_url( $parent_product->get_permalink() ),
			);
		}

		Package::log( sprintf( 'Event request for order #%1$s', $order->get_order_number() ) );
		Package::log( wc_print_r( $request, true ) );

		$result = $this->post( 'events', $request );

		if ( is_wp_error( $result ) || $result->is_error() ) {
			if ( is_wp_error( $result ) ) {
				$code    = $result->get_error_code();
				$message = $result->get_error_message();
			} else {
				$code    = $result->get_code();
				$message = esc_html( $result->get( 'Message' ) );
			}

			Package::log( sprintf( 'Error %1$s detected while posting event: %2$s', $code, $message ) );
		} else {
			$message = esc_html( $result->get( 'Message' ) );
			Package::log( sprintf( 'Successfully posted event for order #%1$s: %2$s', $order->get_order_number(), $message ) );
		}

		return $result;
	}
}
