<?php
/*
Plugin Name: Honey WooCommerce Payments
Description: Receive Honey payments on WooCommerce.
Version: 1.0.2
Author: Honey Plugins
Author URI: http://honeyplugins.com
Text Domain: honey-woocommerce-payments
Domain Path: /assets/languages/
License: GPL2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

add_action( 'plugins_loaded', 'hwp_woohoney_init', 0 );
function hwp_woohoney_init() {

	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;

	include_once( 'honey_woocommerce_class.php' );

	add_filter( 'woocommerce_payment_gateways', 'hwp_add_authorizenet_aim_gateway' );
	function hwp_add_authorizenet_aim_gateway( $methods ) {
		$methods[] = 'WooCommerce_Honey';
		return $methods;
	}
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'hwp_WooCommerce_Honey_action_links' );
function hwp_WooCommerce_Honey_action_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=woocommerce_honey' ) . '">' . __( 'Settings', 'honey-woocommerce-payments' ) . '</a>',
	);

	return array_merge( $plugin_links, $links );	
}

function hwp_add_woohoney_scripts() 
{
    wp_enqueue_style( 'woohoney_style', plugins_url( 'css/style.css', __FILE__ ) );
	wp_enqueue_script( 'woohoney_script', plugins_url( 'scripts/custom.js', __FILE__ ) );
}

add_action('woocommerce_review_order_after_payment', 'hwp_add_woohoney_scripts', 1);

add_filter( 'woocommerce_currencies', 'hwp_add_my_currency' );

function hwp_add_my_currency( $currencies ) {
     $currencies['HNY'] = __( 'Honey', 'honey-woocommerce-payments' );
     return $currencies;
}

add_filter('woocommerce_currency_symbol', 'hwp_add_my_currency_symbol', 10, 2);

function hwp_add_my_currency_symbol( $currency_symbol, $currency ) {
     switch( $currency ) {
          case 'HNY': $currency_symbol = 'Ⓗ'; break;
     }
     return $currency_symbol;
}

// Add admin notice
add_action( 'admin_notices', 'hwp_admin_notice_example_notice' );
//Admin Notice on Activation.
function hwp_admin_notice_example_notice(){
    if( get_transient( 'hwp-admin-notice-example' ) ){
		if ( ! class_exists( 'WC_Payment_Gateway' ) ){
		?><div class="error notice is-dismissible">
            <p><?php echo __('You still need to install ', 'honey-woocommerce-payments'); ?> <strong><a href="https://wordpress.org/plugins/woocommerce/"><?php echo __('WooCommerce', 'honey-woocommerce-payments'); ?></a></strong></p>
        </div>
		<?};
        ?>
        <div class="updated notice is-dismissible">
            <p><?php echo __('Please fill in your', 'honey-woocommerce-payments'); ?> <strong><a href="admin.php?page=wc-settings&tab=checkout&section=woocommerce_honey"><?php echo __('Honey Woocommerce Settings', 'honey-woocommerce-payments'); ?></a></strong></p>
        </div>
        <?
        delete_transient( 'hwp-admin-notice-example' );
    }
}

function hwp_initial_install()
	{
	set_transient( 'hwp-admin-notice-example', true, 5 );
	}
register_activation_hook(__FILE__, 'hwp_initial_install');
