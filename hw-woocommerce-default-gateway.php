<?php
/**
	* The plugin bootstrap file
	*
	* This file is read by WordPress to generate the plugin information in the plugin
	* admin area. This file also includes all of the dependencies used by the plugin,
	* registers the activation and deactivation functions, and defines a function
	* that starts the plugin.
	*
	* @link              https://hardweb.it/
	* @since             1.0
	* @package           hw-woocommerce-default-gateway
	*
	* @wordpress-plugin
	* Plugin Name:       	Default Gateway for WooCommerce
	* Description:       	Manage the default chosen Payment method on checkout, easily!
	* Version:           	1.2
	* Requires at least: 	6.0.1
	* Tested up to: 		6.3.1
	* WC requires at least: 4.4
	* WC tested up to: 		8.2.0
	* Author:            	Hardweb.it
	* Author URI:        	https://hardweb.it/
	* License:           	GPL-2.0+
	* License URI:       	http://www.gnu.org/licenses/gpl-2.0.txt
	* Text Domain:       	hw-woocommerce-default-gateway
	* Domain Path:			/languages
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
exit; // Exit if accessed directly
}

// define plugin constants
define( 'HW_WC_DEFAULT_GATEWAY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

//Load some admin styles
add_action('admin_enqueue_scripts', 'hw_wc_default_gateway_load_scripts');
function hw_wc_default_gateway_load_scripts() {
  wp_enqueue_style('hw-woocommerce-default-gateway', HW_WC_DEFAULT_GATEWAY_PLUGIN_URL . '/assets/css/hw-woocommerce-default-gateway . css', null, null, 'all');
}

//set the chosen default gateway to current checkout session
add_action('woocommerce_before_checkout_form', 'hw_wc_default_gateway_set_to_session');
function hw_wc_default_gateway_set_to_session() {
	$chosen_gateway = get_option('hw_woocommerce_default_gateway', false);
	//set the default gateway as chosen
	WC()->session->set('chosen_payment_method', $chosen_gateway);
}

//add a new gateway column on main settings page
add_filter('woocommerce_payment_gateways_setting_columns', 'hw_wc_default_gateway_add_column');
function hw_wc_default_gateway_add_column($default_columns) {
	$new_columns = array();
	foreach ($default_columns as $id=>$description) {
		if ('description' === $id) {
			$new_columns['wc_default_gateway'] = __( 'Default', 'woocommerce' );
		}
		$new_columns[$id] = $description;
	}
return $new_columns;
}

//do the row output
add_action( 'woocommerce_payment_gateways_setting_column_wc_default_gateway', 'hw_wc_default_gateway_add_column_content' );
function hw_wc_default_gateway_add_column_content( $gateway ) {
	$chosen_gateway = get_option('hw_woocommerce_default_gateway', false);
	$selected = ( $chosen_gateway == $gateway->id ) ? "checked='checked'" : '';
	echo '<td class="hw_wc_default_gateway"><input type="radio" name="hw_wc_default_gateway_radio" class="hw_wc_default_gateway_radio" id="' . esc_attr($gateway->id) . '" value="' . esc_attr($gateway->id) . '" ' . esc_attr($selected) . ' /></td>';
}

/* send value to server */
add_action('admin_footer', 'hw_wc_default_gateway_send_chosen');
function hw_wc_default_gateway_send_chosen() {
$ajax_nonce = wp_create_nonce( 'hw-woocommerce-default-gateway' ); ?>
<script>
jQuery.noConflict()(function($){
	$(document).ready(function(){
		$('input[name="hw_wc_default_gateway_radio"]').click(function(){
			let chosen_gateway = $('input[name="hw_wc_default_gateway_radio"]:checked').val();
			let debug = true;
			$.ajax({
				url:ajaxurl,
				type: 'post',
				data: {
					action:'hw_wc_default_gateway_save_chosen',
					security: '<?php echo esc_attr($ajax_nonce); ?>',
					'chosen_gateway': chosen_gateway
					}, success: function( response ) {
						if (debug) { console.log( response ); }
						var response_obj = jQuery.parseJSON( response );

						console.log(response_obj.saved_gateway + ' set as default');
					},
					error: function(errorThrown){
						if (debug) { console . log( errorThrown ); }
						return false;
					}
			});
		});
	});
});
</script>
<?php
}

/* get and save value from client */
add_action( 'wp_ajax_hw_wc_default_gateway_save_chosen', 'do_hw_wc_default_gateway_save_chosen' );
function do_hw_wc_default_gateway_save_chosen() {
check_ajax_referer( 'hw-woocommerce-default-gateway', 'security' );
	if (isset($_POST['chosen_gateway'])) {

		$chosen_gateway = sanitize_text_field($_POST['chosen_gateway']);
		$save_option = update_option('hw_woocommerce_default_gateway', $chosen_gateway);

		echo json_encode(array('success'=>$save_option, 'saved_gateway'=>$chosen_gateway));
	} else {
		echo json_encode(array('error'=>'chosen_gateway is missing', 'data'=>filter_input_array($_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS)));
	}

wp_die();
}

//add link to plugin "settings"
add_filter( 'plugin_action_links_hw-woocommerce-default-gateway/hw-woocommerce-default-gateway.php', 'hw_woocommerce_default_gateway_settings_link' );
function hw_woocommerce_default_gateway_settings_link( $links ) {
	// Build and escape the URL .
	$url = esc_url( admin_url('admin.php?page=wc-settings&tab=checkout') );
	// Create the link .
	$settings_link = "<a href='$url'>" . __( 'Settings' ) . '</a>';
	// Adds the link to the end of the array .
	array_push(
		$links,
		$settings_link
	);
	return $links;
}