<?php
	#--------------------
	# Resend OTP Templete
	#--------------------
	require_once( SAFEG_PATH . 'lib/safeguard-sms-api.php' );
	
	use Safeguard\Admin\Sms\Safeguard_Sms_Api;

	global $wpdb;
	$table_name = $wpdb->prefix . "safeguard_otp";
	$safeg_settings = get_option( 'safeg_setting' );
	
	$user_ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
	$auth_token = sanitize_key($wp->query_vars['safeg_auth']);

	if((isset($safeg_settings['enable_plugin']) && !empty($safeg_settings['enable_plugin'])) && (isset($safeg_settings['otp_enable']) || isset($safeg_settings['email_otp_disable'])))
	{
		if($auth_token != "")
		{
			global $login_attempt;
			$auth_token = sanitize_key($wp->query_vars['safeg_auth']);

		    $login_attempt = $wpdb->get_row( $wpdb->prepare(
		        "
		            SELECT *
		            FROM $table_name
		            WHERE auth_token = %s
		        ",
		        $auth_token
		    ) );

			if($login_attempt->otp_sent_limit < 5)
			{
				$otp_limit = $login_attempt->otp_sent_limit + 1;
				$otp = mt_rand(100000, 999999);
				$wpdb->update(
	                $table_name,
	                array(
	                	'otp' => $otp,
	                    'login_status' => 0,
	                    'login_time' => current_time( 'mysql' ),
	                    'user_ip' => $user_ip,
	                    'otp_sent_limit' => $otp_limit
	                ),
	                array( 'auth_token' => $auth_token ),
	                array(
	                    '%d','%d','%s','%s'
	                ),
	                array( '%s' )
	            );

	            $all_meta_for_user = get_user_meta($login_attempt->user_id);

	            if(isset($safeg_settings['email_otp_disable']) && isset($safeg_settings['enable_plugin']))
	            {
	            	$user = unserialize( $login_attempt->user_obj );
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

	            if($otp != "" && $auth_token != "")
                {
                	wp_redirect( home_url() . "/verify/" . $auth_token . "/");
                	exit;
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
		        $redirect_to = add_query_arg( array('safeg_error' => '601'), $login_url );
		        wp_redirect( $redirect_to );
		        exit;
            }
		}
	}
	

?>