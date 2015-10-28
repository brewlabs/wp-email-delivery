<?php
/*
 * Plugin Name: WP Email Delivery
 * Version: 1.0
 * Plugin URI: http://www.wpemaildelivery.com
 * Description: Managed Email Delivery for WordPress
 * Author: Josh Lyford
 * Author URI: http://www.wpemaildelivery.com/
 * Requires at least: 4.0
 * Tested up to: 4.3
 *
 * Text Domain: wp-email-delivery
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Josh LYford
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Load plugin class files
require_once( 'includes/class-wp-email-delivery.php' );
require_once( 'includes/class-wp-email-delivery-settings.php' );
require_once( 'includes/class-wp-email-delivery-connections.php' );

//functions
require_once( 'includes/misc-functions.php' );


// Load plugin libraries
require_once( 'includes/lib/class-wp-email-delivery-admin-api.php' );
require_once( 'includes/lib/class-wp-email-delivery-post-type.php' );
require_once( 'includes/lib/class-wp-email-delivery-taxonomy.php' );

class WPED_Exception extends Exception {}

/**
 * Returns the main instance of WP_Email_Delivery to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object WP_Email_Delivery
 */

function WEPD () {
	$instance = WP_Email_Delivery::instance( __FILE__, '1.0.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = WP_Email_Delivery_Settings::instance( $instance );
	}

	if ( is_null( $instance->connections ) ) {
		$instance->connections = WP_Email_Delivery_Connections::instance( $instance );
	}

	if( $instance->connections->is_connected() ) {

		function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
			$instance = WP_Email_Delivery::instance( __FILE__, '1.0.0' );
			try {

				$sent = $instance->connections->mail( $to, $subject, $message, $headers, $attachments );
				
				if ( is_wp_error($sent)  ) {
				    return $instance->connections->wp_mail( $to, $subject, $message, $headers, $attachments );
                }

				return true;					
			} catch ( Exception $e ) {
				return $instance->connections->wp_mail($to, $subject, $message, $headers, $attachments );
			}
		}
	}



	return $instance;
}

WEPD();


