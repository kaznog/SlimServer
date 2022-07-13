<?php
namespace Utils;

use \Utils\OAuth\{OAuthConsumer, OAuthToken, OAuthRequest, OAuthSignatureMethodHMACSHA1};
// use \Utils\OAuthToken;
// use \Utils\OAuthRequest;
// use \Utils\OAuthSignatureMethod_HMAC_SHA1;
use \App\App;

class SqexBridgeRequest
{
	protected $url_base = null;
	protected $consumer = null;
	protected $access_token = null;
	protected $access_token_secret = null;
	protected $sqex_config = null;

	public function __construct()
	{
		$app = App::getInstance();
		$container = $app->getContainer();
		$sqex_config = $container['settings']['SQEX_GRIDGE'];
		$this->url_base            = $sqex_config['API_SERVER_URL_BASE'];
		$this->access_token        = md5($sqex_config['CONSUMER_KEY']);
		$this->access_token_secret = md5($sqex_config['CONSUMER_SECRET']);
		$this->consumer            = new OAuthConsumer($sqex_config['CONSUMER_KEY'], $sqex_config['CONSUMER_SECRET'], NULL);
		$this->sqex_config         = $sqex_config;
	}

	public function get($api, $uri_params, $params){
		return $this->_request($api, $uri_params, $params, null, 'GET');
	}

	public function create($api, $uri_params, $params, $requestbody){
		return $this->_request($api, $uri_params, $params, $requestbody, 'POST');
	}

	public function set($api, $uri_params, $params, $requestbody){
		return $this->_request($api, $uri_params, $params, $requestbody, 'PUT');
	}

	public function delete($api, $uri_params, $params){
		return $this->_request($api, $uri_params, $params, null, 'DELETE');
	}

	protected function _request($api, $uri_params, $params, $requestbody, $method){
		$url = $this->url_base.'/'.str_replace('.', '/', $api);
		if(isset($uri_params)){
			foreach($uri_params as $p){
				$url = $url.'/'.$p;
			}
		}
		if($method == 'POST' || $method == 'PUT'){
			if(isset($requestbody)){
				$requestbody = json_encode($requestbody);
				$params["oauth_body_hash"] = base64_encode(hash("SHA1", $requestbody, true));
			}
		}
		$params["xoauth_modelerrstr"] = 1; // For Debug
		$oauth_token = new OAuthToken($this->access_token, $this->access_token_secret);
		$request = OAuthRequest::from_consumer_and_token($this->consumer, $oauth_token, $method, $url, $params);
		$request->sign_request(new OAuthSignatureMethodHMACSHA1(), $this->consumer, $oauth_token);
		$url = $url.$this->_implode_assoc('=', '&', $params);

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FAILONERROR, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_ENCODING , "gzip");
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($curl, CURLOPT_TIMEOUT, (int)$this->sqex_config['BRIDGE_CONNECTION_TIMEOUT']);
		//curl_setopt($curl, CURLOPT_HEADER, 1);

		$http_headers = array();
		$auth_header = $request->to_header();
		if($auth_header) {
			$http_headers[] = $auth_header;
			curl_setopt($curl, CURLOPT_HTTPHEADER, array($auth_header));
		}
		if($method == 'POST' || $method == 'PUT'){
			if(isset($requestbody)){
				$http_headers[] = "Content-Type: application/json; charset=utf8";
				curl_setopt ($curl,CURLOPT_POSTFIELDS, $requestbody);
			}
		}
		if(isset($http_headers) && isset($http_headers[0])){
			curl_setopt($curl, CURLOPT_HTTPHEADER, $http_headers);
		}

		$response = curl_exec($curl);
		$json = null;
		if(!curl_errno($curl)){
			$json = json_decode($response, $assoc = TRUE);
			if(!isset($json)){
				$json = $response;
			}
		}else{
			$json = curl_error($curl);
		}
		$status = curl_getinfo($curl);
		curl_close($curl);

		$response = array(
			'url' => $url,
			'auth_header' => $auth_header,
			'responseBody' => $json,
			'statusCode' => $status["http_code"]
		);
		return $response;
	}

	function _implode_assoc($inner_glue, $outer_glue, $array) {
		$output = array();
		foreach($array as $key => $item) {
			if(preg_match("/^x?oauth_+/", $key)){
				continue;
			}
			$output[] = $key . $inner_glue . urlencode($item);
		}
		if(isset($output) && isset($output[0])){
			return '?'.implode($outer_glue, $output);
		}
		return '';
	}
}