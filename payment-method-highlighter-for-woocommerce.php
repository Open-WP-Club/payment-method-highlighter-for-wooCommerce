<?php
/**
 * Plugin Name: Payment Method Highlighter for WooCommerce
 * Description: Feature a preferred WooCommerce payment method with a configurable badge, message and polished checkout styling.
 * Version: 1.0.0
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Author: OpenWPClub.com
 * Author URI: https://openwpclub.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: payment-method-highlighter
 * Domain Path: /languages
 * WC requires at least: 8.3
 * WC tested up to: 10.9
 *
 * @package PaymentMethodHighlighter
 */

defined( 'ABSPATH' ) || exit;

define( 'PMH_VERSION', '1.0.0' );
define( 'PMH_FILE', __FILE__ );
define( 'PMH_PATH', plugin_dir_path( __FILE__ ) );
define( 'PMH_URL', plugin_dir_url( __FILE__ ) );

require_once PMH_PATH . 'includes/class-pmh-plugin.php';
require_once PMH_PATH . 'includes/class-pmh-settings.php';
require_once PMH_PATH . 'includes/class-pmh-assets.php';

add_action(
	'before_woocommerce_init',
	static function() {
		if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', PMH_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', PMH_FILE, true );
		}
	}
);

add_action(
	'plugins_loaded',
	static function() {
		PMH\Plugin::instance();
	}
);

register_activation_hook(
	PMH_FILE,
	static function() {
		if ( false === get_option( PMH\Settings::OPTION_KEY, false ) ) {
			add_option( PMH\Settings::OPTION_KEY, PMH\Settings::defaults() );
		}
	}
);
