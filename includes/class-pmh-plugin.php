<?php
/**
 * Plugin bootstrap and WooCommerce integration.
 *
 * @package PaymentMethodHighlighter
 */

namespace PMH;

defined( 'ABSPATH' ) || exit;

final class Plugin {
	/** @var Plugin|null */
	private static $instance = null;

	/** @var Settings */
	private $settings;

	/** @var Assets */
	private $assets;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->settings = new Settings();
		$this->assets   = new Assets( $this->settings );

		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'admin_notices', array( $this, 'woocommerce_notice' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( PMH_FILE ), array( $this, 'add_settings_link' ) );
		add_filter( 'woocommerce_default_gateway', array( $this, 'preferred_gateway' ), 20 );
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'payment-method-highlighter', false, dirname( plugin_basename( PMH_FILE ) ) . '/languages' );
	}

	public function woocommerce_notice() {
		if ( class_exists( 'WooCommerce' ) || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		echo '<div class="notice notice-error"><p>';
		echo esc_html__( 'Payment Method Highlighter requires WooCommerce to be active.', 'payment-method-highlighter' );
		echo '</p></div>';
	}

	public function add_settings_link( $links ) {
		$url = admin_url( 'admin.php?page=payment-method-highlighter' );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'payment-method-highlighter' ) . '</a>' );

		return $links;
	}

	/**
	 * Makes the featured method WooCommerce's default only when the merchant opted in.
	 * The setting is intentionally guarded by availability so country/cart restrictions keep working.
	 */
	public function preferred_gateway( $default ) {
		$options = $this->settings->get();

		if ( 'yes' !== $options['enabled'] || 'yes' !== $options['preselect'] || empty( $options['gateway_id'] ) || ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
			return $default;
		}

		$available = WC()->payment_gateways()->get_available_payment_gateways();

		return isset( $available[ $options['gateway_id'] ] ) ? $options['gateway_id'] : $default;
	}
}
