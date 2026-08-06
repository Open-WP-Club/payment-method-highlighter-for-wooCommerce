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

	/** @var Order_Admin */
	private $order_admin;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->settings    = new Settings();
		new Assets();
		$this->order_admin = new Order_Admin( $this->settings );

		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'admin_notices', array( $this, 'woocommerce_notice' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( PMH_FILE ), array( $this, 'add_settings_link' ) );
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

}
