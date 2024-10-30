<?php
class WooCommerce_Honey extends WC_Payment_Gateway {

	function __construct() {

		$this->id = "WooCommerce_Honey";
		
		$this->method_title = __( "Honey", 'honey-woocommerce-payments' );

		$this->method_description = __( "Honey Payment Gateway for WooCommerce", 'honey-woocommerce-payments' );

		$this->title = __( "Honey", 'honey-woocommerce-payments' );

		$this->icon = plugin_dir_url( __FILE__ )."/images/honeylogo.png";

		$this->has_fields = true;

		$this->init_form_fields();

		$this->init_settings();
		
		foreach ( $this->settings as $setting_key => $value ) {
			$this->$setting_key = $value;
		}
		
		if ( is_admin() ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}		
	}
	  /**
	  * Screen button Field
	  */
	  public function generate_screen_button_html( $key, $value ) { 
	  ?>

<div class="updated notice is-dismissible">
		<p>NB: Set your store 'Currency' to 'Honey (Ⓗ)' in 

		         <a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=general' ); ?>"><?php _e( 'Currency options', 'honey-woocommerce-payments' ); ?>.</a></p>

</div>
	   <?php
	  }

	public function init_form_fields() {
		$this->form_fields = array(
			'screen_button' => array(
              'id'    => 'screen_button',
              'type'  => 'screen_button',
              'title' => __( 'Other Settings', 'honey-woocommerce-payments' ),
          	),
			'enabled' => array(
				'title'		=> __( 'Enable / Disable', 'honey-woocommerce-payments' ),
				'label'		=> __( 'Enable this payment gateway', 'honey-woocommerce-payments' ),
				'type'		=> 'checkbox',
				'default'	=> 'no',
			),
			'trans_key' => array(
				'title'		=> __( 'Coinhive Secret Key', 'honey-woocommerce-payments' ),
				'type'		=> 'password',
				'desc_tip'	=> __( 'This is the Secret Key provided by Coinhive.com', 'honey-woocommerce-payments' ),
			),
			'title' => array(
				'title'		=> __( 'Title', 'honey-woocommerce-payments' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'Payment title the customer will see during the checkout process.', 'honey-woocommerce-payments' ),
				'default'	=> __( 'Honey Payment', 'honey-woocommerce-payments' ),
			),
			'description' => array(
				'title'		=> __( 'Description', 'honey-woocommerce-payments' ),
				'type'		=> 'textarea',
				'desc_tip'	=> __( 'Payment description the customer will see during the checkout process.', 'hhoney-woocommerce-payments' ),
				'default'	=> __( 'Pay securely using your Honey.', 'honey-woocommerce-payments' ),
				'css'		=> 'max-width:350px;'
			)

		);		
	}
	
	public function process_payment( $order_id ) {
		global $woocommerce;
		
		$customer_order = new WC_Order( $order_id );
		
		$environment_url = 'https://api.coinhive.com/user/withdraw';
				
		$username = wp_get_current_user()->user_login;

		$payload = array(
			"secret"           	=> $this->trans_key,
			"name"              => $username,
			"amount"            => $customer_order->order_total			
		);

		$response = wp_remote_post( $environment_url, array(
			'method'    => 'POST',
			'body'      => http_build_query( $payload ),
			'timeout'   => 90,
			'sslverify' => false,
		) );

		if ( is_wp_error( $response ) ) 
			throw new Exception( __( 'We are currently experiencing problems trying to connect to this payment gateway. Sorry for the inconvenience.', 'honey-woocommerce-payments' ) );

		if ( empty( $response['body'] ) )
			throw new Exception( __( 'Honeys\' Response was empty.', 'honey-woocommerce-payments' ) );
			
		$response_body = wp_remote_retrieve_body( $response );

		$resp=json_decode($response_body);

		$r['response_code']             = $resp->success;
		$r['response_reason_text']      = $resp->error;

		if ( ( $r['response_code'] == true ) ) {

			$customer_order->add_order_note( __( 'Honey payment completed.', 'honey-woocommerce-payments' ) );
												 
			$customer_order->payment_complete();

			// Empty cart
			$woocommerce->cart->empty_cart(true);
			$woocommerce->session->set('cart', array());

			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $customer_order ),
			);
		} else {
			if($r['response_reason_text'] == 'missing_input') {
				wc_add_notice( __('You need to be logged in', 'honey-woocommerce-payments' ), 'error' );
				$customer_order->add_order_note( __('You need to be logged in', 'honey-woocommerce-payments' ) );
			}else if($r['response_reason_text'] == 'insufficent_funds') {
				wc_add_notice( __('You have insufficent funds', 'honey-woocommerce-payments' ), 'error' );
				$customer_order->add_order_note( __('You have insufficent funds', 'honey-woocommerce-payments' ) );
			}else if($r['response_reason_text'] == 'unknown_user') {
				wc_add_notice( __('User earnings not found', 'honey-woocommerce-payments' ), 'error' );
				$customer_order->add_order_note( __('User earnings not found', 'honey-woocommerce-payments' ) );
			}else{
				wc_add_notice( $r['response_reason_text'], 'error' );
				$customer_order->add_order_note( 'Error: '. $r['response_reason_text'] );
			}
		}

	}
	
	public function validate_fields() {
		return true;
	}

}
