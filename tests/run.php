<?php
/**
 * Dependency-free unit test runner.
 *
 * Run with: php tests/run.php
 *
 * @package PaymentMethodHighlighter
 */

require_once __DIR__ . '/bootstrap.php';

use PMH\Order_Admin;
use PMH\Settings;

$tests = array(
	'default settings enable the order-list badge and preserve optional badges as opt-in' => function() {
		$defaults = Settings::defaults();
		pmh_assert_same( 'yes', $defaults['show_in_order_list'], 'The order-list badge should default to enabled.' );
		pmh_assert_same( 'yes', $defaults['show_in_order_details'], 'The detailed order badge should default to enabled.' );
		pmh_assert_same( 'no', $defaults['show_in_order_header'], 'The header badge should remain opt-in.' );
		pmh_assert_same( 'no', $defaults['show_missing_method'], 'The missing-method badge should remain opt-in.' );
	},
	'gateway colours have stable built-in defaults' => function() {
		pmh_assert_same( '#0f766e', Settings::default_color( 'bacs' ), 'BACS should use its bank-transfer colour.' );
		pmh_assert_same( '#15803d', Settings::default_color( 'cod' ), 'Cash on delivery should use its built-in colour.' );
		pmh_assert_same( Settings::default_color( 'custom_gateway' ), Settings::default_color( 'custom_gateway' ), 'Custom gateway colours should be deterministic.' );
	},
	'sanitizing settings preserves each display toggle and validates gateway colours' => function() {
		$settings = new Settings();
		$sanitized = $settings->sanitize(
			array(
				'enabled'               => 'yes',
				'show_in_order_list'    => 'yes',
				'show_in_order_details' => 'yes',
				'show_missing_method'   => 'yes',
				'methods'               => array(
					'bacs' => array( 'enabled' => 'yes', 'accent_color' => '#123456' ),
					'cod'  => array( 'accent_color' => 'invalid' ),
				),
			)
		);

		pmh_assert_same( 'no', $sanitized['show_in_order_header'], 'An unchecked header toggle must be disabled.' );
		pmh_assert_same( 'yes', $sanitized['show_missing_method'], 'The missing-method toggle must be saved.' );
		pmh_assert_same( '#123456', $sanitized['methods']['bacs']['accent_color'], 'A valid custom colour must be saved.' );
		pmh_assert_same( '#15803d', $sanitized['methods']['cod']['accent_color'], 'An invalid colour must fall back to the default.' );
	},
	'detailed badge renders the configured payment method' => function() {
		$GLOBALS['pmh_test_options'][ Settings::OPTION_KEY ] = array(
			'schema_version' => 3,
			'enabled' => 'yes',
			'show_in_order_details' => 'yes',
			'methods' => array( 'bacs' => array( 'enabled' => 'yes', 'accent_color' => '#0f766e' ) ),
		);
		$admin = new Order_Admin( new Settings() );
		ob_start();
		$admin->render_payment_method( new WC_Order( 1, 'bacs', 'Direct bank transfer' ) );
		$output = ob_get_clean();

		pmh_assert_true( false !== strpos( $output, 'Payment method' ), 'The detailed badge label should render.' );
		pmh_assert_true( false !== strpos( $output, 'Direct bank transfer' ), 'The detailed badge should render the gateway title.' );
	},
	'detailed badge can be disabled independently from the other displays' => function() {
		$GLOBALS['pmh_test_options'][ Settings::OPTION_KEY ] = array(
			'schema_version' => 3,
			'enabled' => 'yes',
			'show_in_order_details' => 'no',
			'methods' => array( 'bacs' => array( 'enabled' => 'yes' ) ),
		);
		$admin = new Order_Admin( new Settings() );
		ob_start();
		$admin->render_payment_method( new WC_Order( 2, 'bacs', 'Direct bank transfer' ) );
		pmh_assert_same( '', ob_get_clean(), 'The detailed badge must not render when its toggle is disabled.' );
	},
	'header badge renders only when its independent toggle is enabled' => function() {
		$GLOBALS['pmh_test_options'][ Settings::OPTION_KEY ] = array(
			'schema_version' => 3,
			'enabled' => 'yes',
			'show_in_order_header' => 'yes',
			'methods' => array( 'bacs' => array( 'enabled' => 'yes', 'accent_color' => '#0f766e' ) ),
		);
		$admin = new Order_Admin( new Settings() );
		ob_start();
		$admin->render_header_badge( new WC_Order( 4, 'bacs', 'Direct bank transfer' ) );
		$output = ob_get_clean();
		pmh_assert_true( false !== strpos( $output, 'pmh-order-header-badge' ), 'The header badge should render when enabled.' );

		$GLOBALS['pmh_test_options'][ Settings::OPTION_KEY ]['show_in_order_header'] = 'no';
		ob_start();
		$admin->render_header_badge( new WC_Order( 5, 'bacs', 'Direct bank transfer' ) );
		pmh_assert_same( '', ob_get_clean(), 'The header badge must not render when disabled.' );
	},
	'missing payment methods stay hidden unless their dedicated toggle is enabled' => function() {
		$GLOBALS['pmh_test_options'][ Settings::OPTION_KEY ] = array(
			'schema_version' => 3,
			'enabled' => 'yes',
			'show_in_order_list' => 'yes',
			'show_missing_method' => 'no',
			'methods' => array(),
		);
		$admin = new Order_Admin( new Settings() );
		ob_start();
		$admin->render_order_list_column( 'pmh_payment_method', new WC_Order( 6 ) );
		pmh_assert_same( '', ob_get_clean(), 'Orders without a payment method must stay hidden by default.' );
	},
	'order-list column is inserted and renders a neutral badge for a missing method when enabled' => function() {
		$GLOBALS['pmh_test_options'][ Settings::OPTION_KEY ] = array(
			'schema_version' => 3,
			'enabled' => 'yes',
			'show_in_order_list' => 'yes',
			'show_missing_method' => 'yes',
			'methods' => array(),
		);
		$admin = new Order_Admin( new Settings() );
		$columns = $admin->add_order_list_column( array( 'order_number' => 'Order', 'order_status' => 'Status' ) );
		pmh_assert_true( isset( $columns['pmh_payment_method'] ), 'The payment-method column should be added after the order number.' );

		ob_start();
		$admin->render_order_list_column( 'pmh_payment_method', new WC_Order( 3 ) );
		$output = ob_get_clean();
		pmh_assert_true( false !== strpos( $output, 'No payment method' ), 'A neutral missing-method badge should render when enabled.' );
	},
);

$failures = array();
foreach ( $tests as $name => $test ) {
	try {
		$test();
		echo "PASS: {$name}\n";
	} catch ( Throwable $exception ) {
		$failures[] = $name . ': ' . $exception->getMessage();
		echo "FAIL: {$name}\n";
	}
}

if ( $failures ) {
	echo "\nFailures:\n" . implode( "\n", $failures ) . "\n";
	exit( 1 );
}

echo "\n" . count( $tests ) . " tests passed.\n";
