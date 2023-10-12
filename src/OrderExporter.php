<?php
namespace Vendidero\TrustedShopsEasyIntegration;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_CSV_Batch_Exporter', false ) ) {
	require_once WC_ABSPATH . 'includes/export/abstract-wc-csv-batch-exporter.php';
}

class OrderExporter extends \WC_CSV_Batch_Exporter {

	/**
	 * Type of export used in filter names.
	 *
	 * @var string
	 */
	protected $export_type = 'ts_order_export';

	/**
	 * Filename to export to.
	 *
	 * @var string
	 */
	protected $filename = 'ts-order-export.csv';

	protected $delimiter = ';';

	/**
	 * Batch limit.
	 *
	 * @var integer
	 */
	protected $limit = 10;

	protected $days_to_export = 10;

	protected $sales_channel = '';

	protected $include_product_data = false;

	public function __construct( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'days_to_export'       => 10,
				'sales_channel'        => '',
				'limit'                => 10,
				'page'                 => 1,
				'filename_suffix'      => '',
				'include_product_data' => false,
			)
		);

		$this->limit = absint( $args['limit'] );

		$this->set_sales_channel( $args['sales_channel'] );
		$this->set_days_to_export( $args['days_to_export'] );
		$this->set_page( $args['page'] );
		$this->set_include_product_data( $args['include_product_data'] );
		$this->set_filename_suffix( $args['filename_suffix'] );

		parent::__construct();
	}

	public function set_days_to_export( $days ) {
		$this->days_to_export = absint( $days );

		if ( $this->days_to_export <= 0 ) {
			$this->days_to_export = 1;
		}
	}

	public function set_include_product_data( $include ) {
		$this->include_product_data = (bool) $include;
	}

	public function set_sales_channel( $channel ) {
		$this->sales_channel = $channel;
	}

	public function set_filename_suffix( $suffix ) {
		$this->set_filename( sanitize_file_name( 'ts-order-export-' . $suffix . '.csv' ) );
	}

	public function get_sales_channel() {
		return $this->sales_channel;
	}

	public function include_product_data() {
		return $this->include_product_data;
	}

	/**
	 * Tweak: Temporarily adjust the filename to allow custom naming for the filename to be downloaded.
	 *
	 * @return void
	 */
	public function send_headers() {
		$filename = $this->get_filename();
		$this->set_filename( 'orders.csv' );
		parent::send_headers();

		$this->set_filename( $filename );
	}

	/**
	 * Return an array of columns to export.
	 *
	 * @since 3.1.0
	 * @return array
	 */
	public function get_default_column_names() {
		$columns = array(
			'email'            => 'email',
			'reference'        => 'reference',
			'first_name'       => 'firstName',
			'last_name'        => 'lastName',
			'transaction_date' => 'transactionDate',
		);

		if ( $this->include_product_data() ) {
			$columns += array(
				'product_name'      => 'productName',
				'product_url'       => 'productUrl',
				'product_sku'       => 'productSku',
				'product_gtin'      => 'productGtin',
				'product_mpn'       => 'productMpn',
				'product_image_url' => 'productImageUrl',
				'product_brand'     => 'productBrand',
			);
		}

		return $columns;
	}

	/**
	 * @param \WC_Order $order
	 *
	 * @return array
	 */
	protected function generate_row_data( $order ) {
		$rows           = array();
		$order_data_row = array();

		foreach ( array_keys( $this->get_column_names() ) as $column_id ) {
			$column_id = strstr( $column_id, ':' ) ? current( explode( ':', $column_id ) ) : $column_id;
			$value     = '';

			if ( 'email' === $column_id ) {
				$value = $order->get_billing_email();
			} elseif ( 'reference' === $column_id ) {
				$value = $order->get_order_number();
			} elseif ( 'first_name' === $column_id ) {
				$value = $order->get_billing_first_name();
			} elseif ( 'last_name' === $column_id ) {
				$value = $order->get_billing_last_name();
			} elseif ( 'transaction_date' === $column_id ) {
				$value = $order->get_date_created()->date_i18n( 'c' );
			}

			$order_data_row[ $column_id ] = $value;
		}

		if ( $this->include_product_data() ) {
			$order_product_map = array();

			foreach ( $order->get_items() as $line_item ) {
				$row = $order_data_row;

				if ( is_callable( array( $line_item, 'get_product_id' ) ) ) {
					if ( in_array( $line_item->get_product_id(), $order_product_map, true ) ) {
						continue;
					}

					$order_product_map[] = $line_item->get_product_id();

					if ( $product = wc_get_product( $line_item->get_product_id() ) ) {
						$row['product_name']      = $product->get_title();
						$row['product_url']       = $product->get_permalink();
						$row['product_sku']       = Package::get_product_sku( $product );
						$row['product_gtin']      = Package::get_product_gtin( $product ) !== $row['product_sku'] ? Package::get_product_gtin( $product ) : '';
						$row['product_mpn']       = Package::get_product_mpn( $product ) !== $row['product_sku'] ? Package::get_product_mpn( $product ) : '';
						$row['product_image_url'] = Package::get_product_image_src( $product );
						$row['product_brand']     = Package::get_product_brand( $product );

						$rows[] = $row;
					}
				}
			}
		} else {
			$rows[] = $order_data_row;
		}

		return $rows;
	}

	/**
	 * @return \WC_Order[]
	 */
	protected function get_orders() {
		$date = new \DateTime();
		$date = $date->modify( '- ' . $this->days_to_export . ' day' . ( $this->days_to_export > 1 ? 's' : '' ) );
		$args = array(
			'limit'      => $this->get_limit(),
			'offset'     => ( $this->get_page() - 1 ) * $this->get_limit(),
			'type'       => 'shop_order',
			'paginate'   => true,
			'page'       => $this->get_page(),
			'date_after' => $date->format( 'Y-m-d' ),
		);

		do_action( 'ts_easy_integration_before_get_orders_for_export', $this );

		$query            = wc_get_orders( apply_filters( 'ts_easy_integration_order_export_args', $args, $this ) );
		$this->total_rows = $query->total;

		do_action( 'ts_easy_integration_after_get_orders_for_export', $this );

		return $query->orders;
	}

	/**
	 * Prepare data that will be exported.
	 */
	public function prepare_data_to_export() {
		$orders         = $this->get_orders();
		$this->row_data = array();

		foreach ( $orders as $order ) {
			$this->row_data = array_merge( $this->row_data, $this->generate_row_data( $order ) );
		}
	}

	/**
	 * Get file path to export to.
	 *
	 * @return string
	 */
	protected function get_file_path() {
		$upload_dir = wp_upload_dir();

		return trailingslashit( $upload_dir['basedir'] ) . 'woocommerce_uploads/' . $this->get_filename();
	}
}
