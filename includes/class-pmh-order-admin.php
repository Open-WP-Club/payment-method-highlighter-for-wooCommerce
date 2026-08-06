<?php
/**
 * Colour-coded payment method display on the WooCommerce order screen.
 *
 * @package PaymentMethodHighlighter
 */

namespace PMH;

defined( 'ABSPATH' ) || exit;

final class Order_Admin {
	/** @var Settings */
	private $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
		add_action( 'woocommerce_admin_order_data_after_payment_info', array( $this, 'render_payment_method' ) );
	}

	/**
	 * Render a compact, colour-coded payment method note in a single order view.
	 * This hook receives a WC_Order in both legacy order storage and HPOS.
	 *
	 * @param \WC_Order $order Order being displayed.
	 */
	public function render_payment_method( $order ) {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$options = $this->settings->get();
		if ( 'yes' !== $options['enabled'] ) {
			return;
		}

		$gateway_id = $order->get_payment_method();
		if ( ! $gateway_id ) {
			return;
		}

		$method = $this->settings->get_method( $gateway_id, $options );
		if ( 'yes' !== $method['enabled'] ) {
			return;
		}

		$title = $order->get_payment_method_title();
		$title = $title ? $title : $gateway_id;
		$colour = sanitize_hex_color( $method['accent_color'] );
		$colour = $colour ? $colour : Settings::default_color( $gateway_id );
		?>
		<p class="pmh-order-payment-method" style="--pmh-payment-colour: <?php echo esc_attr( $colour ); ?>;">
			<span class="pmh-order-payment-method__label"><?php esc_html_e( 'Payment method', 'payment-method-highlighter' ); ?></span>
			<span class="pmh-order-payment-method__badge"><?php echo esc_html( $title ); ?></span>
		</p>
		<?php
	}
}
