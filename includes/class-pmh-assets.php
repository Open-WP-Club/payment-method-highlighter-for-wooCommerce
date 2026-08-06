<?php
/**
 * Public and admin asset loading.
 *
 * @package PaymentMethodHighlighter
 */

namespace PMH;

defined( 'ABSPATH' ) || exit;

final class Assets {
	/** @var Settings */
	private $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
	}

	public function enqueue_frontend() {
		$options = $this->settings->get();
		if ( 'yes' !== $options['enabled'] || empty( $options['gateway_id'] ) || ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
			return;
		}

		wp_enqueue_style( 'pmh-checkout', PMH_URL . 'assets/css/frontend.css', array(), PMH_VERSION );
		wp_enqueue_script( 'pmh-checkout', PMH_URL . 'assets/js/frontend.js', array(), PMH_VERSION, true );
		wp_add_inline_style( 'pmh-checkout', $this->frontend_variables( $options ) );
		wp_localize_script(
			'pmh-checkout',
			'pmhCheckout',
			array(
				'gatewayId' => $options['gateway_id'],
				'badge'     => $options['badge'],
				'message'   => $options['message'],
				'preselect' => 'yes' === $options['preselect'],
			)
		);
	}

	public function enqueue_admin( $hook ) {
		if ( 'woocommerce_page_payment-method-highlighter' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'pmh-admin', PMH_URL . 'assets/css/admin.css', array(), PMH_VERSION );
	}

	private function frontend_variables( $options ) {
		$color = sanitize_hex_color( $options['accent_color'] );
		$rgb   = $this->hex_to_rgb( $color ? $color : '#6d28d9' );

		return sprintf(
			':root { --pmh-accent: %1$s; --pmh-accent-rgb: %2$d, %3$d, %4$d; --pmh-surface-opacity: %5$s; }',
			esc_html( $color ? $color : '#6d28d9' ),
			$rgb[0],
			$rgb[1],
			$rgb[2],
			'solid' === $options['style'] ? '0.14' : '0.07'
		);
	}

	private function hex_to_rgb( $color ) {
		$color = ltrim( $color, '#' );

		return array(
			hexdec( substr( $color, 0, 2 ) ),
			hexdec( substr( $color, 2, 2 ) ),
			hexdec( substr( $color, 4, 2 ) ),
		);
	}
}
