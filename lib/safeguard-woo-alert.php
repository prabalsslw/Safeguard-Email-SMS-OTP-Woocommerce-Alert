<?php
#-------------------------
# Register & Trigger Hook For Woocommerce alert
#-------------------------

namespace Safeguard\Admin\Woosms;

require_once( SAFEG_PATH . 'lib/safeguard-sms-api.php' );
use Safeguard\Admin\Sms\Safeguard_Sms_Api;

global $safeg_settings;
global $woocommerce;

class Safeguard_Woo_Alert
{
	
	public function __construct()
	{
		$safeg_settings = get_option( 'safeg_setting' );

		if(isset($safeg_settings['woo_pending_alert']) && $safeg_settings['woo_pending_alert'] != "")
		{
			add_action( 'woocommerce_order_status_pending', array($this, 'safeg_alert_pending'));
		}
		if(isset($safeg_settings['woo_processing_alert']) && $safeg_settings['woo_processing_alert'] != "")
		{
			add_action( 'woocommerce_order_status_processing', array($this, 'safeg_alert_processing'));
		}
		if(isset($safeg_settings['woo_hold_alert']) && $safeg_settings['woo_hold_alert'] != "")
		{
			add_action( 'woocommerce_order_status_on-hold', array($this, 'safeg_alert_hold'));
		}
		if(isset($safeg_settings['woo_fail_alert']) && $safeg_settings['woo_fail_alert'] != "")
		{
			add_action( 'woocommerce_order_status_failed', array($this, 'safeg_alert_failed'));
		}
		if(isset($safeg_settings['woo_cancel_alert']) && $safeg_settings['woo_cancel_alert'] != "")
		{
			add_action( 'woocommerce_order_status_cancelled', array($this, 'safeg_alert_cancelled'));
		}
		if(isset($safeg_settings['woo_complete_alert']) && $safeg_settings['woo_complete_alert'] != "")
		{
			add_action( 'woocommerce_order_status_completed', array($this, 'safeg_alert_completed'));
		}
		if(isset($safeg_settings['woo_refund_alert']) && $safeg_settings['woo_refund_alert'] != "")
		{
			add_action( 'woocommerce_order_status_refunded', array($this, 'safeg_alert_refunded'));
		}
		if(isset($safeg_settings['woo_partially_alert']) && $safeg_settings['woo_partially_alert'] != "")
		{
			add_action('woocommerce_order_status_partially-paid', array($this, 'safeg_alert_partially'));
		}
		if(isset($safeg_settings['woo_shipped_alert']) && $safeg_settings['woo_shipped_alert'] != "")
		{
			add_action( 'woocommerce_order_status_shipped', array($this, 'safeg_alert_shipped'));
		}
		if(isset($safeg_settings['user_reg_alert']) && $safeg_settings['user_reg_alert'] != "")
		{
			add_action( 'user_register', array($this, 'safeg_alert_registration'));
		}
	}

	public function safeg_alert_registration($user_id){

		global $wpdb; 
		$safeg_settings = get_option( 'safeg_setting' );
		$all_meta_for_user 	= get_user_meta( $user_id );
		error_log(json_encode($all_meta_for_user), 0);

		$nickname = $all_meta_for_user['nickname'][0];
		$customer_mobile = $all_meta_for_user['safeg_phone_number'][0];

		$smstext = trim($safeg_settings['user_reg_templete']);
	    $api_url = trim($safeg_settings['api_url']);
	    $sms_type = 'User Registration';

	    if( isset($safeg_settings['enable_plugin']) && isset($safeg_settings['user_reg_templete']) && isset($safeg_settings['otp_woo_alert']) && !empty($customer_mobile) && !empty($smstext) && isset($safeg_settings['user_reg_alert']))
	    {
	    	$smstext = str_ireplace("{{name}}", $nickname, $smstext);
	        
	        if(isset($safeg_settings['get_post']))
            {
                $response = Safeguard_Sms_Api::call_to_post_api($api_url, Safeguard_Sms_Api::set_post_parameter($safeg_settings['api_peram'], $customer_mobile, $smstext));
            }
            else
            {
                $response = Safeguard_Sms_Api::call_to_get_api($api_url, Safeguard_Sms_Api::set_get_parameter($safeg_settings['api_peram'], $customer_mobile, $smstext));
            }

            $this->save_response($user_id, $nickname, $sms_type, $customer_mobile, $response);
	    }
		
		// error_log(serialize($all_meta_for_user), 0); 
	}

