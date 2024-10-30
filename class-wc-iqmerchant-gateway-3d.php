<?php
/**
 * Plugin Name: iQMerchant Payment Gateway Extension with 3D Secure 2.0
 * Description: Take credit card payments on your store via WooCommerce iQ merchant Payment Gateway with 3D secure 2.0.
 * Author: Shawn Fernandes
 * Author URI: https://iqmerchant.com
 * Version: 1.0.2
 *
 * @package   iQmerchant-Payment-Gateway
 *
 * @wordpress-plugin
 */

 /**
  * IQ Merchant Payment gateway
  * Copyright (C) 2020-2021 iQ Merchant LLC - All Rights Reserved
  * Unauthorized copying of this file, via any medium is strictly prohibited
  * Proprietary and confidential
  * Written by Shawn Fernandes <shawn.f@iqmerchant.com>, September 2021

  * This gateway supports credit card payments with 3D secure.
  */



function iqmerchant_add_gateway_class_3d( $gateways ) {
	$gateways[] = 'WC_IQmerchant_Gateway_3D';
	return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'iqmerchant_add_gateway_class_3d' );



add_action( 'plugins_loaded', 'iqmerchant_init_gateway_class_3d' );
function iqmerchant_init_gateway_class_3d() {

	class WC_IQmerchant_Gateway_3D extends WC_Payment_Gateway {

		public function __construct() {

			$this->id                 = 'iqmerchant_3d'; // payment gateway plugin ID
			$this->icon               = ''; // URL of the icon that will be displayed on checkout page near your gateway name
			$this->has_fields         = true; // for a custom credit card form
			$this->method_title       = 'iQMerchant Gateway 3D';
			$this->method_description = ' iQ Merchant payment gateway with 3D Secure 2.0'; // will be displayed on the options page

			  $this->supports = array(
				  'products',
			  );

			  $this->init_form_fields();

			  // Load the settings.
			  $this->init_settings();
			  $this->title       = $this->get_option( 'title' );
			  $this->description = $this->get_option( 'description' );
			  $this->enabled     = $this->get_option( 'enabled' );
			  $this->testmode    = $this->get_option( 'testmode' );
			  $this->private_key = ( 'yes' == $this->testmode ) ? $this->get_option( 'test_private_key' ) : $this->get_option( 'private_key' );
			  add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			  
		}

		public function init_form_fields() {

			$this->form_fields = array(
				'enabled'          => array(
					'title'       => 'Enable/Disable',
					'label'       => 'Enable iQMerchant Gateway',
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no',
				),
				'title'            => array(
					'title'       => 'Title',
					'type'        => 'text',
					'description' => 'This controls the title which the user sees during checkout.',
					'default'     => 'Credit Card payment with 3D',
					'desc_tip'    => true,
				),
				'description'      => array(
					'title'       => 'Description',
					'type'        => 'textarea',
					'description' => 'This controls the description which the user sees during checkout.',
					'default'     => 'Pay with your credit card via our super-cool payment gateway.',
				),
				'testmode'         => array(
					'title'       => 'Test mode',
					'label'       => 'Enable Test Mode',
					'type'        => 'checkbox',
					'description' => 'Place the payment gateway in test mode using test API keys.',
					'default'     => 'yes',
					'desc_tip'    => true,
				),
				'test_private_key' => array(
					'title'   => 'Test Private Key',
					'type'    => 'password',
					'default' => '2F822Rw39fx762MaV7Yy86jXGTC7sCDy',
				),
				'private_key'      => array(
					'title' => 'Live Private Key',
					'type'  => 'password',
				),
			);

		}

		public function process_payment( $order_id ) {

			$order_object = wc_get_order( $order_id );

			global $woocommerce,$response;
			try {

				   wc_add_notice( 'Payment Success ' );   // Green check Authorization display

					$order_object->reduce_order_stock();

					// some notes to customer (replace true with false to make it private)
					$order_object->add_order_note( 'Your test order is paid via woocommerce! Thank you for using the iQ Merchant Payment Gateway!', true );

					// Store charge ID
				$order_object->update_meta_data( '_iqmerchant_charge_id', '12345' );
				$order_object->update_meta_data( '_iqmerchant_cc_last4', '4111111111111111' );

				$order_object->set_transaction_id( '12345' );

					// Empty cart
					$woocommerce->cart->empty_cart();

					// Redirect to the thank you page
					return array(
						'result'   => 'success',
						'redirect' => $this->get_return_url( $order_object ),
					);

			} catch ( Exception $e ) {

				do_action( 'wc_gateway_authnet_process_payment_error', $e, $order_object );

				$order_object->update_status( 'failed' );

				return array(
					'result'   => 'fail',
					'redirect' => '',
				);
			}

		}

	}
}
