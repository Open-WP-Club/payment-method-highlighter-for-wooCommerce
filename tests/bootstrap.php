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
function sanitize_text_field( $value ) { return trim( preg_replace( '/[\r\n\t ]+/', ' ', (string) $value ) ); }
function wp_unslash( $value ) { return $value; }
function wp_strip_all_tags( $value ) { return strip_tags( $value ); }
function wp_parse_args( $args, $defaults ) { return array_merge( $defaults, (array) $args ); }
function wp_list_pluck( $list, $field ) { return array_map( function( $item ) use ( $field ) { return is_array( $item ) ? $item[ $field ] : $item->$field; }, $list ); }

class WC_Order {
	private $id;
	private $method;
	private $title;
	private $shipping_items;

	public function __construct( $id, $method = '', $title = '', $shipping_items = array() ) {
		$this->id             = $id;
		$this->method         = $method;
		$this->title          = $title;
		$this->shipping_items = $shipping_items;
	}

	public function get_id() { return $this->id; }
	public function get_payment_method() { return $this->method; }
	public function get_payment_method_title() { return $this->title; }
	public function get_items( $type = 'line_item' ) { return 'shipping' === $type ? $this->shipping_items : array(); }
}

class WC_Order_Item_Shipping {
	private $method_id;
	private $name;

	public function __construct( $method_id, $name ) {
		$this->method_id = $method_id;
		$this->name      = $name;
	}

	public function get_method_id() { return $this->method_id; }
	public function get_name() { return $this->name; }
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

class PMH_Test_Shipping_Method {
	private $title;

	public function __construct( $title ) { $this->title = $title; }
	public function get_method_title() { return $this->title; }
}

class PMH_Test_Shipping_Manager {
	public function get_shipping_methods() {
		return array(
			'flat_rate'     => new PMH_Test_Shipping_Method( 'Flat rate' ),
			'free_shipping' => new PMH_Test_Shipping_Method( 'Free shipping' ),
		);
	}
}

class PMH_Test_WooCommerce {
	public function payment_gateways() { return new PMH_Test_Gateway_Manager(); }
	public function shipping() { return new PMH_Test_Shipping_Manager(); }
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