	public function safeg_alert_pending($order_id) {

		global $wpdb;
	    global $woocommerce;
		$safeg_settings = get_option( 'safeg_setting' );
	    $order 			= wc_get_order( $order_id );
	    $order_amount	= $order->get_total();
	    $user 			= $order->get_user();
	    $user_id 		= $order->get_user_id();
	    $currency 		= $order->get_currency();

	    if($order->get_billing_phone() != "")
	    {
	    	$name    		 = $order->get_billing_last_name().' '.$order->get_billing_first_name();
	    	$customer_mobile = $order->get_billing_phone();
	    }
	    else
	    {
	    	$all_meta_for_user 	= get_user_meta( $user_id );
			$customer_mobile 	= $all_meta_for_user['safeg_phone_number'][0];
			$name 				= $all_meta_for_user['first_name'][0].' '.$all_meta_for_user['last_name'][0];
	    }
	    

	    $status  = 'Pending';
	    $smstext = trim($safeg_settings['order_sms_templete']);
	    $api_url = trim($safeg_settings['api_url']);
	    $sms_type = 'Order Notification Alert - '.$status;

	    if( isset($safeg_settings['enable_plugin']) && isset($safeg_settings['order_sms_templete']) && isset($safeg_settings['otp_woo_alert']) && !empty($customer_mobile) && !empty($smstext) && isset($safeg_settings['woo_pending_alert']))
	    {
	    	$smstext = str_ireplace("{{name}}", $name, $smstext);
	    	$smstext = str_ireplace("{{status}}", $status, $smstext);
	    	$smstext = str_ireplace("{{amount}}", $order_amount, $smstext);
	    	$smstext = str_ireplace("{{currency}}", $currency, $smstext);
	        $smstext = str_ireplace("{{order_id}}", $order_id, $smstext);
	        
	        if(isset($safeg_settings['get_post']))
            {
                $response = Safeguard_Sms_Api::call_to_post_api($api_url, Safeguard_Sms_Api::set_post_parameter($safeg_settings['api_peram'], $customer_mobile, $smstext));
            }
            else
            {
                $response = Safeguard_Sms_Api::call_to_get_api($api_url, Safeguard_Sms_Api::set_get_parameter($safeg_settings['api_peram'], $customer_mobile, $smstext));
            }
            
            $this->save_response($user_id, $name, $sms_type, $customer_mobile, $response);
	    }

    }

    public function safeg_alert_failed($order_id) {

    	global $wpdb;
	    global $woocommerce;
    	$safeg_settings = get_option( 'safeg_setting' );
	    $order 			= wc_get_order( $order_id );
	    $order_amount	= $order->get_total();
	    $user 			= $order->get_user();
	    $user_id 		= $order->get_user_id();
	    $currency 		= $order->get_currency();

	    if($order->get_billing_phone() != "")
	    {
	    	$name    		 = $order->get_billing_last_name().' '.$order->get_billing_first_name();
	    	$customer_mobile = $order->get_billing_phone();
	    }
	    else
	    {
	    	$all_meta_for_user 	= get_user_meta( $user_id );
			$customer_mobile 	= $all_meta_for_user['safeg_phone_number'][0];
			$name 				= $all_meta_for_user['first_name'][0].' '.$all_meta_for_user['last_name'][0];
	    }
	    

	    $status  = 'Failed';
	    $smstext = trim($safeg_settings['order_sms_templete']);
	    $api_url = trim($safeg_settings['api_url']);
	    $sms_type = 'Order Notification Alert - '.$status;

	    if( isset($safeg_settings['enable_plugin']) && isset($safeg_settings['order_sms_templete']) && isset($safeg_settings['otp_woo_alert']) && !empty($customer_mobile) && !empty($smstext) && isset($safeg_settings['woo_fail_alert'] ))
	    {
	    	$smstext = str_ireplace("{{name}}", $name, $smstext);
	    	$smstext = str_ireplace("{{status}}", $status, $smstext);
	    	$smstext = str_ireplace("{{amount}}", $order_amount, $smstext);
	    	$smstext = str_ireplace("{{currency}}", $currency, $smstext);
	        $smstext = str_ireplace("{{order_id}}", $order_id, $smstext);
	        
	        if(isset($safeg_settings['get_post']))
            {
                $response = Safeguard_Sms_Api::call_to_post_api($api_url, Safeguard_Sms_Api::set_post_parameter($safeg_settings['api_peram'], $customer_mobile, $smstext));
            }
            else
            {
                $response = Safeguard_Sms_Api::call_to_get_api($api_url, Safeguard_Sms_Api::set_get_parameter($safeg_settings['api_peram'], $customer_mobile, $smstext));
            }
            error_log("Hitted", 0);
            $this->save_response($user_id, $name, $sms_type, $customer_mobile, $response);
	    }

    }

