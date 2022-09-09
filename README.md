# Trusted Shops Easy Integration for WooCommerce

The Trusted Shops Easy Integration Plugin offers a smooth integration of the Trusted Shops product in WooCommerce.

### Plugin architecture

The plugin main files are located in the `src` directory. Assets, e.g. the base layer TypeScript implementation is stored in the `assets` directory.
Webpack is used to compile the base layer TypeScript file. To install and use, first install the npm dependencies:

```
npm install
npm run-script dev
```

PHP unit tests, which run via docker, are located in the `tests/unit-tests` directory. To run them locally via docker:

```
npm run-script phpunit
```

#### Hooks

All frontend-related hooks, e.g. to output Trustbadge, Trustcard and Widgets are managed in `src/Hooks.php`. External assets (e.g. the TS widgets script) are lazy-enqueued
via the [WordPress script API](https://developer.wordpress.org/reference/functions/wp_enqueue_script/) as soon as a widget is rendered.

For each widget position (e.g. `product_title`) a custom hooks is placed, e.g. `ts_easy_integration_single_product_title_widgets`. This way users may easily customize where/when
to output widgets by attaching custom scripts to it or removing the default logic to output the widget.

To render a widget, 2 templates exist:

1. `templates/widgets/product-widget.php` is used for product-related widgets.
2. `templates/widgets/service-widget.php` is used for service widgets.

### Overriding templates

Templates may be overridden in a (child-) theme. Overriding the `templates/widgets/product-widget.php` template in your theme 
by placing the original file in the following directory: `my-child-theme/woocommerce/widgets/product-widget.php`.

### Trustcard

The Trustcard is placed by using the `woocommerce_thankyou` hook. The template `templates/checkout/trustcard.php` contains the content.

### Product attributes

#### Brand

WooCommerce does by default not provide a global brand attribute/setting for products. The `Package::get_product_brand()` method determines 
the brand for a certain product. By default, the logic searches for an attribute stored within the product data matching the keyword `Brand` (or a translated version of it).

One might easily adjust the brand attribute name by using the filter `ts_easy_integration_product_brand_attribute_name`. E.g.:

```php
add_filter( 'ts_easy_integration_product_brand_attribute_name', function( $attribute_name, $product ) {
    // Maybe adjust the $attribute_name based on the WC_Product $product
    return $attribute_name;
}, 10, 2 );
```

Additionally the filter `ts_easy_integration_product_brand` is provided which allows filtering the actual brand per product.

```php
add_filter( 'ts_easy_integration_product_brand', function( $brand, $product ) {
    // Maybe adjust the $brand based on the WC_Product $product
    return $brand;
}, 10, 2 );
```

#### GTIN, MPN, SKU

The plugin uses product identifiers, which might not be provided by the Woo core (e.g. GTIN, MPN) for widgets, order exports and the Trustcard.
GTIN and MPN fields are provided through the WooCommerce edit product panel and stored as meta data, using the `meta_key` `_ts_gtin` and `_ts_mpn`.

Similar to the brand attribute, `Package::get_product_gtin()`, `Package::get_product_mpn()` and `Package::get_product_sku()` are used to retrieve the data.
Filters exist to adjust the attributes:

* `ts_easy_integration_product_gtin`
* `ts_easy_integration_product_mpn`
* `ts_easy_integration_product_sku`

Example filter-usage to adjust the GTIN:

```php
add_filter( 'ts_easy_integration_product_gtin', function( $gtin, $product ) {
    // Maybe adjust the $gtin based on the WC_Product $product
    return $gtin;
}, 10, 2 );
```

### Shortcodes

This plugins registers the `ts_widget` [shortcode](https://codex.wordpress.org/Shortcode_API) which may be used to
output a certain widget (with an ID) at a custom location, e.g. in a page, post etc. E.g.:

`[ts_widget id="{widget_id}" sales_channel="{sales_channel}" product_identifier="{product_identifier}"]`

* `{widget_id}` The ID of the widget, see the widgets panel for details.
* `{sales_channel}` Allows providing a custom sales channel, by default, the current sales channel is used.
* `{product_identifier}` Product identifier type (e.g. sku, mpn, gtin) in case this is a product-related widget.

Please be aware that product-related widgets will only work if the global `$product` variable is set and valid, e.g. on single product pages.

### Secrets Storage

The secrets (Client ID, Client secret) are stored as encrypted values in the `wp_options` table. The `src/SecretsHelper.php` file contains the 
encryption/decryption logic, using PHP's built-in `sodium_crypto_secretbox`. The encryption key is automagically placed in the [wp-config.php](https://wordpress.org/support/article/editing-wp-config-php/) file
upon installation. In case the encryption key is missing or could not be placed, a warning is being displayed, containing a random key to be inserted manually by the shop owner.

### Order Export

The order export feature, located in `src/OrderExporter.php` is using the [`WC_CSV_Batch_Exporter`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/includes/export/abstract-wc-csv-batch-exporter.php) as a base class to create a custom CSV export.

### Translation

WordPress Plugins use gettext to [translate strings](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/). Some strings located in PHP files are translated
via language files provided in `i18n/languages`. Additionally translations may/should be provided via the [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/trusted-shops-easy-integration-for-woocommerce/) GUI. The
readme file, responsible for formatting the [repository main page](https://wordpress.org/plugins/trusted-shops-easy-integration-for-woocommerce/), may be translated via this GUI.

### WPML

The plugin is compatible with WPML. In case WPML is activate, one sales channels per language is automatically registered. The compatibility script
is located at `src/Compatibility/WPML.php`. The script uses the available filter `ts_sales_channels` to register sales channels. Furthermore the compatibility
script filters the orders to be exported by the order export feature based on the order language.

### Deployment

Deploying a new version of the plugin works as follows:

1. Composer is updated, `vendor` dir is committed. JS build runs, TypeScript is compiled.
2. A GitHub Release is created
3. The new version is pushed via SVN to the WordPress plugin repository to publish the update.

To create a new version, make sure to bump versions in the following files:

1. `trusted-shops-easy-integration-for-woocommerce.php`
2. Update stable tag in `readme.txt` and provide changelog
3. `src/Package.php`

Afterwards you may use the deployment script via shell:

```
./bin/release.sh
```

This script uses [hub](https://github.com/github/hub) to create a new GitHub release and automatically updates the necessary SVN files.