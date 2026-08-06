=== Payment Method Highlighter for WooCommerce ===
Contributors: payment-method-highlighter
Tags: woocommerce, checkout, payment methods, conversion, checkout blocks
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Feature a preferred payment method in WooCommerce checkout with a badge, supporting message and custom accent colour.

== Description ==

Payment Method Highlighter helps merchants draw attention to a preferred payment option without changing the payment gateway or checkout flow.

* Choose any enabled WooCommerce payment gateway.
* Configure a badge, message, accent colour and visual style.
* Optionally make the highlighted gateway the default, only where the gateway is available.
* Supports both the classic checkout and WooCommerce Checkout Blocks.
* Compatible with High Performance Order Storage (HPOS).

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/payment-method-highlighter-for-woocommerce`, or install the ZIP via the WordPress Plugins screen.
2. Activate the plugin through the Plugins screen.
3. Go to WooCommerce > Payment highlighter.
4. Select a payment method and save your settings.

== Frequently Asked Questions ==

= Does this process payments? =

No. It only adds a presentation layer to an existing WooCommerce payment method. Your payment gateway continues to process orders as usual.

= Will it show a gateway that is not available to a customer? =

No. Gateway availability rules, such as country, currency and cart restrictions, remain controlled by WooCommerce and the gateway plugin.

== Changelog ==

= 1.0.0 =

* First release.
