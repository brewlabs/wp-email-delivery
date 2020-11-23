<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class WP_Email_Delivery_Settings {

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

	/**
	 * Available settings for plugin.
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = array();

	public function __construct ( $parent ) {
		$this->parent = $parent;

		$this->base = 'wped_';

		// Initialise settings
		add_action( 'init', array( $this, 'init_settings' ), 11 );

		// Register plugin settings
		add_action( 'admin_init' , array( $this, 'register_settings' ) );

		if(! wped_is_network_activated() ){
			// Add settings page to menu
			add_action( 'admin_menu' , array( $this, 'add_menu_item' ) );
		} else {
			add_filter('network_admin_menu',  array( $this, 'add_network_menu_item' ) );
			add_action('network_admin_edit_'. $this->parent->_token . '_settings',  array( $this, 'network_admin_save' ) );
		}

		// Add settings link to plugins page
		add_filter( 'plugin_action_links_' . plugin_basename( $this->parent->file ) , array( $this, 'add_settings_link' ) );
	}

	/**
	 * Initialise settings
	 * @return void
	 */
	public function init_settings () {
		$this->settings = $this->settings_fields();
	}


	public function network_admin_save(){
			
		  check_admin_referer($this->parent->_token . '_settings-options');
		  // This is the list of registered options.
		  global $new_whitelist_options;
		  $options = $new_whitelist_options[$this->parent->_token . '_settings'];
		  // Go through the posted data and save only our options. This is a generic
		  // way to do this, but you may want to address the saving of each option
		  // individually.
		 
		  foreach ($options as $option) {
		    if (!empty($_POST[$option])) {
		      // If we registered a callback function to sanitizes the option's
		      // value it is where we call it (see register_setting).
		      $option_value = apply_filters('sanitize_option_' . $option, $_POST[$option]);
		      // And finally we save our option with the site's options.
		      update_site_option($option, $option_value);
		    } else {
		      // If the option is not here then delete it. It depends on how you
		      // want to manage your defaults however.
		      delete_site_option($option);
		    }
		  }
		  $red = array('page' => $this->parent->_token . '_settings','settings-updated' => 'true');
		 
			if ( isset( $_POST['tab'] ) && $_POST['tab'] ) {
				$red['tab'] = $_POST['tab'];
			}
			
		  // At last we redirect back to our options page.
		  wp_redirect(add_query_arg( $red , network_admin_url('settings.php')));
		  exit;
	}

	/**
	 * Add settings page to admin menu
	 * @return void
	 */
	public function add_menu_item () {
		$page = add_options_page( __( 'WP Email Delivery', 'wp-email-delivery' ) , __( 'WP Email Delivery', 'wp-email-delivery' ) , 'manage_options' , $this->parent->_token . '_settings' ,  array( $this, 'settings_page' ) );
		add_action( 'admin_print_styles-' . $page, array( $this, 'settings_assets' ) );
	}

	/**
	 * Add settings page to admin menu
	 * @return void
	 */
	public function add_network_menu_item () {
		$page =  add_submenu_page('settings.php',  __( 'WP Email Delivery', 'wp-email-delivery' ) , __( 'WP Email Delivery', 'wp-email-delivery' ) , 'manage_network_options' , $this->parent->_token . '_settings' ,  array( $this, 'settings_page' ) );
		add_action( 'admin_print_styles-' . $page, array( $this, 'settings_assets' ) );	
	}

	/**
	 * Load settings JS & CSS
	 * @return void
	 */
	public function settings_assets () {

		// We're including the farbtastic script & styles here because they're needed for the colour picker
		// If you're not including a colour picker field then you can leave these calls out as well as the farbtastic dependency for the wpt-admin-js script below
		wp_enqueue_style( 'farbtastic' );
    	wp_enqueue_script( 'farbtastic' );

    	// We're including the WP media scripts here because they're needed for the image upload field
    	// If you're not including an image upload then you can leave this function call out
    	wp_enqueue_media();

    	wp_register_script( $this->parent->_token . '-settings-js', $this->parent->assets_url . 'js/settings' . $this->parent->script_suffix . '.js', array( 'farbtastic', 'jquery' ), '1.0.0' );
    	wp_enqueue_script( $this->parent->_token . '-settings-js' );
	}

	/**
	 * Add settings link to plugin list table
	 * @param  array $links Existing links
	 * @return array 		Modified links
	 */
	public function add_settings_link ( $links ) {
		$settings_link = '<a href="options-general.php?page=' . $this->parent->_token . '_settings">' . __( 'Settings', 'wp-email-delivery' ) . '</a>';
  		array_push( $links, $settings_link );
  		return $links;
	}

	/**
	 * Build settings fields
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields () {

		

		$settings['standard'] = array(
			'title'					=> __( 'Setup', 'wp-email-delivery' ),
			'description'			=> __( 'Just enter your API key and enjoy sending via our API. You don\'t even need to worry about your hosting provider blocking SMTP ports. <br>WPED doesn\'t use SMTP so your hosting provider can\'t block it.', 'wp-email-delivery' ),
			'fields'				=> array(
				array(
					'id' 			=> 'license_key',
					'label'			=> __( 'License Key' , 'wp-email-delivery' ),
					'description'	=> __( 'Please enter your key from <a href="https://www.wpemaildelivery.com">https://www.wpemaildelivery.com</a>.', 'wp-email-delivery' ),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'	=> __( 'WPED-XXXXXXXXXXXXXXXXXXXXXXXXXX', 'wp-email-delivery' )
				),
				/*
				array(
					'id' 			=> 'verify_directions',
					'label'			=> __( 'Verification', 'wp-email-delivery' ),
					'description'	=> __( 'To send emails with WP Email Delivery you must verify either an email address or domain', 'wp-email-delivery' ),
					'type'			=> 'link',
					'default'		=> '',
					'classes'			=> 'button-primary',
					'display'		=> 'Verify Email or Domain',
					'disable'		=> !WPED()->connections->is_setup(),
					'url' 			=> '#wped/verify'
				),
				*/
				array(
					'id' 			=> 'enable_sending',
					'label'			=> __( 'Enable', 'wp-email-delivery' ),
					'description'	=> __( 'Allow <b>WP Email Delivery</b> to override the default wp_mail() function to send emails. ( We recommend you send a few tests firsts )', 'wp-email-delivery' ),
					'type'			=> 'checkbox',
					'default'		=> '',
					'disable'		=> !WPED()->connections->is_setup()
				),
				
				/*
				array(
					'id' 			=> 'password_field',
					'label'			=> __( 'A Password' , 'wp-email-delivery' ),
					'description'	=> __( 'This is a standard password field.', 'wp-email-delivery' ),
					'type'			=> 'password',
					'default'		=> '',
					'placeholder'	=> __( 'Placeholder text', 'wp-email-delivery' )
				),
				array(
					'id' 			=> 'secret_text_field',
					'label'			=> __( 'Some Secret Text' , 'wp-email-delivery' ),
					'description'	=> __( 'This is a secret text field - any data saved here will not be displayed after the page has reloaded, but it will be saved.', 'wp-email-delivery' ),
					'type'			=> 'text_secret',
					'default'		=> '',
					'placeholder'	=> __( 'Placeholder text', 'wp-email-delivery' )
				),
				array(
					'id' 			=> 'text_block',
					'label'			=> __( 'A Text Block' , 'wp-email-delivery' ),
					'description'	=> __( 'This is a standard text area.', 'wp-email-delivery' ),
					'type'			=> 'textarea',
					'default'		=> '',
					'placeholder'	=> __( 'Placeholder text for this textarea', 'wp-email-delivery' )
				),
				array(
					'id' 			=> 'single_checkbox',
					'label'			=> __( 'An Option', 'wp-email-delivery' ),
					'description'	=> __( 'A standard checkbox - if you save this option as checked then it will store the option as \'on\', otherwise it will be an empty string.', 'wp-email-delivery' ),
					'type'			=> 'checkbox',
					'default'		=> ''
				),
				array(
					'id' 			=> 'select_box',
					'label'			=> __( 'A Select Box', 'wp-email-delivery' ),
					'description'	=> __( 'A standard select box.', 'wp-email-delivery' ),
					'type'			=> 'select',
					'options'		=> array( 'drupal' => 'Drupal', 'joomla' => 'Joomla', 'wordpress' => 'WordPress' ),
					'default'		=> 'wordpress'
				),
				array(
					'id' 			=> 'radio_buttons',
					'label'			=> __( 'Some Options', 'wp-email-delivery' ),
					'description'	=> __( 'A standard set of radio buttons.', 'wp-email-delivery' ),
					'type'			=> 'radio',
					'options'		=> array( 'superman' => 'Superman', 'batman' => 'Batman', 'ironman' => 'Iron Man' ),
					'default'		=> 'batman'
				),
				array(
					'id' 			=> 'multiple_checkboxes',
					'label'			=> __( 'Some Items', 'wp-email-delivery' ),
					'description'	=> __( 'You can select multiple items and they will be stored as an array.', 'wp-email-delivery' ),
					'type'			=> 'checkbox_multi',
					'options'		=> array( 'square' => 'Square', 'circle' => 'Circle', 'rectangle' => 'Rectangle', 'triangle' => 'Triangle' ),
					'default'		=> array( 'circle', 'triangle' )
				)
				*/
			)
		);
		if( WPED()->connections->is_setup() ){
		$settings['extra'] = array(
			'title'					=> __( 'Advanced', 'wp-email-delivery' ),
			'description'			=> __( 'Some extra features to make WP Email Delivery even better.', 'wp-email-delivery' ),
			'fields'				=> array(
				array(
					'id' 			=> 'enable_override',
					'label'			=> __( 'Override From', 'wp-email-delivery' ),
					'description'	=> __( 'This will force all emails to come from the settings below.', 'wp-email-delivery' ),
					'type'			=> 'checkbox',
					'default'		=> ''
				),
				array(
					'id' 			=> 'custom_name',
					'label'			=> __( 'From Name', 'wp-email-delivery' ),
					'description'	=> __( 'This from name wil be used if one is not set when emails are sent to wp_mail() ', 'wp-email-delivery' ),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'   => ''
				),
				array(
					'id' 			=> 'custom_from',
					'label'			=> __( 'From Email', 'wp-email-delivery' ),
					'description'	=> __( 'This from email address wil be used if one is not set when emails are sent to wp_mail() ', 'wp-email-delivery' ),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'   => ''
				),
				
				/*
				array(
					'id' 			=> 'track_opens',
					'label'			=> __( 'Track Opens', 'wp-email-delivery' ),
					'description'	=> __( 'WPED can track your emails and who opens them. ( Reports to view opens coming soon ) ', 'wp-email-delivery' ),
					'type'			=> 'checkbox',
					'default'		=> ''
				),
				array(
					'id' 			=> 'track_clicks',
					'label'			=> __( 'Track Clicks', 'wp-email-delivery' ),
					'description'	=> __( 'WPED can track your emails and who clicks on links within them. ( Reports to view clicks coming soon ) ', 'wp-email-delivery' ),
					'type'			=> 'checkbox',
					'default'		=> ''
				),
				*/
				array(
					'id' 			=> 'enable_nossl',
					'label'			=> __( 'Disable SSL Sending', 'wp-email-delivery' ),
					'description'	=> __( 'This may be required if your host can not connect over https to the WP Email Delivery API', 'wp-email-delivery' ),
					'type'			=> 'checkbox',
					'default'		=> ''
				),
			)
		);

		$settings['testing'] = array(
			'title'					=> __( 'Testing', 'wp-email-delivery' ),
			'description'			=> __( 'Test WP Email Delivery sending before you activate it.', 'wp-email-delivery' ),
			'custom_save' 			=> __( 'Save & Send Test Email', 'wp-email-delivery' ),
			'fields'				=> array(
				array(
					'id' 			=> 'test_email',
					'label'			=> __( 'Email' , 'wp-email-delivery' ),
					'description'	=> __( 'Email address to send test to.', 'wp-email-delivery' ),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'	=> __( 'delivery@wped.co', 'wp-email-delivery' )
				)
			)
		);
		
		$s = wped_get_option('sending');
		$inline_button = '';
		if($s){
			$inline_button = '<a href="#wped/status" id="sending_verify" class="button-primary">Verify</a>';
		}

		
		$stat = '';

		$spf = wped_get_option('spf_verified');
	
		$stat .= '<br>SPF: ';
		if($spf != false && $spf == $s){
			$stat .= '<span id="spf_check" class="dashicons dashicons-yes" style="color:green;"></span>';
		} else {
			$stat .= '<span id="spf_check" class="dashicons dashicons-yes" style="color:red;"></span>';
		}

		$dkim = wped_get_option('dkim_verified');
		$stat .= '<br>DKIM: ';
		if($dkim != false && $dkim == $s){
			$stat .= '<span id="dkim_check" class="dashicons dashicons-yes" style="color:green;"></span>';
		} else {
			$stat .= '<span id="dkim_check" class="dashicons dashicons-yes" style="color:red;"></span>';
		}

		$settings['DNS'] = array(
			'title'					=> __( 'DNS ( SPF & DKIM )', 'wp-email-delivery' ),
			'description'			=> __( 'Setting up SPF and DKIM require access to your DNS records. Even if you don\'t have access to these records you can still use WP Email Delivery.' , 'wp-email-delivery' ),
			
			'fields'				=> array(
				array(
					'id' 			=> 'sending',
					'label'			=> __( 'Sending Domain' , 'wp-email-delivery' ),
					'description'	=> __( '<br>This is the domain the WPED will create a DKIM record for.', 'wp-email-delivery' ) . $stat,
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'	=> 'example.com',
					'extra' => $inline_button
				),
				array(
					'id' 			=> 'spf',
					'label'			=> __( 'SPF Record' , 'wp-email-delivery' ),
					'description'	=> __( '<br>If you don\'t yet have an SPF record, you\'ll want to add one for your domain. At a minimum, the value should be the entry above if you\'re only sending mail through WP Email Delivery for that domain.<br><br>If you already have a TXT record with SPF information, you\'ll need to add WPED\'s servers to that record by adding <strong>include:spf.wped.co</strong> in the record (before the last operator, which is usually ?all, ~all, or -all).', 'wp-email-delivery' ),
					'type'			=> 'readonly',
					'default'		=> 'v=spf1 include:spf.wped.co ?all',
					'placeholder'	=> ''
				),
				array(
					'id' 			=> 'dkim',
					'pre_text' => 'Create a TXT record. Enter:<br><br><strong>Host/Name:</strong> api._domainkey',
					'label'			=> __( 'DKIM Record' , 'wp-email-delivery' ),
					'type'			=> 'readonlytextarea',
					'always'		=> 'k=rsa;t=s;p=MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCbmGbQMzYeMvxwtNQoXN0waGYaciuKx8mtMh5czguT4EZlJXuCt6V+l56mmt3t68FEX5JJ0q4ijG71BGoFRkl87uJi7LrQt1ZZmZCvrEII0YO4mp8sDLXC8g1aUAoi8TJgxq2MJqCaMyj5kAm3Fdy2tzftPCV/lbdiJqmBnWKjtwIDAQAB',
				)
			)
		);


		}


		$settings = apply_filters( $this->parent->_token . '_settings_fields', $settings );

		return $settings;
	}

	private function send_test_email(){
		$headers = array('Content-type: text/html');
		$this->parent->connections->mail( sanitize_email($_POST[ $this->base .'test_email' ]), "WP Email Delivery Setup Test", wped_basic_test_email(), $headers, "");
	}

	/**
	 * Register plugin settings
	 * @return void
	 */
	public function register_settings () {
		if ( is_array( $this->settings ) ) {

			// Check posted/selected tab
			$current_section = '';
			if (!empty( $_POST['tab'] )) {
				$current_section = sanitize_title_with_dashes($_POST['tab']);
			} else {
				if ( !empty( $_GET['tab'] ) ) {
					$current_section = sanitize_title_with_dashes($_GET['tab']);
				}
			}

			if(!empty( $_POST[ $this->base .'test_email' ] )){
				$this->send_test_email();	
			}

			foreach ( $this->settings as $section => $data ) {

				if ( $current_section && $current_section != $section ) continue;



				// Add section to page
				add_settings_section( $section, $data['title'], array( $this, 'settings_section' ), $this->parent->_token . '_settings' );

				foreach ( $data['fields'] as $field ) {

					// Validation callback for field
					$validation = '';
					if ( isset( $field['callback'] ) ) {
						$validation = $field['callback'];
					}

					// Register field
					$option_name = $this->base . $field['id'];
					register_setting( $this->parent->_token . '_settings', $option_name, $validation );

					// Add field to page
					add_settings_field( $field['id'], $field['label'], array( $this->parent->admin, 'display_field' ), $this->parent->_token . '_settings', $section, array( 'field' => $field, 'prefix' => $this->base ) );
				}

				if ( ! $current_section ) break;
			}
		}
	}

	public function settings_section ( $section ) {
		$html = '<p> ' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		echo $html;
	}

	/**
	 * Load settings page content
	 * @return void
	 */
	public function settings_page () {
		if( !WPED()->connections->is_setup() ){
			wped_set_option('enable_sending', false);
		}
		
		$button_text = esc_attr( __( 'Save Settings' , 'wp-email-delivery' ) );
		// Build page HTML
		$html = '<div class="wrap" id="' . $this->parent->_token . '_settings">' . "\n";
			$html .= '<h2>' . __( 'WP Email Delivery' , 'wp-email-delivery' ) . ' <small>v'.$this->parent->_version.'</small></h2>' . "\n";
			if(isset($this->parent->wp_mail_error)){
				$html .= '</p>'. $this->parent->wp_mail_error .'</p>';
			}
			$tab = '';
			if ( !empty( $_GET['tab'] ) ) {
				$tab .= sanitize_title_with_dashes($_GET['tab']);
			}

			// Show page tabs
			if ( is_array( $this->settings ) && 1 < count( $this->settings ) ) {

				$html .= '<h2 class="nav-tab-wrapper">' . "\n";

				$c = 0;
				foreach ( $this->settings as $section => $data ) {

					// Set tab class
					$class = 'nav-tab';
					if ( empty( $_GET['tab'] ) ) {
						if ( 0 == $c ) {
							$class .= ' nav-tab-active';
							$button_text = isset( $data['custom_save'] ) ? $data['custom_save'] : esc_attr( __( 'Save Settings' , 'wp-email-delivery' ) ) ;
					
						}
					} else {
						if ( !empty( $_GET['tab'] ) && $section == sanitize_title_with_dashes($_GET['tab']) ) {
							$class .= ' nav-tab-active';
							$button_text = isset( $data['custom_save'] ) ? $data['custom_save'] : esc_attr( __( 'Save Settings' , 'wp-email-delivery' ) ) ;
					
						}
					}

					// Set tab link
					$tab_link = add_query_arg( array( 'tab' => $section ) );
					if ( !empty( $_GET['settings-updated'] ) ) {
						$tab_link = remove_query_arg( 'settings-updated', $tab_link );
					}

					// Output tab
					$html .= '<a href="' . $tab_link . '" class="' . esc_attr( $class ) . '">' . esc_html( $data['title'] ) . '</a>' . "\n";

					++$c;
				}

				$html .= '</h2>' . "\n";
			}
			$target = "options.php";
			if(wped_is_network_activated()){
				$target = 'edit.php?action='.$this->parent->_token . '_settings';
			} 
			
			$html .= '<form method="post" action="'.$target.'" enctype="multipart/form-data">' . "\n";

				// Get settings fields
				ob_start();
				settings_fields( $this->parent->_token . '_settings' );
				do_settings_sections( $this->parent->_token . '_settings' );
				$html .= ob_get_clean();
				if($button_text != 'none'){
					$html .= '<p class="submit">' . "\n";
						$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
						$html .= '<input name="Submit" type="submit" class="button-primary" value="' . $button_text . '" />' . "\n";
					$html .= '</p>' . "\n";
				}
			$html .= '</form>' . "\n";
			if( !WPED()->connections->is_setup() ){
				$html .= "<div style='padding: 30px 40px; border: solid 1px #cdcdcd; background: #fff; margin-top: 5px;'><h2 style='margin-top: 0px; padding-top: 0px;'>Get WP Email Delivery for your site.</h2>";
				$html .= "<p>Try it for free with <strong>50 emails per month</strong>. Sign up at <a target='_blank' href='https://www.wpemaildelivery.com'>https://www.wpemaildelivery.com</a><br>Great for small sites and developers.</p>";
				$html .= '<p>Need more emails per month check out our monthly plans starting at <strong>$5.00 a month for 10,000 emails</strong>.<br>';		
				$html .= 'We also have yearly plans starting at <strong>$18 per year for 500 monthly emails</strong>.</p>';	
				$html .= "</div>";

			}
		$html .= '</div>' . "\n";




		echo $html;
		$this->templates();
	}

	public function templates(){
		 include 'templates/verify-domain.html';
	}


	/**
	 * Main WP_Email_Delivery_Settings Instance
	 *
	 * Ensures only one instance of WP_Email_Delivery_Settings is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see WP_Email_Delivery()
	 * @return Main WP_Email_Delivery_Settings instance
	 */
	 static function instance ( $parent ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent );
		}
		return self::$_instance;
	} // End instance()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
	} // End __wakeup()

}