    public function safeg_alert_hold($order_id) {

    	global $wpdb;
	    global $woocommerce;
    	$safeg_settings = get_option( 'safeg_setting' );
	    $order 			= wc_get_order( $order_id );
	    $order_amount	= $order->get_total();
	    $user 			= $order->get_user();
	    $user_id 		= $order->get_user_id();
	    $currency 		= $order->get_currency();

	    if($order->get_billing_phone() != "")
	    {
	    	$name    		 = $order->get_billing_last_name().' '.$order->get_billing_first_name();
	    	$customer_mobile = $order->get_billing_phone();
	    }
	    else
	    {
	    	$all_meta_for_user 	= get_user_meta( $user_id );
			$customer_mobile 	= $all_meta_for_user['safeg_phone_number'][0];
			$name 				= $all_meta_for_user['first_name'][0].' '.$all_meta_for_user['last_name'][0];
	    }
	    

	    $status  = 'On-Hold';
	    $smstext = trim($safeg_settings['order_sms_templete']);
	    $api_url = trim($safeg_settings['api_url']);
	    $sms_type = 'Order Notification Alert - '.$status;

	    if( isset($safeg_settings['enable_plugin']) && isset($safeg_settings['order_sms_templete']) && isset($safeg_settings['otp_woo_alert']) && !empty($customer_mobile) && !empty($smstext) && isset($safeg_settings['woo_hold_alert']))
	    {
	    	$smstext = str_ireplace("{{name}}", $name, $smstext);
	    	$smstext = str_ireplace("{{status}}", $status, $smstext);
	    	$smstext = str_ireplace("{{amount}}", $order_amount, $smstext);
	    	$smstext = str_ireplace("{{currency}}", $currency, $smstext);
	        $smstext = str_ireplace("{{order_id}}", $order_id, $smstext);
	        
	        if(isset($safeg_settings['get_post']))
            {
                $response = Safeguard_Sms_Api::call_to_post_api($api_url, Safeguard_Sms_Api::set_post_parameter($safeg_settings['api_peram'], $customer_mobile, $smstext));
            }
            else
            {
                $response = Safeguard_Sms_Api::call_to_get_api($api_url, Safeguard_Sms_Api::set_get_parameter($safeg_settings['api_peram'], $customer_mobile, $smstext));
            }

            $this->save_response($user_id, $name, $sms_type, $customer_mobile, $response);
	    }

    }

    public function safeg_alert_processing($order_id) {
 
    	global $wpdb;
    	global $woocommerce;
    	$safeg_settings = get_option( 'safeg_setting' );
	    $order 			= wc_get_order( $order_id );
	    $order_amount	= $order->get_total();
	    $user 			= $order->get_user();
	    $user_id 		= $order->get_user_id();
	    $currency 		= $order->get_currency();
	    

	    if($order->get_billing_phone() != "")
	    {
	    	$name    		 = $order->get_billing_last_name().' '.$order->get_billing_first_name();
	    	$customer_mobile = $order->get_billing_phone();
	    }
	    else
	    {
	    	$all_meta_for_user 	= get_user_meta( $user_id );
			$customer_mobile 	= $all_meta_for_user['safeg_phone_number'][0];
			$name 				= $all_meta_for_user['first_name'][0].' '.$all_meta_for_user['last_name'][0];
	    }
	    

	    $status  = 'Processing';
	    $smstext = trim($safeg_settings['order_sms_templete']);
	    $api_url = trim($safeg_settings['api_url']);
	    $sms_type = 'Order Notification Alert - '.$status;

	    if( isset($safeg_settings['enable_plugin']) && isset($safeg_settings['order_sms_templete']) && isset($safeg_settings['otp_woo_alert']) && !empty($customer_mobile) && !empty($smstext) && isset($safeg_settings['woo_processing_alert'] ))
	    {
	    	$smstext = str_ireplace("{{name}}", $name, $smstext);
	    	$smstext = str_ireplace("{{status}}", $status, $smstext);
	    	$smstext = str_ireplace("{{amount}}", $order_amount, $smstext);
	    	$smstext = str_ireplace("{{currency}}", $currency, $smstext);
	        $smstext = str_ireplace("{{order_id}}", $order_id, $smstext);
	        
	        if(isset($safeg_settings['get_post']))
            {
                $response = Safeguard_Sms_Api::call_to_post_api($api_url, Safeguard_Sms_Api::set_post_parameter($safeg_settings['api_peram'], $customer_mobile, $smstext));
            }
            else
            {
                $response = Safeguard_Sms_Api::call_to_get_api($api_url, Safeguard_Sms_Api::set_get_parameter($safeg_settings['api_peram'], $customer_mobile, $smstext));
            }

            $this->save_response($user_id, $name, $sms_type, $customer_mobile, $response);
	    }

    }

