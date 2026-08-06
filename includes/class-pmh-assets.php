<?php
/**
 * WooCommerce order admin assets.
 *
 * @package PaymentMethodHighlighter
 */

namespace PMH;

defined( 'ABSPATH' ) || exit;

final class Assets {
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_order_assets' ) );
	}

	public function enqueue_order_assets() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen ) {
			return;
		}

		$order_screens = array( 'shop_order' );
		if ( function_exists( 'wc_get_page_screen_id' ) ) {
			$order_screens[] = wc_get_page_screen_id( 'shop-order' );
		}

		if ( ! in_array( $screen->id, $order_screens, true ) ) {
			return;
		}

		wp_enqueue_style( 'pmh-order-admin', PMH_URL . 'assets/css/order-admin.css', array(), PMH_VERSION );
	}
}
