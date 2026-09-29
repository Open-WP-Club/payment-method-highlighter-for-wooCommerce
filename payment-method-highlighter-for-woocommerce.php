<?php
/**
 * Plugin Name: Payment Method Highlighter for WooCommerce
 * Description: Show colour-coded payment and shipping method badges when an administrator opens a WooCommerce order.
 * Version: 1.4.0
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Author: OpenWPClub.com
 * Author URI: https://openwpclub.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: payment-method-highlighter-for-woocommerce
 * Domain Path: /languages
 * WC requires at least: 8.3
 * WC tested up to: 10.9
 *
 * @package PaymentMethodHighlighter
 */

defined( 'ABSPATH' ) || exit;

define( 'PMH_VERSION', '1.4.0' );
define( 'PMH_FILE', __FILE__ );
define( 'PMH_PATH', plugin_dir_path( __FILE__ ) );
define( 'PMH_URL', plugin_dir_url( __FILE__ ) );

require_once PMH_PATH . 'includes/class-pmh-plugin.php';
require_once PMH_PATH . 'includes/class-pmh-settings.php';
require_once PMH_PATH . 'includes/class-pmh-assets.php';
require_once PMH_PATH . 'includes/class-pmh-order-admin.php';

add_action(
	'before_woocommerce_init',
	static function() {
		if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) ) {
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
