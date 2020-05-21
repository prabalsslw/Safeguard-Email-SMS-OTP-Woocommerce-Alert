<?php
	defined( 'ABSPATH' ) or die(); 
	# Protect from alien invasion


	# OTP validation Page
	require_once( SAFEG_PATH . 'lib/safeguard-sms-api.php' );
	
	use Safeguard\Admin\Sms\Safeguard_Sms_Api;
	
	$safeg_settings = get_option( 'safeg_setting' );



	if((isset($safeg_settings['enable_plugin']) && !empty($safeg_settings['enable_plugin'])) && (isset($safeg_settings['otp_enable']) || isset($safeg_settings['email_otp_disable'])))
	{
		global $wpdb;
		$auth_token = sanitize_key($wp->query_vars['safeg_auth']);

		$table_name = $wpdb->prefix . "safeguard_otp";
		$safeg_settings = get_option( 'safeg_setting' );


		if ( !isset( $safeg_settings['timeout'] ) || '' == $safeg_settings['timeout'] ) {
		    $safeg_settings['timeout'] = 3;
		}
		if ( !isset( $safeg_settings['from_email'] ) || '' == $safeg_settings['from_email'] ) {
		    $safeg_settings['from_email'] = get_option('admin_email');
		}

		$otp_enabled = isset($safeg_settings['otp_enable']) ? 'on' : 'off';

		if ( $auth_token != "" ) {
		    global $login_attempt;
		    $login_attempt = $wpdb->get_row( $wpdb->prepare(
		        "
		            SELECT *
		            FROM $table_name
		            WHERE auth_token = %s
		        ",
		        $auth_token
		    ) );

		    

		    #################### check for login status, check for otp already sent, check for timeout #####################
		    $the_time = current_time( 'timestamp' );

		    if ( ( $login_attempt != NULL ) && ( ( $the_time- strtotime( $login_attempt->login_time ) ) <= $safeg_settings['timeout'] * MINUTE_IN_SECONDS ) && ( 0 == $login_attempt->login_status ) ) 
		    {
		        if ( isset( $_POST['otp'] ) ) 
		        {
		            $user_otp = sanitize_key( $_POST['otp'] );

		            if ( $login_attempt->otp == $user_otp ) 
		            {
		                $user_ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);

		                if ( $user_ip == $login_attempt->user_ip ) 
		                {
		                    $user = unserialize( $login_attempt->user_obj );

		                    remove_filter( 'authenticate', 'safeg_auth_login', 30 );

		                    $creds = array();
		                    $creds['user_login'] = 'example';
		                    $creds['user_password'] = 'plaintextpw';
		                    $creds['remember'] = false;

		                    $logged_user = wp_signon( $creds, false );

		                    if ( $logged_user ) 
		                    {
		                        $wpdb->update(
		                            $table_name,
		                            array(
		                                'login_status' => 1
		                            ),
		                            array( 'auth_token' => $auth_token ),
		                            array(
		                                '%d'
		                            ),
		                            array( '%s' )
		                        );

		                        wp_redirect( get_bloginfo( "wpurl" ) . "/wp-admin/" );
		                        exit;
		                    }
		                } 
		                else 
		                {
		                    $wpdb->update(
		                        $table_name,
		                        array(
		                            'login_status' => 4
		                        ),
		                        array( 'auth_token' => $auth_token ),
		                        array(
		                            '%d'
		                        ),
		                        array( '%s' )
		                    );

		                    $login_url = wp_login_url();
		                    $redirect_to = add_query_arg( array('safeg_error' => '402'), $login_url );
		                    wp_redirect( $redirect_to );
		                    exit;
		                }
		            } 
		            else 
		            {
		                $safeg_error = '<strong>ERROR</strong>: Incorrect OTP entered!';
		            }
		        }


		        ################################ Generate OTP and send ###########################

		        if ( NULL == $login_attempt->otp ) 
		        {
		        	$destination = "";
		        	if(isset($safeg_settings['otp_enable']))
		        	{
		        		$destination = 'SMS OTP'; 
		        	}
		        	if(isset($safeg_settings['email_otp_disable']))
		        	{
		        		$destination = 'Email OTP'; 
		        	}
		        	if(isset($safeg_settings['otp_enable']) && isset($safeg_settings['email_otp_disable']))
		        	{
		        		$destination = 'SMS, Email OTP'; 
		        	}

		            $user = unserialize( $login_attempt->user_obj );
		            $otp = mt_rand(100000, 999999);

		            $wpdb->update(
		                $table_name,
		                array(
		                    'otp' => $otp,
		                    'otp_destination' => sanitize_text_field($destination)
		                ),
		                array( 'auth_token' => $auth_token ),
		                array(
		                    '%d', '%s'
		                ),
		                array( '%s' )
		            );

		            ################ OTP SMS ################

		            $all_meta_for_user = get_user_meta( $user->ID );

		            if((isset($all_meta_for_user['safeg_phone_number'][0]) || isset($all_meta_for_user['billing_phone'][0])) && isset($safeg_settings['otp_enable']) && isset($safeg_settings['enable_plugin']))
		            {
		                if(isset($all_meta_for_user['safeg_phone_number'][0]) && $all_meta_for_user['safeg_phone_number'][0] != "")
		            	{
		            		$safeg_phone_no = $all_meta_for_user['safeg_phone_number'][0];
		            	}
		            	else
		            	{
		            		$safeg_phone_no = $all_meta_for_user['billing_phone'][0];
		            	}

		                $api_url = trim($safeg_settings['api_url']);
		                $otp_text = trim($safeg_settings['otp_text']);

		                $otp_text = str_ireplace("{{OTP}}", $otp, $otp_text);

		                if(isset($safeg_settings['get_post']))
		                {
		                    $response = Safeguard_Sms_Api::call_to_post_api($api_url, Safeguard_Sms_Api::set_post_parameter($safeg_settings['api_peram'], $safeg_phone_no, $otp_text));
		                }
		                else
		                {
		                    $response = Safeguard_Sms_Api::call_to_get_api($api_url, Safeguard_Sms_Api::set_get_parameter($safeg_settings['api_peram'], $safeg_phone_no, $otp_text));
		                }
		                // print_r($response);
		                if($response != "")
		                {
		                    $wpdb->update(
		                        $table_name,
		                        array(
		                            'sms_ref_id' => sanitize_text_field(serialize($response))
		                        ),
		                        array( 'auth_token' => $auth_token ),
		                        array(
		                            '%s'
		                        ),
		                        array( '%s' )
		                    );
		                }
		            }


		            ##################### Edit the OTP email message here ######################
		            if(isset($safeg_settings['email_otp_disable']) && isset($safeg_settings['enable_plugin']))
		            {
		                $message = "Dear {$user->user_nicename}, \r\n\r\n";
		                $message .= "Your One Time Pin is: <b>{$otp}</b>\r\n\r\n";
		                $message .= "This pin is only valid for the next {$safeg_settings['timeout']} minutes. \r\n\r\n";
		                $message .= "Thanks & Regards,\r\n\r\n";
		                $message .= get_bloginfo('name');
		                $headers = 'From: ' . get_bloginfo('name') . ' <' . $safeg_settings['from_email'] . '>';

		                function safeg_otp_email( $user, $otp, $message, $headers ) {
		                    $mail_sent = wp_mail( $user->user_email, get_bloginfo('name') . ": One Time Pin", apply_filters( "safeg_otp_message", $message ), apply_filters( "safeg_otp_headers", $headers ) );
		                }
		                if ( ! has_action( "safeg_otp_send" ) ) {
		                    add_action( "safeg_otp_send", "safeg_otp_email", 10, 4 );
		                }
		            
		                do_action( "safeg_otp_send", $user, $otp, $message, $headers );
		            }
		        }
		    } 
		    else 
		    {
		        if ( 0 == $login_attempt->login_status ) {
		            $wpdb->update(
		                $table_name,
		                array(
		                    'login_status' => 3
		                ),
		                array( 'auth_token' => $auth_token ),
		                array(
		                    '%d'
		                ),
		                array( '%s' )
		            );
		        }

		        $login_url = wp_login_url();
		        $redirect_to = add_query_arg( array('safeg_error' => '401'), $login_url );
		        wp_redirect( $redirect_to );
		        exit;
		    }
		}

	?>


	<!DOCTYPE html>
		<!--[if IE 8]>
		    <html xmlns="http://www.w3.org/1999/xhtml" class="ie8" <?php language_attributes(); ?>>
		<![endif]-->
		<!--[if !(IE 8) ]><!-->
		    <html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
		<!--<![endif]-->
		<head>
		<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
		<meta name="viewport" content="width=device-width; initial-scale=1.0">
		<title><?php bloginfo('name'); ?> &rsaquo; Enter OTP Code</title>
		<?php

		wp_admin_css( 'login', true );

		do_action( 'login_enqueue_scripts' );
		?>
		</head>
		<body class="login login-action-login wp-core-ui  locale-en-us">
		    <div id="login">
		        <h1><a href="https://wordpress.org/">Powered by WordPress</a></h1>
		        <?php
		        if ( isset( $safeg_error ) ) { ?>
		        <div id='login_error'><p><?php echo $safeg_error; ?></p></div>
		        <?php } else { ?>
		        <div><p class='message'>OTP has been sent to your Email/Phone</p></div>
		        <?php } ?>

		        <form name="otpform" id="otpform" action="<?php echo get_bloginfo( 'wpurl' ) . '/verify/' . $auth_token . '/'; ?>" method="post">
		            <p>
		                <label for="user_otp">Enter One Time Pin<br />
		                <input type="number" name="otp" id="user_otp" class="input" value="" size="20" /></label>
		            </p>
		            <p class="submit">
		                <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="Confirm" />
		            </p>
		        </form>

		        <p id="nav">
		            Haven't received your OTP? <a href="<?php echo home_url() . "/resend-otp/" . $login_attempt->auth_token . "/" ?>">Resend OTP</a>
		        </p>

		        <script type="text/javascript">
		        function wp_attempt_focus(){
		        setTimeout( function(){ try{
		        d = document.getElementById('user_otp');
		        d.focus();
		        d.select();
		        } catch(e){}
		        }, 200);
		        }

		        wp_attempt_focus();
		        if(typeof wpOnload=='function')wpOnload();
		        </script>

		    </div>
		    <div class="clear"></div>
		</body>
	</html>
<?php 
}
else
{
	wp_redirect( wp_login_url());	                    
	exit;
}