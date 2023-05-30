=== Trusted Shops Easy Integration for WooCommerce ===
Contributors: vendidero
Tags: trusted shops, woocommerce, badge, trust, business ratings, business reviews, trustbadge, integration
Requires at least: 4.9
Tested up to: 6.1
WC requires at least: 3.9
WC tested up to: 7.3
Stable tag: 1.0.6
Requires PHP: 5.6
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Show that your customers love you with reviews in your online store and boost your business with the free Trusted Shops Easy Integration Plugin for WooCommerce.

== Description ==

The well-known Trustmark, the Buyer Protection and the authentic reviews from Trusted Shops have stood for trust for over 20 years. More than 30,000 online shops throughout Europe use our Trust solutions for more traffic, higher sales and better conversion rates.

Trusted Shops Easy Integration is the easiest and fastest way to convince visitors of the trustworthiness of your online shop. The simple installation guarantees product use in just 5 minutes and usually requires little to no prior technical knowledge. With our extension you are always technically up to date and have no additional maintenance effort.

Your benefit: With just a few clicks, visitors to your online shop can see trust elements such as the Trustbadge or other on-site widgets, can benefit from buyer protection and are automatically asked for feedback after placing an order.

= All features at a glance: =

* Show Trustbadge, integrate Buyer Protection & collect shop reviews
* Present your collected reviews in an appealing and sales-boosting way
* Collect and display Product Reviews
* Configure multiple shops within the same plugin

Please note: To use the extension Trusted Shops Easy Integration, you need an existing Trusted Shops membership. You can find out more about the products and benefits of Trusted Shops on our [website](https://business.trustedshops.com/) or by calling: +44 23364 5906

== Installation ==

Install this plugin via WP-Admin > Plugins and connect your site via WooCommerce > Settings > Trusted Shops.

= Shortcodes =

`[ts_widget id="{widget_id}" product_identifier="{identifier}"]`
Use this shortcode to embed a specific widget, e.g.: `[ts_widget id="wdg-d6dc1e38-d43b-46aa-123e-232441"]`. Please mind that
embedding product-specific widgets by passing an identifier (e.g. sku, gtin, mpn) will only work on product-specific pages.

== Plugin architecture ==

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

= Hooks =

All frontend-related hooks, e.g. to output Trustbadge, Trustcard and Widgets are managed in `src/Hooks.php`. External assets (e.g. the TS widgets script) are lazy-enqueued
via the [WordPress script API](https://developer.wordpress.org/reference/functions/wp_enqueue_script/) as soon as a widget is rendered.

For each widget position (e.g. `product_title`) a custom hooks is placed, e.g. `ts_easy_integration_single_product_title_widgets`. This way users may easily customize where/when
to output widgets by attaching custom scripts to it or removing the default logic to output the widget.

To render a widget, 2 templates exist:

1. `templates/widgets/product-widget.php` is used for product-related widgets.
2. `templates/widgets/service-widget.php` is used for service widgets.

== Overriding templates ==

Templates may be overridden in a (child-) theme. Overriding the `templates/widgets/product-widget.php` template in your theme 
by placing the original file in the following directory: `my-child-theme/woocommerce/widgets/product-widget.php`.

== Trustcard ==

The Trustcard is placed by using the `woocommerce_thankyou` hook. The template `templates/checkout/trustcard.php` contains the content.

== Product attributes ==

= Brand =

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

= GTIN, MPN, SKU =

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

== Shortcodes ==

This plugins registers the `ts_widget` [shortcode](https://codex.wordpress.org/Shortcode_API) which may be used to
output a certain widget (with an ID) at a custom location, e.g. in a page, post etc. E.g.:

`[ts_widget id="{widget_id}" product_identifier="{product_identifier}"]`

* `{widget_id}` The ID of the widget, see the widgets panel for details.
* `{product_identifier}` Product identifier type (e.g. sku, mpn, gtin) in case this is a product-related widget.

Please be aware that product-related widgets will only work if the global `$product` variable is set and valid, e.g. on single product pages.

== Secrets Storage ==

The secrets (Client ID, Client secret) are stored as encrypted values in the `wp_options` table. The `src/SecretsHelper.php` file contains the 
encryption/decryption logic, using PHP's built-in `sodium_crypto_secretbox`. The encryption key is automagically placed in the [wp-config.php](https://wordpress.org/support/article/editing-wp-config-php/) file
upon installation. In case the encryption key is missing or could not be placed, a warning is being displayed, containing a random key to be inserted manually by the shop owner.

== Order Export ==

The order export feature, located in `src/OrderExporter.php` is using the [`WC_CSV_Batch_Exporter`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/includes/export/abstract-wc-csv-batch-exporter.php) as a base class to create a custom CSV export.

== Translation ==

WordPress Plugins use gettext to [translate strings](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/). Some strings located in PHP files are translated
via language files provided in `i18n/languages`. Additionally translations may/should be provided via the [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/trusted-shops-easy-integration-for-woocommerce/) GUI. The
readme file, responsible for formatting the [repository main page](https://wordpress.org/plugins/trusted-shops-easy-integration-for-woocommerce/), may be translated via this GUI.

== WPML ==

The plugin is compatible with WPML. In case WPML is activate, one sales channels per language is automatically registered. The compatibility script
is located at `src/Compatibility/WPML.php`. The script uses the available filter `ts_sales_channels` to register sales channels. Furthermore the compatibility
script filters the orders to be exported by the order export feature based on the order language.

== Enable QA-mode ==

To enable QA-mode (loads assets from QA instead of live servers), define the constant `TS_EASY_INTEGRATION_IS_DEBUG_MODE` and set it to `true` in your wp-config.php:

```php
define( 'TS_EASY_INTEGRATION_IS_DEBUG_MODE', true );
```

== Deployment ==

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

= Minimal Requirements =

* WordPress 4.9 or newer
* WooCommerce 3.9 (newest version recommended)
* PHP Version 5.6 or newer

== Frequently Asked Questions ==

= Do you need help with Trusted Shops Easy Integration? =
[Learn](https://help.etrusted.com/hc/en-gb/articles/4905016318237-Using-Trusted-Shops-with-a-plugin) how to use Trusted Shops with a Plugin.

== Screenshots ==

1. Screenshot 1
2. Screenshot 2
3. Screenshot 3
4. Screenshot 4
5. Screenshot 5

== Changelog ==
= 1.0.6 =
* Fix: Update textdomain to reflect plugin slug
* Fix: Fallback language file path

= 1.0.5 =
* Improvement: Compatibility during content filter removal

= 1.0.4 =
* Fix: PHP warning for PHP <= 7.4

= 1.0.3 =
* First release

== Upgrade Notice ==

= 1.0.3 =
* no upgrade - just install :)
