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

	/** @var array<int, bool> */
	private $rendered_orders = array();

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
		/*
		 * This hook sits directly below WooCommerce's native "Payment via …"
		 * line in the order details header. It is available for both legacy
		 * order storage and HPOS order edit screens.
		 */
		add_action( 'woocommerce_admin_order_data_after_payment_info', array( $this, 'render_payment_method' ), 20 );
		add_action( 'woocommerce_admin_order_data_header_right', array( $this, 'render_header_badge' ), 20 );
		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'add_order_list_column' ) );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'render_order_list_column' ), 10, 2 );
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_order_list_column' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_order_list_column_legacy' ), 10, 1 );
		/*
		 * Some order-editor integrations omit the header hook but retain the
		 * established billing-address hook. Render there only when needed.
		 */
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'render_payment_method_fallback' ), 20 );
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

		$order_id = $order->get_id();
		if ( isset( $this->rendered_orders[ $order_id ] ) ) {
			return;
		}

		$options = $this->settings->get();
		if ( 'yes' !== $options['show_in_order_details'] ) {
			return;
		}
		$badge = $this->get_badge( $order, $options );
		if ( ! $badge ) {
			return;
		}

		$this->rendered_orders[ $order_id ] = true;
		?>
		<p class="pmh-order-payment-method" style="--pmh-payment-colour: <?php echo esc_attr( $badge['colour'] ); ?>;">
			<span class="pmh-order-payment-method__label"><?php esc_html_e( 'Payment method', 'payment-method-highlighter' ); ?></span>
			<span class="pmh-order-payment-method__badge"><?php echo esc_html( $badge['title'] ); ?></span>
		</p>
		<?php
	}

	/**
	 * Render a compact badge alongside the order heading.
	 *
	 * @param \WC_Order $order Order being displayed.
	 */
	public function render_header_badge( $order ) {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$options = $this->settings->get();
		if ( 'yes' !== $options['show_in_order_header'] ) {
			return;
		}

		$badge = $this->get_badge( $order, $options );
		if ( ! $badge ) {
			return;
		}
		?>
		<span class="pmh-order-header-badge" style="--pmh-payment-colour: <?php echo esc_attr( $badge['colour'] ); ?>;">
			<span class="screen-reader-text"><?php esc_html_e( 'Payment method:', 'payment-method-highlighter' ); ?> </span><?php echo esc_html( $badge['title'] ); ?>
		</span>
		<?php
	}

	/**
	 * Add the payment method column to both order list implementations.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function add_order_list_column( $columns ) {
		$options = $this->settings->get();
		if ( 'yes' !== $options['enabled'] || 'yes' !== $options['show_in_order_list'] ) {
			return $columns;
		}

		$updated = array();
		foreach ( $columns as $key => $label ) {
			$updated[ $key ] = $label;
			if ( 'order_number' === $key ) {
				$updated['pmh_payment_method'] = __( 'Payment method', 'payment-method-highlighter' );
			}
		}

		return $updated;
	}

	/**
	 * Render the payment method column for HPOS order lists.
	 *
	 * @param string    $column_name Column name.
	 * @param \WC_Order $order Order being displayed.
	 */
	public function render_order_list_column( $column_name, $order ) {
		if ( 'pmh_payment_method' !== $column_name || ! $order instanceof \WC_Order ) {
			return;
		}

		$this->render_list_badge( $order );
	}

	/**
	 * Render the payment method column for legacy order lists.
	 *
	 * @param string $column_name Column name.
	 */
	public function render_order_list_column_legacy( $column_name ) {
		global $the_order;

		if ( 'pmh_payment_method' === $column_name && $the_order instanceof \WC_Order ) {
			$this->render_list_badge( $the_order );
		}
	}

	/**
	 * Output the compact list-table badge.
	 *
	 * @param \WC_Order $order Order being displayed.
	 */
	private function render_list_badge( $order ) {
		$badge = $this->get_badge( $order );
		if ( ! $badge ) {
			return;
		}
		?>
		<span class="pmh-order-list-badge" style="--pmh-payment-colour: <?php echo esc_attr( $badge['colour'] ); ?>;"><?php echo esc_html( $badge['title'] ); ?></span>
		<?php
	}

	/**
	 * Return the badge data for an order, or false if it should be hidden.
	 *
	 * @param \WC_Order  $order Order being displayed.
	 * @param array|null $options Plugin options.
	 * @return array|false
	 */
	private function get_badge( $order, $options = null ) {
		$options = is_array( $options ) ? $options : $this->settings->get();
		if ( 'yes' !== $options['enabled'] ) {
			return false;
		}

		$gateway_id = $order->get_payment_method();
		if ( ! $gateway_id ) {
			if ( 'yes' !== $options['show_missing_method'] ) {
				return false;
			}

			return array(
				'title'  => __( 'No payment method', 'payment-method-highlighter' ),
				'colour' => '#6b7280',
			);
		}

		$method = $this->settings->get_method( $gateway_id, $options );
		if ( 'yes' !== $method['enabled'] ) {
			return false;
		}

		$colour = sanitize_hex_color( $method['accent_color'] );
		return array(
			'title'  => $order->get_payment_method_title() ? $order->get_payment_method_title() : $gateway_id,
			'colour' => $colour ? $colour : Settings::default_color( $gateway_id ),
		);
	}

	/**
	 * Render a single fallback badge on integrations that don't call the
	 * payment-info hook.
	 *
	 * @param \WC_Order $order Order being displayed.
	 */
	public function render_payment_method_fallback( $order ) {
		$this->render_payment_method( $order );
	}
}