    public function safeg_alert_completed($order_id) {

    	global $wpdb;
	    global $woocommerce;
    	$safeg_settings = get_option( 'safeg_setting' );
	    $order 			= wc_get_order( $order_id );
	    $order_amount	= $order->get_total();
	    $user 			= $order->get_user();
	    $user_id 		= $order->get_user_id();
	    $currency 		= $order->get_currency();

	    if($order->get_billing_phone() != "")
	    {
	    	$name    		 = $order->get_billing_last_name().' '.$order->get_billing_first_name();
	    	$customer_mobile = $order->get_billing_phone();
	    }
	    else
	    {
	    	$all_meta_for_user 	= get_user_meta( $user_id );
			$customer_mobile 	= $all_meta_for_user['safeg_phone_number'][0];
			$name 				= $all_meta_for_user['first_name'][0].' '.$all_meta_for_user['last_name'][0];
	    }
	    

	    $status  = 'Completed';
	    $smstext = trim($safeg_settings['order_sms_templete']);
	    $api_url = trim($safeg_settings['api_url']);
	    $sms_type = 'Order Notification Alert - '.$status;

	    if( isset($safeg_settings['enable_plugin']) && isset($safeg_settings['order_sms_templete']) && isset($safeg_settings['otp_woo_alert']) && !empty($customer_mobile) && !empty($smstext) && isset($safeg_settings['woo_complete_alert'] ))
	    {
	    	$smstext = str_ireplace("{{name}}", $name, $smstext);
	    	$smstext = str_ireplace("{{status}}", $status, $smstext);
	    	$smstext = str_ireplace("{{amount}}", $order_amount, $smstext);
	    	$smstext = str_ireplace("{{currency}}", $currency, $smstext);
	        $smstext = str_ireplace("{{order_id}}", $order_id, $smstext);
	        
	        if(isset($safeg_settings['get_post']))
            {
                $response = Safeguard_Sms_Api::call_to_post_api($api_url, Safeguard_Sms_Api::set_post_parameter($safeg_settings['api_peram'], $customer_mobile, $smstext));
            }
            else
            {
                $response = Safeguard_Sms_Api::call_to_get_api($api_url, Safeguard_Sms_Api::set_get_parameter($safeg_settings['api_peram'], $customer_mobile, $smstext));
            }

            $this->save_response($user_id, $name, $sms_type, $customer_mobile, $response);
	    }

    }

    public function safeg_alert_refunded($order_id) {

    	global $wpdb;
	    global $woocommerce;
    	$safeg_settings = get_option( 'safeg_setting' );
	    $order 			= wc_get_order( $order_id );
	    $order_amount	= $order->get_total();
	    $user 			= $order->get_user();
	    $user_id 		= $order->get_user_id();
	    $currency 		= $order->get_currency();

	    if($order->get_billing_phone() != "")
	    {
	    	$name    		 = $order->get_billing_last_name().' '.$order->get_billing_first_name();
	    	$customer_mobile = $order->get_billing_phone();
	    }
	    else
	    {
	    	$all_meta_for_user 	= get_user_meta( $user_id );
			$customer_mobile 	= $all_meta_for_user['safeg_phone_number'][0];
			$name 				= $all_meta_for_user['first_name'][0].' '.$all_meta_for_user['last_name'][0];
	    }
	    

	    $status  = 'Refunded';
	    $smstext = trim($safeg_settings['order_sms_templete']);
	    $api_url = trim($safeg_settings['api_url']);
	    $sms_type = 'Order Notification Alert - '.$status;

	    if( isset($safeg_settings['enable_plugin']) && isset($safeg_settings['order_sms_templete']) && isset($safeg_settings['otp_woo_alert']) && !empty($customer_mobile) && !empty($smstext) && isset($safeg_settings['woo_refund_alert']))
	    {
	    	$smstext = str_ireplace("{{name}}", $name, $smstext);
	    	$smstext = str_ireplace("{{status}}", $status, $smstext);
	    	$smstext = str_ireplace("{{amount}}", $order_amount, $smstext);
	    	$smstext = str_ireplace("{{currency}}", $currency, $smstext);
	        $smstext = str_ireplace("{{order_id}}", $order_id, $smstext);
	        
	        if(isset($safeg_settings['get_post']))
            {
                $response = Safeguard_Sms_Api::call_to_post_api($api_url, Safeguard_Sms_Api::set_post_parameter($safeg_settings['api_peram'], $customer_mobile, $smstext));
            }
            else
            {
                $response = Safeguard_Sms_Api::call_to_get_api($api_url, Safeguard_Sms_Api::set_get_parameter($safeg_settings['api_peram'], $customer_mobile, $smstext));
            }

            $this->save_response($user_id, $name, $sms_type, $customer_mobile, $response);
	    }

    }

