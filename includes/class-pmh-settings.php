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
		add_action( 'admin_menu', array( $this, 'register_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public static function defaults() {
		return array(
			'enabled'      => 'yes',
			'gateway_id'   => '',
			'badge'        => __( 'Recommended', 'payment-method-highlighter' ),
			'message'      => __( 'A fast, secure choice for your order.', 'payment-method-highlighter' ),
			'accent_color' => '#6d28d9',
			'style'        => 'soft',
			'preselect'    => 'no',
		);
	}

	public function get() {
		return wp_parse_args( (array) get_option( self::OPTION_KEY, array() ), self::defaults() );
	}

	public function register_page() {
		add_submenu_page(
			'woocommerce',
			__( 'Payment highlighter', 'payment-method-highlighter' ),
			__( 'Payment highlighter', 'payment-method-highlighter' ),
			'manage_woocommerce',
			'payment-method-highlighter',
			array( $this, 'render_page' )
		);
	}

	public function register_settings() {
		register_setting(
			'pmh_settings',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	public function sanitize( $input ) {
		$defaults = self::defaults();
		$input    = is_array( $input ) ? $input : array();
		$gateways = $this->payment_gateways();
		$gateway  = isset( $input['gateway_id'] ) ? sanitize_key( wp_unslash( $input['gateway_id'] ) ) : '';
		$color    = isset( $input['accent_color'] ) ? sanitize_hex_color( wp_unslash( $input['accent_color'] ) ) : '';

		return array(
			'enabled'      => isset( $input['enabled'] ) ? 'yes' : 'no',
			'gateway_id'   => array_key_exists( $gateway, $gateways ) ? $gateway : '',
			'badge'        => isset( $input['badge'] ) ? sanitize_text_field( wp_unslash( $input['badge'] ) ) : $defaults['badge'],
			'message'      => isset( $input['message'] ) ? sanitize_textarea_field( wp_unslash( $input['message'] ) ) : $defaults['message'],
			'accent_color' => $color ? $color : $defaults['accent_color'],
			'style'        => isset( $input['style'] ) && in_array( $input['style'], array( 'soft', 'solid' ), true ) ? $input['style'] : $defaults['style'],
			'preselect'    => isset( $input['preselect'] ) ? 'yes' : 'no',
		);
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$options  = $this->get();
		$gateways = $this->payment_gateways();
		?>
		<div class="wrap pmh-admin">
			<section class="pmh-admin__hero">
				<div class="pmh-admin__eyebrow"><span aria-hidden="true">✦</span> <?php esc_html_e( 'Checkout optimization', 'payment-method-highlighter' ); ?></div>
				<h1><?php esc_html_e( 'Payment Method Highlighter', 'payment-method-highlighter' ); ?></h1>
				<p><?php esc_html_e( 'Guide customers towards the payment method you want to promote — without changing how payments are processed.', 'payment-method-highlighter' ); ?></p>
			</section>

			<?php if ( empty( $gateways ) ) : ?>
				<div class="notice notice-warning inline"><p><?php esc_html_e( 'No payment methods are currently configured. Enable at least one gateway in WooCommerce → Settings → Payments, then return here.', 'payment-method-highlighter' ); ?></p></div>
			<?php endif; ?>

			<form action="options.php" method="post" class="pmh-admin__form">
				<?php settings_fields( 'pmh_settings' ); ?>
				<div class="pmh-admin__layout">
					<div class="pmh-admin__card pmh-admin__card--main">
						<div class="pmh-admin__card-heading">
							<div>
								<h2><?php esc_html_e( 'Featured method', 'payment-method-highlighter' ); ?></h2>
								<p><?php esc_html_e( 'Choose a live WooCommerce payment gateway to highlight at checkout.', 'payment-method-highlighter' ); ?></p>
							</div>
							<label class="pmh-switch">
								<input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[enabled]" value="yes" <?php checked( $options['enabled'], 'yes' ); ?> />
								<span class="pmh-switch__track" aria-hidden="true"></span>
								<span class="screen-reader-text"><?php esc_html_e( 'Enable payment method highlighter', 'payment-method-highlighter' ); ?></span>
							</label>
						</div>

						<div class="pmh-field">
							<label for="pmh-gateway-id"><?php esc_html_e( 'Payment method', 'payment-method-highlighter' ); ?></label>
							<select id="pmh-gateway-id" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[gateway_id]" <?php disabled( empty( $gateways ) ); ?>>
								<option value=""><?php esc_html_e( 'Select a payment method…', 'payment-method-highlighter' ); ?></option>
								<?php foreach ( $gateways as $id => $title ) : ?>
									<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $options['gateway_id'], $id ); ?>><?php echo esc_html( $title ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Only enabled gateways are shown. Availability rules set by the gateway still apply.', 'payment-method-highlighter' ); ?></p>
						</div>

						<div class="pmh-grid">
							<div class="pmh-field">
								<label for="pmh-badge"><?php esc_html_e( 'Badge label', 'payment-method-highlighter' ); ?></label>
								<input id="pmh-badge" type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[badge]" value="<?php echo esc_attr( $options['badge'] ); ?>" maxlength="50" placeholder="<?php esc_attr_e( 'Recommended', 'payment-method-highlighter' ); ?>" />
							</div>
							<div class="pmh-field">
								<label for="pmh-color"><?php esc_html_e( 'Accent colour', 'payment-method-highlighter' ); ?></label>
								<div class="pmh-color-control"><input id="pmh-color" type="color" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[accent_color]" value="<?php echo esc_attr( $options['accent_color'] ); ?>" /><code><?php echo esc_html( $options['accent_color'] ); ?></code></div>
							</div>
						</div>

						<div class="pmh-field">
							<label for="pmh-message"><?php esc_html_e( 'Supporting message', 'payment-method-highlighter' ); ?></label>
							<textarea id="pmh-message" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[message]" rows="3" maxlength="180" placeholder="<?php esc_attr_e( 'A fast, secure choice for your order.', 'payment-method-highlighter' ); ?>"><?php echo esc_textarea( $options['message'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Keep it short and helpful. It appears below the payment method title.', 'payment-method-highlighter' ); ?></p>
						</div>
					</div>

					<aside class="pmh-admin__card pmh-admin__card--side">
						<h2><?php esc_html_e( 'Display options', 'payment-method-highlighter' ); ?></h2>
						<div class="pmh-field">
							<span class="pmh-label"><?php esc_html_e( 'Highlight style', 'payment-method-highlighter' ); ?></span>
							<div class="pmh-choice-group">
								<label><input type="radio" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[style]" value="soft" <?php checked( $options['style'], 'soft' ); ?> /> <span><strong><?php esc_html_e( 'Soft', 'payment-method-highlighter' ); ?></strong><small><?php esc_html_e( 'Light accent surface', 'payment-method-highlighter' ); ?></small></span></label>
								<label><input type="radio" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[style]" value="solid" <?php checked( $options['style'], 'solid' ); ?> /> <span><strong><?php esc_html_e( 'Solid', 'payment-method-highlighter' ); ?></strong><small><?php esc_html_e( 'Stronger accent border', 'payment-method-highlighter' ); ?></small></span></label>
							</div>
						</div>
						<label class="pmh-check-row">
							<input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[preselect]" value="yes" <?php checked( $options['preselect'], 'yes' ); ?> />
							<span><strong><?php esc_html_e( 'Preselect this method', 'payment-method-highlighter' ); ?></strong><small><?php esc_html_e( 'Use it as the default when it is available for the cart and customer.', 'payment-method-highlighter' ); ?></small></span>
						</label>
					</aside>
				</div>

				<div class="pmh-admin__footer">
					<p><span aria-hidden="true">✓</span> <?php esc_html_e( 'Works with the classic checkout and Checkout Blocks.', 'payment-method-highlighter' ); ?></p>
					<?php submit_button( __( 'Save changes', 'payment-method-highlighter' ), 'primary', 'submit', false ); ?>
				</div>
			</form>
		</div>
		<?php
	}

	private function payment_gateways() {
		if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
			return array();
		}

		$gateways = array();
		foreach ( WC()->payment_gateways()->payment_gateways() as $gateway_id => $gateway ) {
			if ( 'yes' === $gateway->enabled ) {
				$gateways[ $gateway_id ] = wp_strip_all_tags( $gateway->get_title() );
			}
		}

		return $gateways;
	}
}
