<?php
/*
Plugin Name: WP Email Delivery
Plugin URI: https://www.wpemaildelivery.com
Description: Managed Email Delivery for WordPress
Author: BrewLabs
Author URI: https://www.wpemaildelivery.com/
Requires at least: 3.7
Tested up to: 5.6
Version: 1.20.11.23

Text Domain: wp-email-delivery
Domain Path: /lang/
*/

if ( ! defined( 'ABSPATH' ) ) exit;

// Load plugin class files
require_once( 'includes/class-wp-email-delivery.php' );
require_once( 'includes/class-wp-email-delivery-settings.php' );
require_once( 'includes/class-wp-email-delivery-connections.php' );

// Load plugin libraries
if(is_admin()){
	require_once( 'includes/lib/class-wp-email-delivery-admin-ajax.php' );
}
require_once( 'includes/lib/class-wp-email-delivery-admin-api.php' );
require_once( 'includes/lib/class-wp-email-delivery-post-type.php' );
require_once( 'includes/lib/class-wp-email-delivery-taxonomy.php' );
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

class WPED_Exception extends Exception {}
//functions
require_once( 'includes/misc-functions.php' );

define( 'WPED_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPED_IS_NETWORK', wped_is_network_activated() );


/**
 * Returns the main instance of WP_Email_Delivery to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object WP_Email_Delivery
 */
function WPED () {
	return WP_Email_Delivery::instance( __FILE__, '1.20.11.23' );
}
WPED();

function WPED_WP_MAIL(){

	if (function_exists ( 'wp_mail' )) {
		WPED()->wp_mail_error = __('The function wp_mail() is already in use by another plugin. WPED will not be able to send for you and has been disabled. ', 'wp-email-delivery');
		wped_set_option('enable_sending', false);
		return;
	}

	if( WPED()->connections->is_connected() ) {
		
		function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
			
			try {
				
				$sent = WPED()->connections->mail( $to, $subject, $message, $headers, $attachments );
				
				if ( is_wp_error($sent)  ) {
					return WPED()->connections->wp_mail( $to, $subject, $message, $headers, $attachments );
                }

				return true;					
			} catch ( Exception $e ) {
				return WPED()->connections->wp_mail($to, $subject, $message, $headers, $attachments );
			}
		}
	}
}
WPED_WP_MAIL();







