<?php 
	#-----------------
	# Api requesting script.
	#-----------------
namespace Safeguard\Admin\Sms;

class Safeguard_Sms_Api
{

	############################### Process parameters for POST request ################################
	public static function set_post_parameter($string, $phone_number, $sms_text)
	{
	    $post_param = explode('&', $string);

	    $unique_id = uniqid();

	    $post_param=str_ireplace("{{phone_number}}", $phone_number, $post_param);
	    $post_param=str_ireplace("{{sms_text}}", $sms_text, $post_param);
	    $post_param=str_ireplace("{{unique_id}}", $unique_id, $post_param);

	    $param = explode("&", implode("&",$post_param));
	    $count_param = count($param);

	    $splited_data = array();
	    $post_data = array();

	    for ($i=0; $i < $count_param; $i++) { 
	         $splited_data[] = explode("=",$param[$i]);
	    }

	    $count_spl = count($splited_data);

	    for ($i=0; $i < $count_spl; $i++) { 
	        $post_data[$splited_data[$i][0]] = $splited_data[$i][1];
	    }

	    return $post_data;
	}

	############################### Process parameters for GET request ################################

	public static function set_get_parameter($string, $phone_number, $sms_text)
	{
	    $post_param = explode('&', $string);

	    $unique_id = uniqid();

	    $post_param=str_ireplace("{{phone_number}}", $phone_number, $post_param);
	    $post_param=str_ireplace("{{sms_text}}", urlencode($sms_text), $post_param);
	    $post_param=str_ireplace("{{unique_id}}", $unique_id, $post_param);

	    $param = implode("&",$post_param);
	    
	    return $param;
	}


	################################# Process API For GET REQUEST ##################################

	public static function call_to_get_api($apiurl, $peram)
	{
		$url = $apiurl."?".$peram;

		$response = wp_remote_post(
			$url,
			array(
				'method'      => 'GET',
				'timeout'     => 30,
				'redirection' => 10,
				'httpversion' => '1.1',
				'blocking'    => true,
				'headers'     => array(),
				'body'        => array(),
				'cookies'     => array(),
			)
		);

		if ( is_wp_error( $response ) ) 
		{
		   	$apiresponse = $response->get_error_message();
		} 
		else 
		{
		   	$apiresponse = array($response['response'], $response['body']);
		}

		return $apiresponse;
	}


	################################# Process API For POST REQUEST ##################################

	public static function call_to_post_api($apiurl, $post_data = array())
	{
		$response = wp_remote_post( $apiurl, array(
		    'method'      => 'POST',
			'timeout'     => 30,
			'redirection' => 10,
			'httpversion' => '1.1',
			'blocking'    => true,
			'headers'     => array(),
			'body'        => $post_data,
			'cookies'     => array(),
		    )
		);

		if ( is_wp_error( $response ) ) 
		{
		   	$apiresponse = $response->get_error_message();
		} 
		else 
		{
		   	$apiresponse = array($response['response'], $response['body']);
		}

		return $apiresponse;
	}

}