<?php
/**
 * WooCommerce Product Fees
 *
 * Add the fees at checkout.
 *
 * @class 	WooCommerce_Product_Fees
 * @author 	Caleb Burks
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WooCommerce_Product_Fees {

	/**
	 * Constructor for the main product fees class.
	 *
	 * @access public
	 */
	public function __construct() {
		if ( is_admin() ) {
			// Product Settings
			require_once 'admin/class-wcpf-admin-product-settings.php';
			new WCPF_Admin_Product_Settings();
			// Global Settings
			require_once 'admin/class-wcpf-admin-global-settings.php';
			new WCPF_Admin_Global_Settings();
		}

		// Fee Classes
		require_once( 'fees/class-wcpf-fee.php' );
		require_once( 'fees/class-wcpf-product-fee.php' );
		require_once( 'fees/class-wcpf-variation-fee.php' );

		// Text Domain
		add_action( 'plugins_loaded', array( $this, 'text_domain' ) );

		// Hook in for fees to be added
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_fees' ), 15 );
	}

	/**
	 * Load Text Domain
	 */
	public function text_domain() {
	 	load_plugin_textdomain( 'woocommerce-product-fees', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Add all fees at checkout.
	 *
	 * @access public
	 */
	public function add_fees( $cart ) {
		foreach( $cart->get_cart() as $cart_item => $values ) {
			// Assume there is no fee.
			$fee = false;

			// Data we need from each product in the cart.
			$product_data = array(
				'id'     => $values['product_id'],
				'qty'    => $values['quantity'],
				'price'  => $values['data']->get_price()
			);

			// Check first for a variation specific fee, and use that if it exists.
			if ( 0 !== $values['variation_id'] ) {
				$product_data['variation_id'] = $values['variation_id'];

				// Get variation fee. Will return false if there is no fee.
				$fee = new WCPF_Variation_Fee( $product_data, $cart );
			}

			if ( ! $fee ) {
				// Get product fee. Will return false if there is no fee.
				$fee = new WCPF_Product_Fee( $product_data, $cart );
			}

			if ( $fee ) {
				$fee_data = $fee->return_fee();
				do_action( 'wcpf_before_fee_is_added', $fee_data );

				// Check if taxes need to be added.
				if ( get_option( 'wcpf_fee_tax_class', '' ) !== '' ) {
					$cart->add_fee( $fee_data['name'], $fee_data['amount'], true, get_option( 'wcpf_fee_tax_class' ) );
				} else {
					$cart->add_fee( $fee_data['name'], $fee_data['amount'], false );
				}

				do_action( 'wcpf_after_fee_is_added', $fee_data );
			}
		}
	}

}
