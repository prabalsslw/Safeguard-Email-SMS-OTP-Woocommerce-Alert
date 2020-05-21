<?php 
	# Validate login with OTP
	$safeg_settings = get_option( 'safeg_setting' );

	if((isset($safeg_settings['enable_plugin']) && !empty($safeg_settings['enable_plugin'] )) && (isset($safeg_settings['otp_enable']) || isset($safeg_settings['email_otp_disable'])) )
	{
		add_filter( 'authenticate', 'safeg_auth_login', 30, 3 );
	}
	

	function safeg_auth_login ( $user, $username, $password ) 
	{
	    if ( is_wp_error( $user ) ) 
	    {
	        return $user;
	    } 
	    else 
	    {
	        global $wpdb;
	        $table_name = $wpdb->prefix . "safeguard_otp";
	        $safeg_settings = get_option( 'safeg_setting' );

	        if ( !isset( $safeg_settings['timeout'] ) || '' == $safeg_settings['timeout'] ) 
	        {
	            $safeg_settings['timeout'] = 3;
	        }

	        $user_id = sanitize_key( $user->ID );

	        $login_attempt = $wpdb->get_row( $wpdb->prepare(
	            "
	                SELECT *
	                FROM $table_name
	                WHERE user_id = %d AND login_status = 0
	            ",
	            $user_id
	        ) );

	        if ( NULL === $login_attempt ) 
	        {
	            $user_hash = md5( $user->ID . time() );
	            $user_ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
	            $wpdb->insert(
	                $table_name,
	                array(
	                    'user_id' => $user_id,
	                    'user_obj' => serialize($user),
	                    'auth_token' => $user_hash,
	                    'login_time' => current_time( 'mysql' ),
	                    'user_ip' => $user_ip,
	                )
	            );

	            wp_redirect( home_url() . "/verify/" . $user_hash . "/");
	        } 
	        elseif ( ( current_time( 'timestamp' ) - strtotime( $login_attempt->login_time ) ) > $safeg_settings['timeout'] * MINUTE_IN_SECONDS ) 
	        {
	            $wpdb->update(
	                $table_name,
	                array(
	                    'login_status' => 3
	                ),
	                array( 'auth_token' => $login_attempt->auth_token ),
	                array(
	                    '%d'
	                ),
	                array( '%s' )
	            );

	            $user_hash = md5( $user->ID . time() );
	            $user_ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
	            $wpdb->insert(
	                $table_name,
	                array(
	                    'user_id' => $user_id,
	                    'user_obj' => serialize($user),
	                    'auth_token' => $user_hash,
	                    'login_time' => current_time( 'mysql' ),
	                    'user_ip' => $user_ip,
	                )
	            );

	            wp_redirect( home_url() . "/verify/" . $user_hash . "/");
	        } 
	        else 
	        {
	            wp_redirect( home_url() . "/verify/" . $login_attempt->auth_token . "/");
	        }

	        exit;
	    }
	}

	function safeg_error_code_sanitize($ecode)
	{
		if(is_numeric($ecode))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	# Display error message on login page
	function safeg_modify_html() 
	{
	    if (isset($_GET['safeg_error']) && safeg_error_code_sanitize($_GET['safeg_error'])) {
	        $safeg_error = intval($_GET['safeg_error']);
	    }
	    else
	    {
	        $safeg_error = '';
	    }

	    if ( $safeg_error != '' ) {
	        $login_error = get_query_var( 'safeg_error' );
	        switch ( $safeg_error ) {
	            case 401:
	                $message = '<strong>ERROR</strong>: Session timed out!';
	                break;
	            case 402:
	                $message = '<strong>ERROR</strong>: IP does not match!';
	                break;
	            case 601:
	            	$message = '<strong>ERROR</strong>: You have exceeded OTP limit!';
	                break;
	            default:
	                $message = '<strong>ERROR</strong>: Session timed out!';
	        }
	        add_filter( 'login_message', function () use ($message) {return "<div id='login_error'>$message</div>";} );
	    }
	}

	if((isset($safeg_settings['enable_plugin']) && !empty($safeg_settings['enable_plugin'] )) && (isset($safeg_settings['otp_enable']) || isset($safeg_settings['email_otp_disable'])))
	{
		add_action( 'login_head', 'safeg_modify_html');
	}