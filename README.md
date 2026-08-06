# Payment Method Highlighter for WooCommerce

A modern WooCommerce plugin that shows a colour-coded payment-method badge when an administrator opens an order. It does not create or process payments—it works with your existing payment gateways.

## Features

- Configure colours for one or more WooCommerce payment gateways from the standard WordPress admin interface.
- Each order displays the payment method as a colour-coded badge in WooCommerce Admin.
- Every payment gateway starts with a built-in colour; uncheck any method you do not want to display.
- Supports legacy order storage and High Performance Order Storage (HPOS).
- Declares compatibility with High Performance Order Storage (HPOS).
- Accessible focus states, `prefers-reduced-motion` support, and no external dependencies.

## Installation

1. Upload this directory to `wp-content/plugins/`, or create a ZIP and install it from the WordPress admin.
2. Activate **Payment Method Highlighter for WooCommerce**.
3. Open **WooCommerce → Payment highlighter**.
4. Choose an active payment gateway, customise the content, and save your changes.

## Requirements

- WordPress 6.5+
- PHP 7.4+
- WooCommerce 8.3+ (tested through 10.9)

## Development

The plugin has no build step and adds no npm or PHP dependencies. Before a release, run:

```sh
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```