    public function safeg_alert_cancelled($order_id) {

    	global $wpdb;
	    global $woocommerce;
    	$safeg_settings = get_option( 'safeg_setting' );
	    $order 			= wc_get_order( $order_id );
	    $order_amount	= $order->get_total();
	    $user 			= $order->get_user();
	    $user_id 		= $order->get_user_id();
	    $currency 		= $order->get_currency();

	    if($order->get_billing_phone() != "")
	    {
	    	$name    		 = $order->get_billing_last_name().' '.$order->get_billing_first_name();
	    	$customer_mobile = $order->get_billing_phone();
	    }
	    else
	    {
	    	$all_meta_for_user 	= get_user_meta( $user_id );
			$customer_mobile 	= $all_meta_for_user['safeg_phone_number'][0];
			$name 				= $all_meta_for_user['first_name'][0].' '.$all_meta_for_user['last_name'][0];
	    }
	    

	    $status  = 'Cancelled';
	    $smstext = trim($safeg_settings['order_sms_templete']);
	    $api_url = trim($safeg_settings['api_url']);
	    $sms_type = 'Order Notification Alert - '.$status;

	    if( isset($safeg_settings['enable_plugin']) && isset($safeg_settings['order_sms_templete']) && isset($safeg_settings['otp_woo_alert']) && !empty($customer_mobile) && !empty($smstext) && isset($safeg_settings['woo_cancel_alert']))
	    {
	    	$smstext = str_ireplace("{{name}}", $name, $smstext);
	    	$smstext = str_ireplace("{{status}}", $status, $smstext);
	    	$smstext = str_ireplace("{{amount}}", $order_amount, $smstext);
	    	$smstext = str_ireplace("{{currency}}", $currency, $smstext);
	        $smstext = str_ireplace("{{order_id}}", $order_id, $smstext);
	        
	        if(isset($safeg_settings['get_post']))
            {
                $response = Safeguard_Sms_Api::call_to_post_api($api_url, Safeguard_Sms_Api::set_post_parameter($safeg_settings['api_peram'], $customer_mobile, $smstext));
            }
            else
            {
                $response = Safeguard_Sms_Api::call_to_get_api($api_url, Safeguard_Sms_Api::set_get_parameter($safeg_settings['api_peram'], $customer_mobile, $smstext));
            }

            $this->save_response($user_id, $name, $sms_type, $customer_mobile, $response);
	    }

    }

