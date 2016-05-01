<?php

if ( ! defined( 'ABSPATH' ) ) exit;
add_action( 'wp_ajax_wped_verify_dkim', 'wped_verify_dkim' );

function wped_verify_dkim(){
	if ( empty( $_POST['domain'] ) ) {
		die( '-1' );
	} else {
		$domain = $_POST['domain'];
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		die( '-1' );
	}

		$url = 'https://gateway.wped.co/';
			//$url = 'http://spnl.dev/';
			$verify_ssl = true;
			if( wped_get_option('enable_nossl') ){
				$verify_ssl = false;
				$url = 'http://api.wped.co/';
				$info['X-WPED'] = "nossl";
			}
			$url =$url .'domain/dkim/' . $domain;
	$response = wp_remote_get( $url );
	$body ='';
	if( is_array($response) ) {
	  $header = $response['headers']; // array of http header lines
	  $body = $response['body']; // use the content
	  $b2 = json_decode($body);
	}
	if( $b2->success == true ){
		wped_set_option('dkim_verified', $domain);
	} else {
		wped_set_option('dkim_verified', false);
	}

	echo $body;
	die();
}


add_action( 'wp_ajax_wped_verify_spf', 'wped_verify_spf' );

function wped_verify_spf(){
	if ( empty( $_POST['domain'] ) ) {
		die( '-1' );
	} else {
		$domain = $_POST['domain'];
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		die( '-1' );
	}

		$url = 'https://gateway.wped.co/';
			//$url = 'http://spnl.dev/';
			$verify_ssl = true;
			if( wped_get_option('enable_nossl') ){
				$verify_ssl = false;
				$url = 'http://api.wped.co/';
				$info['X-WPED'] = "nossl";
			}
			$url = $url .'domain/spf/' . $domain;
	$response = wp_remote_get( $url );
	$body ='';
	if( is_array($response) ) {
	  $header = $response['headers']; // array of http header lines
	  $body = $response['body']; // use the content
	  $b2 = json_decode($body);
	}
	
	if( $b2->success == true ){
		wped_set_option('spf_verified', $domain);
	} else {
		wped_set_option('spf_verified', false);
	}
	

	echo $body;
	die();
}