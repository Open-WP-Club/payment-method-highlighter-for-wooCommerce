<?php
/**
 * Remove plugin settings only when WordPress uninstalls the plugin.
 *
 * @package PaymentMethodHighlighter
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'pmh_options' );
