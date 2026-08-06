<?php
/**
 * Admin settings page and settings sanitation.
 *
 * @package PaymentMethodHighlighter
 */

namespace PMH;

defined( 'ABSPATH' ) || exit;

final class Settings {
	const OPTION_KEY = 'pmh_options';

	public function __construct() {
	}

	public static function defaults() {
		return array(
			'schema_version'         => 3,
			'enabled'                => 'yes',
			'show_in_order_list'     => 'yes',
			'show_in_order_details'  => 'yes',
			'show_in_order_header'   => 'no',
			'show_missing_method'    => 'no',
			'methods'                => array(),
		);
	}

	public static function method_defaults( $gateway_id ) {
		return array(
			'enabled'      => 'yes',
			'accent_color' => self::default_color( $gateway_id ),
		);
	}

	/**
	 * Each gateway starts with a useful colour so merchants can enable several
	 * methods without having to configure a colour palette first.
	 */
	public static function default_color( $gateway_id ) {
		$brand_colours = array(
			'paypal'               => '#0070ba',
			'ppec_paypal'          => '#0070ba',
			'stripe'               => '#635bff',
			'woocommerce_payments' => '#635bff',
			'cod'                  => '#15803d',
			'bacs'                 => '#0f766e',
			'cheque'               => '#a16207',
			'klarna'               => '#c2255c',
		);

		if ( isset( $brand_colours[ $gateway_id ] ) ) {
			return $brand_colours[ $gateway_id ];
		}

		$palette = array( '#2563eb', '#7c3aed', '#0891b2', '#059669', '#d97706', '#dc2626', '#db2777', '#4f46e5' );
		$index   = (int) sprintf( '%u', crc32( $gateway_id ) ) % count( $palette );

		return $palette[ $index ];
	}

	public function get() {
		$stored = (array) get_option( self::OPTION_KEY, array() );

		if ( ! isset( $stored['methods'] ) && isset( $stored['gateway_id'] ) ) {
			return $this->migrate_legacy_options( $stored );
		}

		$is_previous_version = ! isset( $stored['schema_version'] );
		$options            = wp_parse_args( $stored, self::defaults() );
		$options['methods'] = isset( $options['methods'] ) && is_array( $options['methods'] ) ? $options['methods'] : array();

		// Version 1.0 saved every method as disabled by default. Promote those
		// records once so existing stores receive the new colour-first behaviour.
		if ( $is_previous_version ) {
			foreach ( $this->payment_gateways() as $gateway_id => $gateway ) {
				if ( ! isset( $options['methods'][ $gateway_id ] ) || ! is_array( $options['methods'][ $gateway_id ] ) ) {
					$options['methods'][ $gateway_id ] = array();
				}
				$options['methods'][ $gateway_id ]['enabled'] = 'yes';
			}
		}

		return $options;
	}

	/**
	 * Read the previous one-method setup without requiring merchants to re-enter it.
	 */
	private function migrate_legacy_options( $legacy ) {
		$options = self::defaults();
		$gateway = isset( $legacy['gateway_id'] ) ? sanitize_key( $legacy['gateway_id'] ) : '';

		$options['enabled']           = isset( $legacy['enabled'] ) ? $legacy['enabled'] : 'yes';
		if ( $gateway ) {
			$options['methods'][ $gateway ] = array(
				'enabled'      => 'yes',
				'accent_color' => isset( $legacy['accent_color'] ) ? $legacy['accent_color'] : self::default_color( $gateway ),
			);
		}

		return $options;
	}

	public function get_method( $gateway_id, $options = null ) {
		$options = is_array( $options ) ? $options : $this->get();
		$stored  = isset( $options['methods'][ $gateway_id ] ) && is_array( $options['methods'][ $gateway_id ] ) ? $options['methods'][ $gateway_id ] : array();

		return wp_parse_args( $stored, self::method_defaults( $gateway_id ) );
	}

	public function sanitize( $input ) {
		$input      = is_array( $input ) ? $input : array();
		$gateways   = $this->payment_gateways();
		$raw_methods = isset( $input['methods'] ) && is_array( $input['methods'] ) ? $input['methods'] : array();
		$methods    = array();

		foreach ( $gateways as $gateway_id => $gateway ) {
			$raw    = isset( $raw_methods[ $gateway_id ] ) && is_array( $raw_methods[ $gateway_id ] ) ? $raw_methods[ $gateway_id ] : array();
			$color  = isset( $raw['accent_color'] ) ? sanitize_hex_color( wp_unslash( $raw['accent_color'] ) ) : '';
			$method = self::method_defaults( $gateway_id );

			$methods[ $gateway_id ] = array(
				'enabled'      => isset( $raw['enabled'] ) ? 'yes' : 'no',
				'accent_color' => $color ? $color : $method['accent_color'],
			);
		}

		return array(
			'schema_version'       => 3,
			'enabled'              => isset( $input['enabled'] ) ? 'yes' : 'no',
			'show_in_order_list'   => isset( $input['show_in_order_list'] ) ? 'yes' : 'no',
			'show_in_order_details' => isset( $input['show_in_order_details'] ) ? 'yes' : 'no',
			'show_in_order_header' => isset( $input['show_in_order_header'] ) ? 'yes' : 'no',
			'show_missing_method'  => isset( $input['show_missing_method'] ) ? 'yes' : 'no',
			'methods'              => $methods,
		);
	}

