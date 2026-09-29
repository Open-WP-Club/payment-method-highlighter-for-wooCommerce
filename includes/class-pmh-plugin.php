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
		add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_settings_page' ) );
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'payment-method-highlighter-for-woocommerce', false, dirname( plugin_basename( PMH_FILE ) ) . '/languages' );
	}

	public function woocommerce_notice() {
		if ( class_exists( 'WooCommerce' ) || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		echo '<div class="notice notice-error"><p>';
		echo esc_html__( 'Payment Method Highlighter requires WooCommerce to be active.', 'payment-method-highlighter-for-woocommerce' );
		echo '</p></div>';
	}

	public function add_settings_link( $links ) {
		$url = admin_url( 'admin.php?page=wc-settings&tab=payment_highlighter' );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'payment-method-highlighter-for-woocommerce' ) . '</a>' );

		return $links;
	}

	/**
	 * Register the plugin's tab in WooCommerce → Settings.
	 *
	 * @param array $pages WooCommerce settings pages.
	 * @return array
	 */
	public function add_settings_page( $pages ) {
		if ( ! class_exists( 'WC_Settings_Page' ) ) {
			return $pages;
		}

		require_once PMH_PATH . 'includes/class-pmh-settings-page.php';
		$pages[] = new Settings_Page( $this->settings );

		return $pages;
	}

}
