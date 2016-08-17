<?php

# Arvin Castro
# 9 September 2012
# http://codecri.me/sources/includes/class-xhttp-php/plugin-xhttp-rpc-php/

# Changelog
# 9 September 2012 - Added function attach, base64_encode

require_once 'class.xhttp.php';

class xhttp_rpc {

	public function __construct() {
		# oh, nothing
	}

	public function call($url, $method, $parameters, $options = array()) {
		$options = array_merge(array('verbosity'=>'no_white_space'), $options);
        $data['post'] = xmlrpc_encode_request($method, $parameters, $options);
        
        $data['post'] = str_replace(array('<string>&#60;base64&#62;','&#60;/base64&#62;</string>'), array('<base64>','</base64>'), $data['post']);
        
        $data['headers']['Content-Type'] = 'text/xml';
        $data['method'] = 'post';

        xhttp::addHookToRequest($data, 'data-preparation', array(__CLASS__, 'set_rpc_data'), 8);

        $response = xhttp::fetch($url, $data);

        $response['raw'] = $response['body'];
        $response['body'] = str_replace('i8>', 'i4>', $response['body']);
        $response['body'] = xmlrpc_decode($response['body']);

        if($response['body'] AND xmlrpc_is_fault($response['body'])) {
            $response['rpc_fault'] = $response['body']['faultString'];
            $response['rpc_fault_code'] = $response['body']['faultCode'];
        }
        return $response;
	}

	public function load() {
		return true;
	}

	# hook: data-preparation
	public static function set_rpc_data(&$urlparts, &$requestData) {
		$requestData['method'] = 'post';
		$requestData['curl'][CURLOPT_POSTFIELDS] = $requestData['post'];
		$requestData['post'] = null;
	}
	
	public static function attach($URI) {
		return self::base64_encode(file_get_contents($URI));
	}
	
	public static function base64_encode($data) {
		return '<base64>'.base64_encode($data).'</base64>';
	}
}

?>