	/**
	 * Render the fields inside the WooCommerce Settings form.
	 */
	public function render_fields() {
		$options  = $this->get();
		$gateways = $this->payment_gateways();
		?>
		<h2><?php esc_html_e( 'Payment Method Highlighter', 'payment-method-highlighter' ); ?></h2>
		<p><?php esc_html_e( 'Show a colour-coded payment method badge when an administrator opens a WooCommerce order. Each method can have its own colour.', 'payment-method-highlighter' ); ?></p>

		<?php if ( empty( $gateways ) ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'No payment methods are currently registered. Configure a payment gateway in WooCommerce → Settings → Payments, then return here.', 'payment-method-highlighter' ); ?></p></div>
		<?php endif; ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable payment colours', 'payment-method-highlighter' ); ?></th>
				<td>
					<label for="pmh-enabled"><input id="pmh-enabled" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[enabled]" type="checkbox" value="yes" <?php checked( $options['enabled'], 'yes' ); ?> /> <?php esc_html_e( 'Enable all payment-method badges.', 'payment-method-highlighter' ); ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Order list badge', 'payment-method-highlighter' ); ?></th>
				<td>
					<label for="pmh-show-in-order-list"><input id="pmh-show-in-order-list" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[show_in_order_list]" type="checkbox" value="yes" <?php checked( $options['show_in_order_list'], 'yes' ); ?> /> <?php esc_html_e( 'Show a colour-coded payment-method column in WooCommerce → Orders.', 'payment-method-highlighter' ); ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Order header badge', 'payment-method-highlighter' ); ?></th>
				<td>
					<label for="pmh-show-in-order-header"><input id="pmh-show-in-order-header" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[show_in_order_header]" type="checkbox" value="yes" <?php checked( $options['show_in_order_header'], 'yes' ); ?> /> <?php esc_html_e( 'Show a compact badge next to the order number when viewing an order.', 'payment-method-highlighter' ); ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Order details badge', 'payment-method-highlighter' ); ?></th>
				<td>
					<label for="pmh-show-in-order-details"><input id="pmh-show-in-order-details" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[show_in_order_details]" type="checkbox" value="yes" <?php checked( $options['show_in_order_details'], 'yes' ); ?> /> <?php esc_html_e( 'Show the detailed payment-method block in the order screen.', 'payment-method-highlighter' ); ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Orders without a payment method', 'payment-method-highlighter' ); ?></th>
				<td>
					<label for="pmh-show-missing-method"><input id="pmh-show-missing-method" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[show_missing_method]" type="checkbox" value="yes" <?php checked( $options['show_missing_method'], 'yes' ); ?> /> <?php esc_html_e( 'Show a neutral “No payment method” badge instead of hiding it.', 'payment-method-highlighter' ); ?></label>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Payment method colours', 'payment-method-highlighter' ); ?></h2>
		<p><?php esc_html_e( 'Every payment method starts with its own built-in colour. Uncheck Use colour for any method that should not receive a badge in the order admin screen.', 'payment-method-highlighter' ); ?></p>
		<table class="widefat striped">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Payment method', 'payment-method-highlighter' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Use colour', 'payment-method-highlighter' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Colour', 'payment-method-highlighter' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $gateways as $gateway_id => $gateway ) : ?>
							<?php $method = $this->get_method( $gateway_id, $options ); ?>
							<tr>
								<td>
									<strong><?php echo esc_html( $gateway['title'] ); ?></strong><br />
									<code><?php echo esc_html( $gateway_id ); ?></code>
									<?php if ( ! $gateway['active'] ) : ?><p class="description"><?php esc_html_e( 'Inactive in WooCommerce', 'payment-method-highlighter' ); ?></p><?php endif; ?>
								</td>
								<td><label class="screen-reader-text" for="pmh-method-<?php echo esc_attr( $gateway_id ); ?>-enabled"><?php esc_html_e( 'Use colour for this method', 'payment-method-highlighter' ); ?></label><input id="pmh-method-<?php echo esc_attr( $gateway_id ); ?>-enabled" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[methods][<?php echo esc_attr( $gateway_id ); ?>][enabled]" type="checkbox" value="yes" <?php checked( $method['enabled'], 'yes' ); ?> /></td>
								<td><label class="screen-reader-text" for="pmh-method-<?php echo esc_attr( $gateway_id ); ?>-colour"><?php esc_html_e( 'Accent colour', 'payment-method-highlighter' ); ?></label><input id="pmh-method-<?php echo esc_attr( $gateway_id ); ?>-colour" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[methods][<?php echo esc_attr( $gateway_id ); ?>][accent_color]" type="color" value="<?php echo esc_attr( $method['accent_color'] ); ?>" /></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
		</table>
		<?php
	}

	private function payment_gateways() {
		if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
			return array();
		}

		$gateways = array();
		foreach ( WC()->payment_gateways()->payment_gateways() as $gateway_id => $gateway ) {
			$gateways[ $gateway_id ] = array(
				'title'  => wp_strip_all_tags( $gateway->get_title() ),
				'active' => 'yes' === $gateway->enabled,
			);
		}

		return $gateways;
	}
}
