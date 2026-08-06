# Payment Method Highlighter for WooCommerce

A modern WooCommerce plugin that visually highlights a selected payment method at checkout. It does not create or process payments—it works with your existing payment gateways.

## Features

- Choose an active WooCommerce payment gateway from a simple admin interface.
- Customise the badge, supporting message, accent colour, and one of two display styles.
- Optionally preselect the method, only when it is available for the current customer and cart.
- Supports the classic WooCommerce checkout and Checkout Blocks.
- Declares compatibility with Cart & Checkout Blocks and HPOS.
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
node --check assets/js/frontend.js
```
