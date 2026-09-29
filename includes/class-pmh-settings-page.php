<?php
/**
 * WooCommerce Settings tab integration.
 *
 * @package PaymentMethodHighlighter
 */

namespace PMH;

defined( 'ABSPATH' ) || exit;

final class Settings_Page extends \WC_Settings_Page {
	/** @var Settings */
	private $settings;

	public function __construct( Settings $settings ) {
		$this->id       = 'payment_highlighter';
		$this->label    = __( 'Payment highlighter', 'payment-method-highlighter-for-woocommerce' );
		$this->settings = $settings;

		parent::__construct();
	}

	/**
	 * This tab uses a single, structured option rather than WooCommerce's
	 * individual option fields.
	 *
	 * @return array
	 */
	protected function get_settings_for_default_section() {
		return array();
	}

	/**
	 * Output the custom fields inside WooCommerce → Settings.
	 */
	public function output() {
		$this->settings->render_fields();
	}

	/**
	 * Save the structured option from the standard WooCommerce settings form.
	 */
	public function save() {
		if ( isset( $_POST[ Settings::OPTION_KEY ] ) ) {
			$input = wp_unslash( $_POST[ Settings::OPTION_KEY ] );
			update_option( Settings::OPTION_KEY, $this->settings->sanitize( $input ) );
		}

		do_action( 'woocommerce_update_options_' . $this->id );
	}
}
