=== Payment Method Highlighter for WooCommerce ===
Contributors: payment-method-highlighter
Tags: woocommerce, orders, payment methods, order management, admin
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Show a colour-coded payment method badge when an administrator opens a WooCommerce order.

== Description ==

Payment Method Highlighter helps administrators identify the payment method used on an order without changing how payments are processed.

* Configure an independent colour for every payment gateway.
* Show a colour-coded payment method badge in the order admin screen.
* Every payment gateway starts with a built-in colour; uncheck any method you do not want to display.
* Supports legacy order storage and High Performance Order Storage (HPOS).
* Compatible with High Performance Order Storage (HPOS).

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/payment-method-highlighter-for-woocommerce`, or install the ZIP via the WordPress Plugins screen.
2. Activate the plugin through the Plugins screen.
3. Go to WooCommerce > Settings > Payment highlighter.
4. Select a payment method and save your settings.

== Frequently Asked Questions ==

= Does this process payments? =

No. It only adds a presentation layer to an existing WooCommerce payment method. Your payment gateway continues to process orders as usual.

= Will it show a gateway that is not available to a customer? =

No. Gateway availability rules, such as country, currency and cart restrictions, remain controlled by WooCommerce and the gateway plugin.

== Changelog ==

= 1.2.1 =

* Add an independent on/off setting for the detailed order-screen badge.

= 1.2.0 =

* Add optional payment badges to the WooCommerce order list and order header.
* Add an optional neutral badge for orders without a payment method.

= 1.1.2 =

* Add a translation template and Bulgarian interface translation.

= 1.1.1 =

* Move plugin settings to WooCommerce > Settings.
* Add a fallback placement for the order payment-method badge.

= 1.1.0 =

* Add independent settings and built-in colours for every payment method.
* Update the settings screen to the standard WordPress admin layout.

= 1.0.0 =

* First release.
