<?php
/**
 * Colour-coded payment and shipping method display on the WooCommerce order screen.
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

	/** @var array<int, bool> */
	private $rendered_shipping_orders = array();

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
		/*
		 * This hook sits directly below WooCommerce's native "Payment via …"
		 * line in the order details header. It is available for both legacy
		 * order storage and HPOS order edit screens.
		 */
		add_action( 'woocommerce_admin_order_data_after_payment_info', array( $this, 'render_payment_method' ), 20 );
		add_action( 'woocommerce_admin_order_data_header_right', array( $this, 'render_header_badge' ), 20 );
		add_action( 'woocommerce_admin_order_data_header_right', array( $this, 'render_shipping_header_badge' ), 25 );
		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'add_order_list_column' ) );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'render_order_list_column' ), 10, 2 );
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_order_list_column' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_order_list_column_legacy' ), 10, 1 );
		/*
		 * Some order-editor integrations omit the header hook but retain the
		 * established billing-address hook. Render there only when needed.
		 */
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'render_payment_method_fallback' ), 20 );
		/*
		 * This hook sits directly below WooCommerce's native shipping address
		 * block in the order details screen, mirroring the payment method
		 * placement above.
		 */
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'render_shipping_method' ), 20 );
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
		<p class="pmh-order-payment-method" style="--pmh-accent-colour: <?php echo esc_attr( $badge['colour'] ); ?>;">
			<span class="pmh-order-payment-method__label"><?php esc_html_e( 'Payment method', 'payment-method-highlighter-for-woocommerce' ); ?></span>
			<span class="pmh-order-payment-method__badge"><?php echo esc_html( $badge['title'] ); ?></span>
		</p>
		<?php
	}

	/**
	 * Render a compact, colour-coded shipping method note in a single order
	 * view. Orders can carry more than one shipping line item, so each gets
	 * its own badge.
	 *
	 * @param \WC_Order $order Order being displayed.
	 */
	public function render_shipping_method( $order ) {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$order_id = $order->get_id();
		if ( isset( $this->rendered_shipping_orders[ $order_id ] ) ) {
			return;
		}

		$options = $this->settings->get();
		if ( 'yes' !== $options['show_shipping_in_order_details'] ) {
			return;
		}

		$badges = $this->get_shipping_badges( $order, $options );
		if ( ! $badges ) {
			return;
		}

		$this->rendered_shipping_orders[ $order_id ] = true;
		?>
		<p class="pmh-order-shipping-method">
			<span class="pmh-order-shipping-method__label"><?php esc_html_e( 'Shipping method', 'payment-method-highlighter-for-woocommerce' ); ?></span>
			<?php foreach ( $badges as $badge ) : ?>
				<span class="pmh-order-shipping-method__badge" style="--pmh-accent-colour: <?php echo esc_attr( $badge['colour'] ); ?>;"><?php echo esc_html( $badge['title'] ); ?></span>
			<?php endforeach; ?>
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
		<span class="pmh-order-header-badge" style="--pmh-accent-colour: <?php echo esc_attr( $badge['colour'] ); ?>;">
			<span class="screen-reader-text"><?php esc_html_e( 'Payment method:', 'payment-method-highlighter-for-woocommerce' ); ?> </span><?php echo esc_html( $badge['title'] ); ?>
		</span>
		<?php
	}

	/**
	 * Render compact shipping-method badges alongside the order heading.
	 *
	 * @param \WC_Order $order Order being displayed.
	 */
	public function render_shipping_header_badge( $order ) {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$options = $this->settings->get();
		if ( 'yes' !== $options['show_shipping_in_order_header'] ) {
			return;
		}

		$badges = $this->get_shipping_badges( $order, $options );
		foreach ( $badges as $badge ) :
			?>
			<span class="pmh-order-header-badge" style="--pmh-accent-colour: <?php echo esc_attr( $badge['colour'] ); ?>;">
				<span class="screen-reader-text"><?php esc_html_e( 'Shipping method:', 'payment-method-highlighter-for-woocommerce' ); ?> </span><?php echo esc_html( $badge['title'] ); ?>
			</span>
			<?php
		endforeach;
	}

	/**
	 * Add the payment- and shipping-method columns to both order list
	 * implementations.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function add_order_list_column( $columns ) {
		$options       = $this->settings->get();
		$show_payment  = 'yes' === $options['enabled'] && 'yes' === $options['show_in_order_list'];
		$show_shipping = 'yes' === $options['shipping_enabled'] && 'yes' === $options['show_shipping_in_order_list'];

		if ( ! $show_payment && ! $show_shipping ) {
			return $columns;
		}

		$updated = array();
		foreach ( $columns as $key => $label ) {
			$updated[ $key ] = $label;
			if ( 'order_number' === $key ) {
				if ( $show_payment ) {
					$updated['pmh_payment_method'] = __( 'Payment method', 'payment-method-highlighter-for-woocommerce' );
				}
				if ( $show_shipping ) {
					$updated['pmh_shipping_method'] = __( 'Shipping method', 'payment-method-highlighter-for-woocommerce' );
				}
			}
		}

		return $updated;
	}

	/**
	 * Render the payment- or shipping-method column for HPOS order lists.
	 *
	 * @param string    $column_name Column name.
	 * @param \WC_Order $order Order being displayed.
	 */
	public function render_order_list_column( $column_name, $order ) {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		if ( 'pmh_payment_method' === $column_name ) {
			$this->render_list_badge( $order );
		} elseif ( 'pmh_shipping_method' === $column_name ) {
			$this->render_shipping_list_badge( $order );
		}
	}

	/**
	 * Render the payment- or shipping-method column for legacy order lists.
	 *
	 * @param string $column_name Column name.
	 */
	public function render_order_list_column_legacy( $column_name ) {
		global $the_order;

		if ( ! $the_order instanceof \WC_Order ) {
			return;
		}

		if ( 'pmh_payment_method' === $column_name ) {
			$this->render_list_badge( $the_order );
		} elseif ( 'pmh_shipping_method' === $column_name ) {
			$this->render_shipping_list_badge( $the_order );
		}
	}

	/**
	 * Output the compact list-table badge for the payment method.
	 *
	 * @param \WC_Order $order Order being displayed.
	 */
	private function render_list_badge( $order ) {
		$badge = $this->get_badge( $order );
		if ( ! $badge ) {
			return;
		}
		?>
		<span class="pmh-order-list-badge" style="--pmh-accent-colour: <?php echo esc_attr( $badge['colour'] ); ?>;"><?php echo esc_html( $badge['title'] ); ?></span>
		<?php
	}

	/**
	 * Output the compact list-table badges for the shipping method(s).
	 *
	 * @param \WC_Order $order Order being displayed.
	 */
	private function render_shipping_list_badge( $order ) {
		foreach ( $this->get_shipping_badges( $order ) as $badge ) {
			?>
			<span class="pmh-order-list-badge" style="--pmh-accent-colour: <?php echo esc_attr( $badge['colour'] ); ?>;"><?php echo esc_html( $badge['title'] ); ?></span>
			<?php
		}
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
				'title'  => __( 'No payment method', 'payment-method-highlighter-for-woocommerce' ),
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
	 * Return the shipping-method badge data for an order. Orders can carry
	 * more than one shipping line item (e.g. split packages), so this
	 * returns a list rather than a single badge.
	 *
	 * @param \WC_Order  $order Order being displayed.
	 * @param array|null $options Plugin options.
	 * @return array
	 */
	private function get_shipping_badges( $order, $options = null ) {
		$options = is_array( $options ) ? $options : $this->settings->get();
		if ( 'yes' !== $options['shipping_enabled'] ) {
			return array();
		}

		$badges = array();
		foreach ( $order->get_items( 'shipping' ) as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Shipping ) {
				continue;
			}

			$method_id = $item->get_method_id();
			$method    = $this->settings->get_shipping_method( $method_id, $options );
			if ( 'yes' !== $method['enabled'] ) {
				continue;
			}

			$colour = sanitize_hex_color( $method['accent_color'] );
			$colour = $colour ? $colour : Settings::default_shipping_color( $method_id );
			$title  = $item->get_name() ? $item->get_name() : $method_id;

			/*
			 * Delivery type (e.g. "To office" vs "To address") is often only
			 * present in the line item's title, so a keyword match overrides
			 * the shipping method's own colour.
			 */
			$keyword_colour = $this->settings->get_shipping_keyword_colour( $title, $options );

			$badges[] = array(
				'title'  => $title,
				'colour' => $keyword_colour ? $keyword_colour : $colour,
			);
		}

		if ( ! $badges && 'yes' === $options['show_missing_shipping_method'] ) {
			$badges[] = array(
				'title'  => __( 'No shipping method', 'payment-method-highlighter-for-woocommerce' ),
				'colour' => '#6b7280',
			);
		}

		return $badges;
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
