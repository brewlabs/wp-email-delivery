<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class WP_Email_Delivery_Connections {
	const API_VERSION = '1.0';    
    const SSL_END_POINT = 'http://spnl-env.elasticbeanstalk.com/send/';
	const NOSSL_END_POINT = 'http://spnl-env.elasticbeanstalk.com/send';
	
		/**
	 * The single instance of WP_Email_Delivery_Settings.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * The main plugin object.
	 * @var 	object
	 * @access  public
	 * @since 	1.0.0
	 */
	public $parent = null;

	/**
	 * Prefix for plugin settings.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $base = '';

	public function __construct ( $parent ) {
		$this->parent = $parent;

		$this->base = 'wped_';

	}
	
	/**
	* Can Connected to WPED.co
	* @access  public
	* @since   1.0.0
	* @return void
	*/
	public function is_connected () {
		$key = wped_get_option('license_key');
		$enabled = wped_get_option('enable_sending');
		if( ($key && $key != '') && $enabled ){
			return true;
		}
		return false;
	} // End enqueue_styles ()

	public function is_verified(){
		if( $this->is_setup() ){
			$verify = wped_get_option('is_verified');
			if($verify == true){
				return true;
			}
			return false;
		}
		return false;
	}

	public function is_setup(){
		$key = wped_get_option('license_key');
		if( $key && $key != ''){
			return true;
		}
		return false;
	}


	//Not yet implemented in UI
	public function custom_reply_to(){
		return '';
	}


	public function mail( $to, $subject, $message, $headers = '', $attachments = array(), 
	                        $tags = array(), 
	                        $from_name = '', 
	                        $from_email = '', 
	                        $template_name = '', 
	                        $track_opens = null, 
	                        $track_clicks = null,
	                        $url_strip_qs = false,
	                        $merge = true,
	                        $global_merge_vars = array(),
	                        $merge_vars = array(),
	                        $google_analytics_domains = array(),
	                        $google_analytics_campaign = array(),
	                        $meta_data = array(),
	                        $important = false,
	                        $inline_css = null,
	                        $preserve_recipients=null,
	                        $view_content_link=null,
	                        $tracking_domain=null,
	                        $signing_domain=null,
	                        $return_path_domain=null,
	                        $subaccount=null,
	                        $recipient_metadata=null,
	                        $ip_pool=null,
	                        $send_at=null,
	                        $async=null
	                     ) {
		//if ( $track_opens === null ) $track_opens = self::getTrackOpens();
		//if ( $track_clicks === null ) $track_clicks = self::getTrackClicks();
		 
        try {
        	extract( apply_filters( 'wp_mail', compact( 'to', 'subject', 'message', 'headers', 'attachments' ) ) );
        	$message2 = compact('subject', 'from_name', 'from_email', 'to', 'headers', 'attachments', 
                                    'url_strip_qs', 
                                    'merge', 
                                    'global_merge_vars', 
                                    'merge_vars',
                                    'google_analytics_domains',
                                    'google_analytics_campaign',
                                    'meta_data',
            						'important',
        							'inline_css',
        							'preserve_recipients',
        							'view_content_link',
        							'tracking_domain',
        							'signing_domain',
        							'return_path_domain',
        							'subaccount',
        							'recipient_metadata',
        							'ip_pool',
        							'send_at',
        							'async'        	
                                    );
        	$message2['html'] = $message;
        	
            return $this->wped_mail($message2, $tags, $template_name, $track_opens, $track_clicks);
        } catch ( Exception $e ) {
	        error_log( "\nwped->::mail: Exception Caught => ".$e->getMessage()."\n" );
            return new WP_Error( $e->getMessage() );
        }
    }


     public function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
     	require WPED_PLUGIN_DIR . '/includes/legacy/wped.wp-mail.php';
     }




     /**
	 * @link https://mandrillapp.com/api/docs/messages.html#method=send
	 *
	 * @param array $message
	 * @param boolean $track_opens
	 * @param boolean $track_clicks
	 * @param array $tags
	 * @return array|WP_Error
	 */
	public function wped_mail( $message, $tags = array(), $template_name = '', $track_opens = true, $track_clicks = true ) {
	
	    try {
	        if ( !$this->is_setup() ) throw new Exception('Invalid API Key');
	        
	        /************
	        *
	        *  Processing supplied fields to make them valid for the Mandrill API
	        *
	        *************************/ 
	        $content_type = null;


	        	        
		    // Checking the user-specified headers
	            if ( empty( $message['headers'] ) ) {
		            $message['headers'] = array();
	            } else {
		            if ( !is_array( $message['headers'] ) ) {
			            $tempheaders = explode( "\n", str_replace( "\r\n", "\n", $message['headers'] ) );
		            } else {
			            $tempheaders = $message['headers'];
		            }
		            $message['headers'] = array();

		            // If it's actually got contents
		            if ( !empty( $tempheaders ) ) {
			            // Iterate through the raw headers
			            foreach ( (array) $tempheaders as $header ) {
				            if ( strpos($header, ':') === false ) continue;

				            // Explode them out
				            list( $name, $content ) = explode( ':', trim( $header ), 2 );

				            // Cleanup crew
				            $name    = trim( $name    );
				            $content = trim( $content );

				            switch ( strtolower( $name ) ) {
					            case 'from':
						            if ( strpos($content, '<' ) !== false ) {
							            // So... making my life hard again?
							            $from_name = substr( $content, 0, strpos( $content, '<' ) - 1 );
							            $from_name = str_replace( '"', '', $from_name );
							            $from_name = trim( $from_name );

							            $from_email = substr( $content, strpos( $content, '<' ) + 1 );
							            $from_email = str_replace( '>', '', $from_email );
							            $from_email = trim( $from_email );
						            } else {
							            $from_name  = '';
							            $from_email = trim( $content );
						            }
						            $message['from_email']  = $from_email;
						            $message['from_name']   = $from_name;						            
						            break;
						        case 'content-type':
										if ( strpos( $content, ';' ) !== false ) {
											list( $type, $charset ) = explode( ';', $content );
											$content_type = trim( $type );
											if ( false !== stripos( $charset, 'charset=' ) ) {
												$charset = trim( str_replace( array( 'charset=', '"' ), '', $charset ) );
											} elseif ( false !== stripos( $charset, 'boundary=' ) ) {
												$boundary = trim( str_replace( array( 'BOUNDARY=', 'boundary=', '"' ), '', $charset ) );
												$charset = '';
											}
										} else {
											$content_type = trim( $content );
										}
										
										
									break;   
					            case 'bcc':
					                // TODO: Mandrill's API only accept one BCC address. Other addresses will be silently discarded
					                $bcc = array_merge( (array) $bcc, explode( ',', $content ) );
					                
					                $message['bcc_address'] = $bcc[0];
						            break;
						            
					            case 'reply-to':
						            $message['headers'][trim( $name )] = trim( $content );
						            break;
					            case 'importance':
					            case 'x-priority':
					            case 'x-msmail-priority':
					            	if ( !$message['important'] ) $message['important'] = ( strpos(strtolower($content),'high') !== false ) ? true : false;
					            	break;
					            default:
					                if ( substr($name,0,2) == 'x-' || substr($name,0,2) == 'X-') {
    						            $message['headers'][trim( $name )] = trim( $content );
    						        }
						            break;
				            }
			            }
		            }
                }
                
            	// Adding a Reply-To header if needed.
                $reply_to = $this->custom_reply_to();
                if ( !empty($reply_to) && !in_array( 'reply-to', array_map( 'strtolower', array_keys($message['headers']) ) ) ) {
                    $message['headers']['Reply-To'] = trim($reply_to);
                }

	        // Checking To: field
                if( !is_array($message['to']) ) $message['to'] = explode(',', $message['to']);
                
                $processed_to = array();
                foreach ( $message['to'] as $email ) {
                    if ( is_array($email) ) {
                		$processed_to[] = $email;
                	} else { 
                		$processed_to[] = array( 'email' => $email );
                	}
                }
                $message['to'] = $processed_to;
	        
	        // Checking From: field
                //if ( empty($message['from_email']) ) $message['from_email'] = self::getFromEmail();
                //if ( empty($message['from_name'] ) ) $message['from_name']  = self::getFromName();
            
            // Checking tags.
    		    $message['tags'] = $this->find_tags($tags);
		    
		    // Checking attachments
                if ( !empty($message['attachments']) ) {
                	$message['attachments'] = $this->process_attachments($message['attachments']);
                	if ( is_wp_error($message['attachments']) ) {
                		throw new Exception('Invalid attachment (check http://eepurl.com/nXMa1 for supported file types).');
                	} elseif ( !is_array($message['attachments']) ) {	// some plugins return this value malformed.
                		unset($message['attachments']);
                	}
                }
		    	// Default values for other parameters
                $message['auto_text']   = true;
                $message['track_opens'] = wped_get_option('track_opens') ? true : false;
                $message['track_clicks']= wped_get_option('track_clicks') ? true : false;
                
	        	// Supporting editable sections: Common transformations for the HTML part
	        	//$nl2br = self::getnl2br() == 1;
                if ( !isset( $content_type ) ){
					$content_type = 'text/plain';
				}

				$content_type = apply_filters( 'wp_mail_content_type', $content_type );

	        	$nl2br = $content_type == 'text/plain';
	        	$nl2br = apply_filters('wped_nl2br', $nl2br, $message);
	        	if ( $nl2br ) {
	                if ( is_array($message['html']) ) {
                        foreach ($message['html'] as &$value){
                        	$value['content'] = preg_replace('#<(https?://[^*]+)>#', '$1', $value['content']);
                            $value['content'] = nl2br($value['content']);
                        }
                        
	                } else {
	                	$message['html'] = preg_replace('#<(https?://[^*]+)>#', '$1', $message['html']);
	                	$message['html'] = nl2br($message['html']);
	                }
	    		}

            	// Letting user to filter/change the message payload
            	$message['from_email']  = apply_filters('wp_mail_from', $message['from_email']);
				$message['from_name']	= apply_filters('wp_mail_from_name', $message['from_name']);

				if( empty( $message['from_email'] ) ){
					$custom_email = wped_get_option('custom_from');
					if(is_email($custom_email)){
						$message['from_email']  = $custom_email;
					} else {

					// Get the site domain and get rid of www.
						$sitename = strtolower( $_SERVER['SERVER_NAME'] );
						if ( substr( $sitename, 0, 4 ) == 'www.' ) {
							$sitename = substr( $sitename, 4 );
						}

						$message['from_email']  = 'wordpress@' . $sitename;
					}
				}

				if( empty( $message['from_name'] ) ){
					$custom_name = wped_get_option('custom_name');
					if(!empty($custom_name) && $custom_name != false ){
						$message['from_name']  = $custom_name;
					} else {
						$message['from_name']  = __('WordPress','wp-email-delivery');
					}
				}

				$enable_override = wped_get_option('enable_override');

				if($enable_override != false){
					$custom_name = wped_get_option('custom_name');
					if(!empty($custom_name) && $custom_name != false ){
						$message['from_name']  = $custom_name;
					}
					$custom_email = wped_get_option('custom_from');
					if(is_email($custom_email)){
						$message['from_email']  = $custom_email;
					} 
				}


				


				

                $message  = apply_filters('wped_payload', $message);
                



                // if user doesn't want to process this email by wped, so be it.
                if ( isset($message['force_native']) && $message['force_native'] ) throw new Exception('Manually falling back to native wp_mail()');
                
            	// Setting the tags property correctly to be received by the Mandrill's API
               if ( !is_array($message['tags']['user']) )      $message['tags']['user']        = array();
               if ( !is_array($message['tags']['general']) )   $message['tags']['general']     = array();
               if ( !is_array($message['tags']['automatic']) ) $message['tags']['automatic']   = array();
                
	            $message['tags'] = array_merge( $message['tags']['general'], $message['tags']['automatic'], $message['tags']['user'] );
	           
	        	// Sending the message
	          	return $this->message_send( $message );
            
	    } catch ( Exception $e) {
	        error_log( date('Y-m-d H:i:s') . " wped::send_email: Exception Caught => ".$e->getMessage()."\n" );
			return new WP_Error( $e->getMessage() );
	    }
	}

	/**
	 * @param string $subject
	 * @return array
	 */
	public function find_tags($tags) {

        // Getting general tags
        $gtags   = array();
        /*
        $general_tags = self::getTags();
		if ( !empty( $general_tags ) ) {
		    $gtags   = explode("\n",$general_tags);		
		    foreach ( $gtags as $index => $gtag ) {
		        if ( empty($gtag) ) unset($gtags[$index]);
		    }
		    $gtags = array_values($gtags);
		}
		*/
		// Finding tags based on WP Backtrace 
		$trace  = debug_backtrace();
		$level  = 4;        
		$function = $trace[$level]['function'];

        $wtags = array();
		if( 'include' == $function || 'require' == $function ) {

			$file = basename($trace[$level]['args'][0]);
			$wtags[] = "wp_{$file}";
		}
		else {
			if( isset( $trace[$level]['class'] ) )
				$function = $trace[$level]['class'].$trace[$level]['type'].$function;
			$wtags[] = "wp_{$function}";
		}
		
		return array('user' => $tags, 'general' => $gtags, 'automatic' => $wtags);
	}

	public function message_send($message){
			$info = array(
				"X-WPED"=>"ssl",
				"X-WPED-DOMAIN"=> home_url()
			);

			$url = self::SSL_END_POINT;
			//$url = 'http://spnl.dev/';
			$verify_ssl = true;
			if( wped_get_option('enable_nossl') ){
				$verify_ssl = false;
				$url = self::NOSSL_END_POINT;
				$info['X-WPED'] = "nossl";
			}

			if(defined('SPNL_TESTING')){
				$url = 'http://spnl.io/';
			}
			
			$message['headers'] =  array_merge( $info , $message['headers'] );
			$message['subaccount'] =  wped_get_option('license_key');
			$message['metadata'] = array(
			    'return'=> home_url()
			);
			

		 	$message_encoded = json_encode( $message );
		 	switch (json_last_error()) {
		        case JSON_ERROR_UTF8:
		            $message_encoded = json_encode( $this->utf8_encode_recursive($message) );
		        break;
		    }
			$response = wp_remote_post( $url , array(
				'method' => 'POST',
				'timeout' => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => array('Content-Type' => 'application/json'),
				'body' => $message_encoded,
				'sslverify' => $verify_ssl,
				'cookies' => array()
			    )
			);
			
			if( is_wp_error( $response ) ) {
			   	$error_message = $response->get_error_message();
			   	error_log( date('Y-m-d H:i:s') . " wped::message_send: Exception Caught => ". $error_message ."\n" );
			  	return false;
			} else {
				return true;
			}

			return false;   
			  
	}
	
	public function utf8_encode_recursive ($array)
	{
		$result = array();
		foreach ($array as $key => $value)
		{
		    if (is_array($value))
		    {
		        $result[$key] = $this->utf8_encode_recursive($value);
		    }
		    else if (is_string($value))
		    {
		        $result[$key] = utf8_encode($value);
		    }
		    else
		    {
		        $result[$key] = $value;
		    }
		}
		return $result;
	}


	public function process_attachments($attachments = array()) {
        if ( !is_array($attachments) && $attachments )
	        $attachments = explode( "\n", str_replace( "\r\n", "\n", $attachments ) );
	        
        foreach ( $attachments as $index => $attachment ) {
            try {
                $attachments[$index] = $this->get_attachment_struct($attachment);
            } catch ( Exception $e ) {
                error_log( "\nwped::process_attachments: $attachment => ".$e->getMessage()."\n" );
                return new WP_Error( $e->getMessage() );
            }
        }
        
        return $attachments;
    }

       static function get_attachment_struct($path) {
        
        $struct = array();
        
        try {
            
            if ( !@is_file($path) ) throw new Exception($path.' is not a valid file.');

            $filename = basename($path);
            
            if ( !function_exists('get_magic_quotes') ) {
                function get_magic_quotes() { return false; }
            }
            if ( !function_exists('set_magic_quotes') ) {
                function set_magic_quotes($value) { return true;}
            }
            
            $file_buffer  = file_get_contents($path);
            $file_buffer  = chunk_split(base64_encode($file_buffer), 76, "\n");
            
            
            $mime_type = '';
			if ( function_exists('finfo_open') && function_exists('finfo_file') ) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $path);
            } elseif ( function_exists('mime_content_type') ) {
                $mime_type = mime_content_type($path);
            }

            if ( !empty($mime_type) ) $struct['type']     = $mime_type;
            $struct['name']     = $filename;
            $struct['content']  = $file_buffer;

        } catch (Exception $e) {
            throw new WPED_Exception('Error creating the attachment structure: '.$e->getMessage());
        }
        
        return $struct;
    }

	/**
	 * Main WP_Email_Delivery_Connections Instance
	 *
	 * Ensures only one instance of WP_Email_Delivery_Connections is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see WP_Email_Delivery()
	 * @return Main WP_Email_Delivery_Connections instance
	 */
	public static function instance ( $parent ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent );
		}
		return self::$_instance;
	} // End instance()

}