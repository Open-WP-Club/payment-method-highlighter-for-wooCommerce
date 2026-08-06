<?php
/**
 * Minimal WordPress and WooCommerce test doubles for plugin unit tests.
 *
 * @package PaymentMethodHighlighter
 */

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['pmh_test_options'] = array();

function add_action() {}
function add_filter() {}
function esc_attr( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_html_e( $value ) { echo esc_html( $value ); }
function __( $value ) { return $value; }
function get_option( $key, $default = false ) { return isset( $GLOBALS['pmh_test_options'][ $key ] ) ? $GLOBALS['pmh_test_options'][ $key ] : $default; }
function sanitize_hex_color( $color ) { return is_string( $color ) && preg_match( '/^#[a-fA-F0-9]{6}$/', $color ) ? strtolower( $color ) : null; }
function sanitize_key( $key ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) ); }
function wp_unslash( $value ) { return $value; }
function wp_strip_all_tags( $value ) { return strip_tags( $value ); }
function wp_parse_args( $args, $defaults ) { return array_merge( $defaults, (array) $args ); }

class WC_Order {
	private $id;
	private $method;
	private $title;

	public function __construct( $id, $method = '', $title = '' ) {
		$this->id     = $id;
		$this->method = $method;
		$this->title  = $title;
	}

	public function get_id() { return $this->id; }
	public function get_payment_method() { return $this->method; }
	public function get_payment_method_title() { return $this->title; }
}

class PMH_Test_Gateway {
	public $enabled = 'yes';
	private $title;

	public function __construct( $title ) { $this->title = $title; }
	public function get_title() { return $this->title; }
}

class PMH_Test_Gateway_Manager {
	public function payment_gateways() {
		return array(
			'bacs' => new PMH_Test_Gateway( 'Direct bank transfer' ),
			'cod'  => new PMH_Test_Gateway( 'Cash on delivery' ),
		);
	}
}

class PMH_Test_WooCommerce {
	public function payment_gateways() { return new PMH_Test_Gateway_Manager(); }
}

function WC() { return new PMH_Test_WooCommerce(); }

function pmh_assert_true( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function pmh_assert_same( $expected, $actual, $message ) {
	pmh_assert_true( $expected === $actual, $message . ' Expected: ' . var_export( $expected, true ) . '; actual: ' . var_export( $actual, true ) );
}

require_once dirname( __DIR__ ) . '/includes/class-pmh-settings.php';
require_once dirname( __DIR__ ) . '/includes/class-pmh-order-admin.php';
