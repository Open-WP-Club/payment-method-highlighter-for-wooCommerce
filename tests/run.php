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
		pmh_assert_same( 'yes', $defaults['show_shipping_in_order_list'], 'The shipping order-list badge should default to enabled.' );
		pmh_assert_same( 'yes', $defaults['show_shipping_in_order_details'], 'The detailed shipping order badge should default to enabled.' );
		pmh_assert_same( 'no', $defaults['show_shipping_in_order_header'], 'The shipping header badge should remain opt-in.' );
		pmh_assert_same( 'no', $defaults['show_missing_shipping_method'], 'The missing-shipping-method badge should remain opt-in.' );
	},
	'gateway colours have stable built-in defaults' => function() {
		pmh_assert_same( '#0f766e', Settings::default_color( 'bacs' ), 'BACS should use its bank-transfer colour.' );
		pmh_assert_same( '#15803d', Settings::default_color( 'cod' ), 'Cash on delivery should use its built-in colour.' );
		pmh_assert_same( Settings::default_color( 'custom_gateway' ), Settings::default_color( 'custom_gateway' ), 'Custom gateway colours should be deterministic.' );
	},
	'shipping method colours have stable built-in defaults' => function() {
		pmh_assert_same( '#2563eb', Settings::default_shipping_color( 'flat_rate' ), 'Flat rate should use its built-in colour.' );
		pmh_assert_same( '#059669', Settings::default_shipping_color( 'free_shipping' ), 'Free shipping should use its built-in colour.' );
		pmh_assert_same( Settings::default_shipping_color( 'custom_method' ), Settings::default_shipping_color( 'custom_method' ), 'Custom shipping method colours should be deterministic.' );
	},
	'default settings ship with built-in Bulgarian and English office/address keyword colours' => function() {
		$defaults = Settings::defaults();
		$keywords = wp_list_pluck( $defaults['shipping_keywords'], 'keyword' );
		pmh_assert_true( in_array( 'офис', $keywords, true ), 'The Bulgarian "офис" keyword should be a built-in default.' );
		pmh_assert_true( in_array( 'office', $keywords, true ), 'The English "office" keyword should be a built-in default.' );
		pmh_assert_true( in_array( 'адрес', $keywords, true ), 'The Bulgarian "адрес" keyword should be a built-in default.' );
		pmh_assert_true( in_array( 'address', $keywords, true ), 'The English "address" keyword should be a built-in default.' );
	},
	'default office and address keywords share the same colour across languages' => function() {
		$defaults = Settings::defaults();
		$by_keyword = array();
		foreach ( $defaults['shipping_keywords'] as $row ) {
			$by_keyword[ $row['keyword'] ] = $row['accent_color'];
		}
		pmh_assert_same( $by_keyword['офис'], $by_keyword['office'], 'Office-type keywords should share a colour regardless of language.' );
		pmh_assert_same( $by_keyword['адрес'], $by_keyword['address'], 'Address-type keywords should share a colour regardless of language.' );
	},
	'a keyword is matched as a substring anywhere in the shipping title, in any language' => function() {
		$GLOBALS['pmh_test_options'][ Settings::OPTION_KEY ] = array(
			'schema_version' => 3,
			'shipping_enabled' => 'yes',
			'show_shipping_in_order_details' => 'yes',
			'shipping_methods' => array( 'flat_rate' => array( 'enabled' => 'yes', 'accent_color' => '#2563eb' ) ),
			'shipping_keywords' => Settings::shipping_keyword_defaults(),
		);
		$admin = new Order_Admin( new Settings() );
		$order = new WC_Order(
			13,
			'',
			'',
			array(
				new WC_Order_Item_Shipping( 'flat_rate', 'Speedy – To Office' ),
				new WC_Order_Item_Shipping( 'flat_rate', 'Спиди – До адрес на клиента' ),
			)
		);

		ob_start();
		$admin->render_shipping_method( $order );
		$output = ob_get_clean();

		pmh_assert_true( false !== strpos( $output, '--pmh-accent-colour: #7c3aed;">Speedy' ), 'The English "office" particle should match case-insensitively and colour the badge.' );
		pmh_assert_true( false !== strpos( $output, '--pmh-accent-colour: #0891b2;">Спиди' ), 'The Bulgarian "адрес" particle should match anywhere within a longer title.' );
	},
	'a keyword match overrides the shipping method colour for that badge only' => function() {
		$GLOBALS['pmh_test_options'][ Settings::OPTION_KEY ] = array(
			'schema_version' => 3,
			'shipping_enabled' => 'yes',
			'show_shipping_in_order_details' => 'yes',
			'shipping_methods' => array( 'flat_rate' => array( 'enabled' => 'yes', 'accent_color' => '#2563eb' ) ),
			'shipping_keywords' => array(
				array( 'keyword' => 'До офис', 'enabled' => 'yes', 'accent_color' => '#7c3aed' ),
			),
		);
		$admin = new Order_Admin( new Settings() );
		$order = new WC_Order(
			11,
			'',
			'',
			array(
				new WC_Order_Item_Shipping( 'flat_rate', 'Speedy - До офис' ),
				new WC_Order_Item_Shipping( 'flat_rate', 'Speedy - До адрес' ),
			)
		);

		ob_start();
		$admin->render_shipping_method( $order );
		$output = ob_get_clean();

		pmh_assert_true( false !== strpos( $output, '--pmh-accent-colour: #7c3aed;">Speedy - До офис' ), 'The keyword colour should override the method colour for the matching badge.' );
		pmh_assert_true( false !== strpos( $output, '--pmh-accent-colour: #2563eb;">Speedy - До адрес' ), 'A non-matching badge should keep the shipping method colour.' );
	},
	'disabled keyword rows are ignored' => function() {
		$GLOBALS['pmh_test_options'][ Settings::OPTION_KEY ] = array(
			'schema_version' => 3,
			'shipping_enabled' => 'yes',
			'show_shipping_in_order_details' => 'yes',
			'shipping_methods' => array( 'flat_rate' => array( 'enabled' => 'yes', 'accent_color' => '#2563eb' ) ),
			'shipping_keywords' => array(
				array( 'keyword' => 'До офис', 'enabled' => 'no', 'accent_color' => '#7c3aed' ),
			),
		);
		$admin = new Order_Admin( new Settings() );
		$order = new WC_Order( 12, '', '', array( new WC_Order_Item_Shipping( 'flat_rate', 'Speedy - До офис' ) ) );

		ob_start();
		$admin->render_shipping_method( $order );
		$output = ob_get_clean();

		pmh_assert_true( false !== strpos( $output, '--pmh-accent-colour: #2563eb;">Speedy - До офис' ), 'A disabled keyword row must not override the shipping method colour.' );
	},
	'sanitizing keyword rows drops blank rows and validates colours' => function() {
		$settings = new Settings();
		$sanitized = $settings->sanitize(
			array(
				'shipping_keywords' => array(
					array( 'keyword' => '  До офис  ', 'enabled' => 'yes', 'accent_color' => '#123456' ),
					array( 'keyword' => '', 'enabled' => 'yes', 'accent_color' => '#654321' ),
					array( 'keyword' => 'Invalid colour', 'enabled' => 'yes', 'accent_color' => 'not-a-colour' ),
				),
			)
		);

		pmh_assert_same( 2, count( $sanitized['shipping_keywords'] ), 'A blank keyword row must be dropped.' );
		pmh_assert_same( 'До офис', $sanitized['shipping_keywords'][0]['keyword'], 'The keyword must be trimmed.' );
		pmh_assert_same( '#123456', $sanitized['shipping_keywords'][0]['accent_color'], 'A valid custom colour must be saved.' );
		pmh_assert_same( Settings::default_keyword_color( 'Invalid colour' ), $sanitized['shipping_keywords'][1]['accent_color'], 'An invalid colour must fall back to the deterministic default.' );
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
	'detailed shipping badge renders every shipping line item on the order' => function() {
		$GLOBALS['pmh_test_options'][ Settings::OPTION_KEY ] = array(
			'schema_version' => 3,
			'shipping_enabled' => 'yes',
			'show_shipping_in_order_details' => 'yes',
			'shipping_methods' => array( 'flat_rate' => array( 'enabled' => 'yes', 'accent_color' => '#2563eb' ) ),
		);
		$admin = new Order_Admin( new Settings() );
		$order = new WC_Order( 7, '', '', array( new WC_Order_Item_Shipping( 'flat_rate', 'Flat rate' ) ) );
		ob_start();
		$admin->render_shipping_method( $order );
		$output = ob_get_clean();

		pmh_assert_true( false !== strpos( $output, 'Shipping method' ), 'The detailed shipping badge label should render.' );
		pmh_assert_true( false !== strpos( $output, 'Flat rate' ), 'The detailed shipping badge should render the method title.' );
	},
	'detailed shipping badge can be disabled independently from the other displays' => function() {
		$GLOBALS['pmh_test_options'][ Settings::OPTION_KEY ] = array(
			'schema_version' => 3,
			'shipping_enabled' => 'yes',
			'show_shipping_in_order_details' => 'no',
			'shipping_methods' => array( 'flat_rate' => array( 'enabled' => 'yes' ) ),
		);
		$admin = new Order_Admin( new Settings() );
		$order = new WC_Order( 8, '', '', array( new WC_Order_Item_Shipping( 'flat_rate', 'Flat rate' ) ) );
		ob_start();
		$admin->render_shipping_method( $order );
		pmh_assert_same( '', ob_get_clean(), 'The detailed shipping badge must not render when its toggle is disabled.' );
	},
	'missing shipping methods stay hidden unless their dedicated toggle is enabled' => function() {
		$GLOBALS['pmh_test_options'][ Settings::OPTION_KEY ] = array(
			'schema_version' => 3,
			'shipping_enabled' => 'yes',
			'show_shipping_in_order_list' => 'yes',
			'show_missing_shipping_method' => 'no',
			'shipping_methods' => array(),
		);
		$admin = new Order_Admin( new Settings() );
		ob_start();
		$admin->render_order_list_column( 'pmh_shipping_method', new WC_Order( 9 ) );
		pmh_assert_same( '', ob_get_clean(), 'Orders without a shipping method must stay hidden by default.' );
	},
	'shipping order-list column is inserted and renders a neutral badge for a missing method when enabled' => function() {
		$GLOBALS['pmh_test_options'][ Settings::OPTION_KEY ] = array(
			'schema_version' => 3,
			'shipping_enabled' => 'yes',
			'show_shipping_in_order_list' => 'yes',
			'show_missing_shipping_method' => 'yes',
			'shipping_methods' => array(),
		);
		$admin = new Order_Admin( new Settings() );
		$columns = $admin->add_order_list_column( array( 'order_number' => 'Order', 'order_status' => 'Status' ) );
		pmh_assert_true( isset( $columns['pmh_shipping_method'] ), 'The shipping-method column should be added after the order number.' );

		ob_start();
		$admin->render_order_list_column( 'pmh_shipping_method', new WC_Order( 10 ) );
		$output = ob_get_clean();
		pmh_assert_true( false !== strpos( $output, 'No shipping method' ), 'A neutral missing-shipping-method badge should render when enabled.' );
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
