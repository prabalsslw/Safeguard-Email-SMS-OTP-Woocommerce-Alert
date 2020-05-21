<?php
	defined( 'ABSPATH' ) or die(); 
	# Protect from alien invasion

	# OTP page rewrite rule

	add_action( 'init', 'safeg_init_internal' );
	function safeg_init_internal()
	{
	    add_rewrite_tag('%safeg_auth%', '([^&]+)');
	    add_rewrite_rule( '^verify/([^/]*)/?', 'index.php?safeg_api=1&safeg_auth=$matches[1]', 'top' );
	}

	add_filter( 'query_vars', 'safeg_query_vars' );
	function safeg_query_vars( $query_vars )
	{
	    $query_vars[] = 'safeg_api';
	    $query_vars[] = 'safeg_error';
	    return $query_vars;
	}

	add_action( 'parse_request', 'safeg_parse_request' );
	function safeg_parse_request( &$wp )
	{
	    if ( array_key_exists( 'safeg_api', $wp->query_vars ) ) {
	        require_once( SAFEG_PATH . 'lib/safeguard-verify.php');
	        exit();
	    }
	    return;
	}

	# Resend OTP page rewrite rule

	add_action( 'init', 'safeg_re_init_internal' );
	function safeg_re_init_internal()
	{
	    add_rewrite_tag('%safeg_auth%', '([^&]+)');
	    add_rewrite_rule( '^resend-otp/([^/]*)/?', 'index.php?safeg_re_api=1&safeg_auth=$matches[1]', 'top' );
	}

	add_filter( 'query_vars', 'safeg_re_query_vars' );
	function safeg_re_query_vars( $query_vars )
	{
	    $query_vars[] = 'safeg_re_api';
	    $query_vars[] = 'safeg_error';
	    return $query_vars;
	}

	add_action( 'parse_request', 'safeg_re_parse_request' );
	function safeg_re_parse_request( &$wp )
	{
	    if ( array_key_exists( 'safeg_re_api', $wp->query_vars ) ) {
	        require_once( SAFEG_PATH . 'lib/safeguard-resend-otp.php');
	        exit();
	    }
	    return;
	}