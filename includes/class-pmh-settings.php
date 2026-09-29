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
			'schema_version'                  => 4,
			'enabled'                         => 'yes',
			'show_in_order_list'              => 'yes',
			'show_in_order_details'           => 'yes',
			'show_in_order_header'            => 'no',
			'show_missing_method'             => 'no',
			'methods'                         => array(),
			'shipping_enabled'                => 'yes',
			'show_shipping_in_order_list'     => 'yes',
			'show_shipping_in_order_details'  => 'yes',
			'show_shipping_in_order_header'   => 'no',
			'show_missing_shipping_method'    => 'no',
			'shipping_methods'                => array(),
			'shipping_keywords'               => self::shipping_keyword_defaults(),
		);
	}

	/**
	 * Courier delivery types (e.g. "To office" vs "To address") are often
	 * encoded only in the shipping line item's title rather than in a
	 * distinct method id, in whichever language the store or courier plugin
	 * uses. Matching looks for these as plain substrings anywhere in the
	 * title (e.g. "офис" also matches "До офис", "Офис на Спиди", …), so the
	 * same short root word works across most Bulgarian and English phrasing.
	 * Office-type and address-type keywords share a colour across languages
	 * so the delivery type reads the same regardless of store language.
	 */
	public static function shipping_keyword_defaults() {
		return array(
			array(
				'keyword'      => 'офис',
				'enabled'      => 'yes',
				'accent_color' => '#7c3aed',
			),
			array(
				'keyword'      => 'office',
				'enabled'      => 'yes',
				'accent_color' => '#7c3aed',
			),
			array(
				'keyword'      => 'адрес',
				'enabled'      => 'yes',
				'accent_color' => '#0891b2',
			),
			array(
				'keyword'      => 'address',
				'enabled'      => 'yes',
				'accent_color' => '#0891b2',
			),
		);
	}

	public static function method_defaults( $gateway_id ) {
		return array(
			'enabled'      => 'yes',
			'accent_color' => self::default_color( $gateway_id ),
		);
	}

	public static function shipping_method_defaults( $method_id ) {
		return array(
			'enabled'      => 'yes',
			'accent_color' => self::default_shipping_color( $method_id ),
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

	/**
	 * Each shipping method starts with a useful colour so merchants can enable
	 * several methods without having to configure a colour palette first.
	 */
	public static function default_shipping_color( $method_id ) {
		$brand_colours = array(
			'flat_rate'     => '#2563eb',
			'free_shipping' => '#059669',
			'local_pickup'  => '#d97706',
		);

		if ( isset( $brand_colours[ $method_id ] ) ) {
			return $brand_colours[ $method_id ];
		}

		$palette = array( '#2563eb', '#7c3aed', '#0891b2', '#059669', '#d97706', '#dc2626', '#db2777', '#4f46e5' );
		$index   = (int) sprintf( '%u', crc32( 'shipping_' . $method_id ) ) % count( $palette );

		return $palette[ $index ];
	}

	/**
	 * Custom keywords have no fixed identifier, so their fallback colour is
	 * derived from the keyword text itself.
	 */
	public static function default_keyword_color( $keyword ) {
		$palette = array( '#7c3aed', '#0891b2', '#dc2626', '#d97706', '#059669', '#2563eb', '#db2777', '#4f46e5' );
		$index   = (int) sprintf( '%u', crc32( 'keyword_' . $keyword ) ) % count( $palette );

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

	public function get_shipping_method( $method_id, $options = null ) {
		$options = is_array( $options ) ? $options : $this->get();
		$stored  = isset( $options['shipping_methods'][ $method_id ] ) && is_array( $options['shipping_methods'][ $method_id ] ) ? $options['shipping_methods'][ $method_id ] : array();

		return wp_parse_args( $stored, self::shipping_method_defaults( $method_id ) );
	}

	/**
	 * Match a shipping line item's title against the configured keyword
	 * rules (e.g. "офис"/"office", "адрес"/"address") and return the colour
	 * of the first enabled keyword found anywhere in it, so delivery type
	 * can be highlighted independently from the underlying shipping method
	 * and regardless of which language the title is in.
	 *
	 * @param string     $title Shipping line item title.
	 * @param array|null $options Plugin options.
	 * @return string|null
	 */
	public function get_shipping_keyword_colour( $title, $options = null ) {
		$options = is_array( $options ) ? $options : $this->get();
		$rows    = isset( $options['shipping_keywords'] ) && is_array( $options['shipping_keywords'] ) ? $options['shipping_keywords'] : array();
		$title   = (string) $title;

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) || empty( $row['keyword'] ) ) {
				continue;
			}

			$enabled = isset( $row['enabled'] ) ? $row['enabled'] : 'no';
			if ( 'yes' !== $enabled ) {
				continue;
			}

			$keyword = (string) $row['keyword'];
			$found   = function_exists( 'mb_stripos' ) ? false !== mb_stripos( $title, $keyword ) : false !== stripos( $title, $keyword );
			if ( ! $found ) {
				continue;
			}

			$colour = isset( $row['accent_color'] ) ? sanitize_hex_color( $row['accent_color'] ) : '';

			return $colour ? $colour : self::default_keyword_color( $keyword );
		}

		return null;
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

		$shipping_list         = $this->shipping_methods();
		$raw_shipping_methods  = isset( $input['shipping_methods'] ) && is_array( $input['shipping_methods'] ) ? $input['shipping_methods'] : array();
		$shipping_methods      = array();

		foreach ( $shipping_list as $method_id => $shipping_method ) {
			$raw    = isset( $raw_shipping_methods[ $method_id ] ) && is_array( $raw_shipping_methods[ $method_id ] ) ? $raw_shipping_methods[ $method_id ] : array();
			$color  = isset( $raw['accent_color'] ) ? sanitize_hex_color( wp_unslash( $raw['accent_color'] ) ) : '';
			$method = self::shipping_method_defaults( $method_id );

			$shipping_methods[ $method_id ] = array(
				'enabled'      => isset( $raw['enabled'] ) ? 'yes' : 'no',
				'accent_color' => $color ? $color : $method['accent_color'],
			);
		}

		$raw_keyword_rows = isset( $input['shipping_keywords'] ) && is_array( $input['shipping_keywords'] ) ? $input['shipping_keywords'] : array();
		$shipping_keywords = array();

		foreach ( $raw_keyword_rows as $raw_row ) {
			$raw_row = is_array( $raw_row ) ? $raw_row : array();
			$keyword = isset( $raw_row['keyword'] ) ? trim( sanitize_text_field( wp_unslash( $raw_row['keyword'] ) ) ) : '';
			if ( '' === $keyword ) {
				continue;
			}

			$color = isset( $raw_row['accent_color'] ) ? sanitize_hex_color( wp_unslash( $raw_row['accent_color'] ) ) : '';

			$shipping_keywords[] = array(
				'keyword'      => $keyword,
				'enabled'      => isset( $raw_row['enabled'] ) ? 'yes' : 'no',
				'accent_color' => $color ? $color : self::default_keyword_color( $keyword ),
			);
		}

		return array(
			'schema_version'                  => 4,
			'enabled'                         => isset( $input['enabled'] ) ? 'yes' : 'no',
			'show_in_order_list'              => isset( $input['show_in_order_list'] ) ? 'yes' : 'no',
			'show_in_order_details'           => isset( $input['show_in_order_details'] ) ? 'yes' : 'no',
			'show_in_order_header'            => isset( $input['show_in_order_header'] ) ? 'yes' : 'no',
			'show_missing_method'             => isset( $input['show_missing_method'] ) ? 'yes' : 'no',
			'methods'                         => $methods,
			'shipping_enabled'                => isset( $input['shipping_enabled'] ) ? 'yes' : 'no',
			'show_shipping_in_order_list'     => isset( $input['show_shipping_in_order_list'] ) ? 'yes' : 'no',
			'show_shipping_in_order_details'  => isset( $input['show_shipping_in_order_details'] ) ? 'yes' : 'no',
			'show_shipping_in_order_header'   => isset( $input['show_shipping_in_order_header'] ) ? 'yes' : 'no',
			'show_missing_shipping_method'    => isset( $input['show_missing_shipping_method'] ) ? 'yes' : 'no',
			'shipping_methods'                => $shipping_methods,
			'shipping_keywords'               => $shipping_keywords,
		);
	}

	/**
	 * Render the fields inside the WooCommerce Settings form.
	 */
	public function render_fields() {
		$options       = $this->get();
		$gateways      = $this->payment_gateways();
		$shipping_list = $this->shipping_methods();
		$keyword_rows  = $this->pad_keyword_rows( $options['shipping_keywords'] );
		?>
		<h2><?php esc_html_e( 'Payment Method Highlighter', 'payment-method-highlighter-for-woocommerce' ); ?></h2>
		<p><?php esc_html_e( 'Show a colour-coded payment method badge when an administrator opens a WooCommerce order. Each method can have its own colour.', 'payment-method-highlighter-for-woocommerce' ); ?></p>

		<?php if ( empty( $gateways ) ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'No payment methods are currently registered. Configure a payment gateway in WooCommerce → Settings → Payments, then return here.', 'payment-method-highlighter-for-woocommerce' ); ?></p></div>
		<?php endif; ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable payment colours', 'payment-method-highlighter-for-woocommerce' ); ?></th>
				<td>
					<label for="pmh-enabled"><input id="pmh-enabled" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[enabled]" type="checkbox" value="yes" <?php checked( $options['enabled'], 'yes' ); ?> /> <?php esc_html_e( 'Enable all payment-method badges.', 'payment-method-highlighter-for-woocommerce' ); ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Order list badge', 'payment-method-highlighter-for-woocommerce' ); ?></th>
				<td>
					<label for="pmh-show-in-order-list"><input id="pmh-show-in-order-list" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[show_in_order_list]" type="checkbox" value="yes" <?php checked( $options['show_in_order_list'], 'yes' ); ?> /> <?php esc_html_e( 'Show a colour-coded payment-method column in WooCommerce → Orders.', 'payment-method-highlighter-for-woocommerce' ); ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Order header badge', 'payment-method-highlighter-for-woocommerce' ); ?></th>
				<td>
					<label for="pmh-show-in-order-header"><input id="pmh-show-in-order-header" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[show_in_order_header]" type="checkbox" value="yes" <?php checked( $options['show_in_order_header'], 'yes' ); ?> /> <?php esc_html_e( 'Show a compact badge next to the order number when viewing an order.', 'payment-method-highlighter-for-woocommerce' ); ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Order details badge', 'payment-method-highlighter-for-woocommerce' ); ?></th>
				<td>
					<label for="pmh-show-in-order-details"><input id="pmh-show-in-order-details" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[show_in_order_details]" type="checkbox" value="yes" <?php checked( $options['show_in_order_details'], 'yes' ); ?> /> <?php esc_html_e( 'Show the detailed payment-method block in the order screen.', 'payment-method-highlighter-for-woocommerce' ); ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Orders without a payment method', 'payment-method-highlighter-for-woocommerce' ); ?></th>
				<td>
					<label for="pmh-show-missing-method"><input id="pmh-show-missing-method" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[show_missing_method]" type="checkbox" value="yes" <?php checked( $options['show_missing_method'], 'yes' ); ?> /> <?php esc_html_e( 'Show a neutral “No payment method” badge instead of hiding it.', 'payment-method-highlighter-for-woocommerce' ); ?></label>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Payment method colours', 'payment-method-highlighter-for-woocommerce' ); ?></h2>
		<p><?php esc_html_e( 'Every payment method starts with its own built-in colour. Uncheck Use colour for any method that should not receive a badge in the order admin screen.', 'payment-method-highlighter-for-woocommerce' ); ?></p>
		<table class="widefat striped">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Payment method', 'payment-method-highlighter-for-woocommerce' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Use colour', 'payment-method-highlighter-for-woocommerce' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Colour', 'payment-method-highlighter-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $gateways as $gateway_id => $gateway ) : ?>
							<?php $method = $this->get_method( $gateway_id, $options ); ?>
							<tr>
								<td>
									<strong><?php echo esc_html( $gateway['title'] ); ?></strong><br />
									<code><?php echo esc_html( $gateway_id ); ?></code>
									<?php if ( ! $gateway['active'] ) : ?><p class="description"><?php esc_html_e( 'Inactive in WooCommerce', 'payment-method-highlighter-for-woocommerce' ); ?></p><?php endif; ?>
								</td>
								<td><label class="screen-reader-text" for="pmh-method-<?php echo esc_attr( $gateway_id ); ?>-enabled"><?php esc_html_e( 'Use colour for this method', 'payment-method-highlighter-for-woocommerce' ); ?></label><input id="pmh-method-<?php echo esc_attr( $gateway_id ); ?>-enabled" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[methods][<?php echo esc_attr( $gateway_id ); ?>][enabled]" type="checkbox" value="yes" <?php checked( $method['enabled'], 'yes' ); ?> /></td>
								<td><label class="screen-reader-text" for="pmh-method-<?php echo esc_attr( $gateway_id ); ?>-colour"><?php esc_html_e( 'Accent colour', 'payment-method-highlighter-for-woocommerce' ); ?></label><input id="pmh-method-<?php echo esc_attr( $gateway_id ); ?>-colour" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[methods][<?php echo esc_attr( $gateway_id ); ?>][accent_color]" type="color" value="<?php echo esc_attr( $method['accent_color'] ); ?>" /></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
		</table>

		<h2><?php esc_html_e( 'Shipping Method Highlighter', 'payment-method-highlighter-for-woocommerce' ); ?></h2>
		<p><?php esc_html_e( 'Show a colour-coded shipping method badge when an administrator opens a WooCommerce order. Each method can have its own colour.', 'payment-method-highlighter-for-woocommerce' ); ?></p>

		<?php if ( empty( $shipping_list ) ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'No shipping methods are currently registered. Configure a shipping method in WooCommerce → Settings → Shipping, then return here.', 'payment-method-highlighter-for-woocommerce' ); ?></p></div>
		<?php endif; ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable shipping colours', 'payment-method-highlighter-for-woocommerce' ); ?></th>
				<td>
					<label for="pmh-shipping-enabled"><input id="pmh-shipping-enabled" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[shipping_enabled]" type="checkbox" value="yes" <?php checked( $options['shipping_enabled'], 'yes' ); ?> /> <?php esc_html_e( 'Enable all shipping-method badges.', 'payment-method-highlighter-for-woocommerce' ); ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Order list badge', 'payment-method-highlighter-for-woocommerce' ); ?></th>
				<td>
					<label for="pmh-show-shipping-in-order-list"><input id="pmh-show-shipping-in-order-list" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[show_shipping_in_order_list]" type="checkbox" value="yes" <?php checked( $options['show_shipping_in_order_list'], 'yes' ); ?> /> <?php esc_html_e( 'Show a colour-coded shipping-method column in WooCommerce → Orders.', 'payment-method-highlighter-for-woocommerce' ); ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Order header badge', 'payment-method-highlighter-for-woocommerce' ); ?></th>
				<td>
					<label for="pmh-show-shipping-in-order-header"><input id="pmh-show-shipping-in-order-header" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[show_shipping_in_order_header]" type="checkbox" value="yes" <?php checked( $options['show_shipping_in_order_header'], 'yes' ); ?> /> <?php esc_html_e( 'Show a compact badge next to the order number when viewing an order.', 'payment-method-highlighter-for-woocommerce' ); ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Order details badge', 'payment-method-highlighter-for-woocommerce' ); ?></th>
				<td>
					<label for="pmh-show-shipping-in-order-details"><input id="pmh-show-shipping-in-order-details" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[show_shipping_in_order_details]" type="checkbox" value="yes" <?php checked( $options['show_shipping_in_order_details'], 'yes' ); ?> /> <?php esc_html_e( 'Show the detailed shipping-method block in the order screen.', 'payment-method-highlighter-for-woocommerce' ); ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Orders without a shipping method', 'payment-method-highlighter-for-woocommerce' ); ?></th>
				<td>
					<label for="pmh-show-missing-shipping-method"><input id="pmh-show-missing-shipping-method" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[show_missing_shipping_method]" type="checkbox" value="yes" <?php checked( $options['show_missing_shipping_method'], 'yes' ); ?> /> <?php esc_html_e( 'Show a neutral “No shipping method” badge instead of hiding it.', 'payment-method-highlighter-for-woocommerce' ); ?></label>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Shipping method colours', 'payment-method-highlighter-for-woocommerce' ); ?></h2>
		<p><?php esc_html_e( 'Every shipping method starts with its own built-in colour. Uncheck Use colour for any method that should not receive a badge in the order admin screen.', 'payment-method-highlighter-for-woocommerce' ); ?></p>
		<table class="widefat striped">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Shipping method', 'payment-method-highlighter-for-woocommerce' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Use colour', 'payment-method-highlighter-for-woocommerce' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Colour', 'payment-method-highlighter-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $shipping_list as $method_id => $shipping_method ) : ?>
							<?php $method = $this->get_shipping_method( $method_id, $options ); ?>
							<tr>
								<td>
									<strong><?php echo esc_html( $shipping_method['title'] ); ?></strong><br />
									<code><?php echo esc_html( $method_id ); ?></code>
								</td>
								<td><label class="screen-reader-text" for="pmh-shipping-method-<?php echo esc_attr( $method_id ); ?>-enabled"><?php esc_html_e( 'Use colour for this method', 'payment-method-highlighter-for-woocommerce' ); ?></label><input id="pmh-shipping-method-<?php echo esc_attr( $method_id ); ?>-enabled" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[shipping_methods][<?php echo esc_attr( $method_id ); ?>][enabled]" type="checkbox" value="yes" <?php checked( $method['enabled'], 'yes' ); ?> /></td>
								<td><label class="screen-reader-text" for="pmh-shipping-method-<?php echo esc_attr( $method_id ); ?>-colour"><?php esc_html_e( 'Accent colour', 'payment-method-highlighter-for-woocommerce' ); ?></label><input id="pmh-shipping-method-<?php echo esc_attr( $method_id ); ?>-colour" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[shipping_methods][<?php echo esc_attr( $method_id ); ?>][accent_color]" type="color" value="<?php echo esc_attr( $method['accent_color'] ); ?>" /></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
		</table>

		<h2><?php esc_html_e( 'Delivery type keyword colours', 'payment-method-highlighter-for-woocommerce' ); ?></h2>
		<p><?php esc_html_e( 'Some couriers encode the delivery type (e.g. “To office” vs “To address”) only in the shipping line title rather than as a separate method. A keyword is matched anywhere in the title (not case sensitive), so a short word like “office” or “офис” also matches longer titles such as “Speedy – To office” or “До офис на Спиди”. When a badge’s title contains an enabled keyword, its colour overrides the shipping method’s colour; the built-in list already covers common Bulgarian and English phrasing. Leave the keyword field blank to ignore a row.', 'payment-method-highlighter-for-woocommerce' ); ?></p>
		<table class="widefat striped">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Keyword', 'payment-method-highlighter-for-woocommerce' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Use colour', 'payment-method-highlighter-for-woocommerce' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Colour', 'payment-method-highlighter-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $keyword_rows as $index => $row ) : ?>
							<tr>
								<td>
									<label class="screen-reader-text" for="pmh-shipping-keyword-<?php echo esc_attr( $index ); ?>-keyword"><?php esc_html_e( 'Keyword', 'payment-method-highlighter-for-woocommerce' ); ?></label>
									<input id="pmh-shipping-keyword-<?php echo esc_attr( $index ); ?>-keyword" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[shipping_keywords][<?php echo esc_attr( $index ); ?>][keyword]" type="text" class="regular-text" value="<?php echo esc_attr( $row['keyword'] ); ?>" placeholder="<?php esc_attr_e( 'e.g. офис or office', 'payment-method-highlighter-for-woocommerce' ); ?>" />
								</td>
								<td><label class="screen-reader-text" for="pmh-shipping-keyword-<?php echo esc_attr( $index ); ?>-enabled"><?php esc_html_e( 'Use colour for this keyword', 'payment-method-highlighter-for-woocommerce' ); ?></label><input id="pmh-shipping-keyword-<?php echo esc_attr( $index ); ?>-enabled" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[shipping_keywords][<?php echo esc_attr( $index ); ?>][enabled]" type="checkbox" value="yes" <?php checked( $row['enabled'], 'yes' ); ?> /></td>
								<td><label class="screen-reader-text" for="pmh-shipping-keyword-<?php echo esc_attr( $index ); ?>-colour"><?php esc_html_e( 'Accent colour', 'payment-method-highlighter-for-woocommerce' ); ?></label><input id="pmh-shipping-keyword-<?php echo esc_attr( $index ); ?>-colour" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[shipping_keywords][<?php echo esc_attr( $index ); ?>][accent_color]" type="color" value="<?php echo esc_attr( $row['accent_color'] ); ?>" /></td>
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

	private function shipping_methods() {
		if ( ! function_exists( 'WC' ) || ! WC()->shipping() ) {
			return array();
		}

		$methods = array();
		foreach ( WC()->shipping()->get_shipping_methods() as $method_id => $method ) {
			$methods[ $method_id ] = array(
				'title' => wp_strip_all_tags( $method->get_method_title() ),
			);
		}

		return $methods;
	}

	/**
	 * Pad the saved keyword rows with a few blank rows so merchants always
	 * have room to add new keywords without a JavaScript "add row" control.
	 *
	 * @param array $rows Saved keyword rows.
	 * @return array
	 */
	private function pad_keyword_rows( $rows ) {
		$rows       = is_array( $rows ) ? array_values( $rows ) : array();
		$blank_rows = 4;
		$total      = max( count( $rows ), 2 ) + $blank_rows;

		while ( count( $rows ) < $total ) {
			$rows[] = array(
				'keyword'      => '',
				'enabled'      => 'yes',
				'accent_color' => self::default_keyword_color( 'row_' . count( $rows ) ),
			);
		}

		foreach ( $rows as $index => $row ) {
			$rows[ $index ] = wp_parse_args(
				is_array( $row ) ? $row : array(),
				array(
					'keyword'      => '',
					'enabled'      => 'no',
					'accent_color' => self::default_keyword_color( 'row_' . $index ),
				)
			);
		}

		return $rows;
	}
}