	public function safeg_alert_shipped($order_id){

		global $wpdb;
	    global $woocommerce;
	    $safeg_settings = get_option( 'safeg_setting' );
	    $order 			= wc_get_order( $order_id );
	    $order_amount	= $order->get_total();
	    $user 			= $order->get_user();
	    $user_id 		= $order->get_user_id();
	    $currency 		= $order->get_currency();

	    if($order->get_billing_phone() != "")
	    {
	    	$name    		 = $order->get_billing_last_name().' '.$order->get_billing_first_name();
	    	$customer_mobile = $order->get_billing_phone();
	    }
	    else
	    {
	    	$all_meta_for_user 	= get_user_meta( $user_id );
			$customer_mobile 	= $all_meta_for_user['safeg_phone_number'][0];
			$name 				= $all_meta_for_user['first_name'][0].' '.$all_meta_for_user['last_name'][0];
	    }
	    

	    $status  = 'Shipped';
	    $smstext = trim($safeg_settings['order_sms_templete']);
	    $api_url = trim($safeg_settings['api_url']);
	    $sms_type = 'Order Notification Alert - '.$status;

	    if( isset($safeg_settings['enable_plugin']) && isset($safeg_settings['order_sms_templete']) && isset($safeg_settings['otp_woo_alert']) && !empty($customer_mobile) && !empty($smstext) && isset($safeg_settings['woo_shipped_alert']))
	    {
	    	$smstext = str_ireplace("{{name}}", $name, $smstext);
	    	$smstext = str_ireplace("{{status}}", $status, $smstext);
	    	$smstext = str_ireplace("{{amount}}", $order_amount, $smstext);
	    	$smstext = str_ireplace("{{currency}}", $currency, $smstext);
	        $smstext = str_ireplace("{{order_id}}", $order_id, $smstext);
	        
	        if(isset($safeg_settings['get_post']))
            {
                $response = Safeguard_Sms_Api::call_to_post_api($api_url, Safeguard_Sms_Api::set_post_parameter($safeg_settings['api_peram'], $customer_mobile, $smstext));
            }
            else
            {
                $response = Safeguard_Sms_Api::call_to_get_api($api_url, Safeguard_Sms_Api::set_get_parameter($safeg_settings['api_peram'], $customer_mobile, $smstext));
            }

            $this->save_response($user_id, $name, $sms_type, $customer_mobile, $response);
	    }

	}

	public function safeg_alert_partially($order_id){

		global $wpdb;
	    global $woocommerce;
	    $safeg_settings = get_option( 'safeg_setting' );
	    $order 			= wc_get_order( $order_id );
	    $order_amount	= $order->get_total();
	    $user 			= $order->get_user();
	    $user_id 		= $order->get_user_id();
	    $currency 		= $order->get_currency();

	    if($order->get_billing_phone() != "")
	    {
	    	$name    		 = $order->get_billing_last_name().' '.$order->get_billing_first_name();
	    	$customer_mobile = $order->get_billing_phone();
	    }
	    else
	    {
	    	$all_meta_for_user 	= get_user_meta( $user_id );
			$customer_mobile 	= $all_meta_for_user['safeg_phone_number'][0];
			$name 				= $all_meta_for_user['first_name'][0].' '.$all_meta_for_user['last_name'][0];
	    }
	    

	    $status  = 'Partially Paid';
	    $smstext = trim($safeg_settings['order_sms_templete']);
	    $api_url = trim($safeg_settings['api_url']);
	    $sms_type = 'Order Notification Alert - '.$status;

	    if( isset($safeg_settings['enable_plugin']) && isset($safeg_settings['order_sms_templete']) && isset($safeg_settings['otp_woo_alert']) && !empty($customer_mobile) && !empty($smstext) && isset($safeg_settings['woo_partially_alert']))
	    {
	    	$smstext = str_ireplace("{{name}}", $name, $smstext);
	    	$smstext = str_ireplace("{{status}}", $status, $smstext);
	    	$smstext = str_ireplace("{{amount}}", $order_amount, $smstext);
	    	$smstext = str_ireplace("{{currency}}", $currency, $smstext);
	        $smstext = str_ireplace("{{order_id}}", $order_id, $smstext);
	        
	        if(isset($safeg_settings['get_post']))
            {
                $response = Safeguard_Sms_Api::call_to_post_api($api_url, Safeguard_Sms_Api::set_post_parameter($safeg_settings['api_peram'], $customer_mobile, $smstext));
            }
            else
            {
                $response = Safeguard_Sms_Api::call_to_get_api($api_url, Safeguard_Sms_Api::set_get_parameter($safeg_settings['api_peram'], $customer_mobile, $smstext));
            }

            $this->save_response($user_id, $name, $sms_type, $customer_mobile, $response);
	    }
	}

	public function save_response($user_id, $name, $sms_type, $customer_mobile, $response)
	{
		if($response != "")
		{
			global $wpdb;
			$user_ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
			$table_woo_name = $wpdb->prefix . "safeguard_wooalert";
			$wpdb->insert(
	            $table_woo_name,
	            array(
	                'user_id' => $user_id,
	                'user_name' => sanitize_text_field($name),
	                'user_ip' => $user_ip,
	                'sms_type' => sanitize_text_field($sms_type),
	                'phone_no' => $customer_mobile,
	                'sms_ref_id' => sanitize_text_field(serialize($response))
	            )
	        );
		}
	}

}