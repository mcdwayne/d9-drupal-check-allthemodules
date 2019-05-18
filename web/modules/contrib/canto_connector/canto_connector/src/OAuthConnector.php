<?php
namespace Drupal\canto_connector;

class OAuthConnector {
    protected $appIds;
    
    
    public function __construct()
    {
    }
   
    
	private function obtainUserInfo(string $subDomain, string $accessToken): string{
		// https://yourdomain.cantoflight.com/api/v1/user
		$url = 'https://' . $subDomain . '/api/v1/user';
		$header = array(
			'Authorization: Bearer ' . $accessToken,
		);

		$ch = curl_init();
		$options = array(
			CURLOPT_URL => $url,
			CURLOPT_REFERER => 'Universal Connector',
			CURLOPT_USERAGENT => 'Universal Connector',
			// CURLOPT_SSL_VERIFYHOST => 0,
			// CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_HEADER => 0,
			CURLOPT_HTTPHEADER => $header,
			CURLOPT_RETURNTRANSFER => 1, // this decide return response body directly and not echo to console
			CURLOPT_TIMEOUT => 10,
			CURLOPT_POST => 0,
		);

		curl_setopt_array($ch, $options);
		$response = curl_exec($ch);
		$curl_info = curl_getinfo($ch);
		curl_close($ch);

		//get header and body
		// $header_size = $curl_info['header_size']; // 'http_code'
		// $header = substr($response, 0, $header_size);
		// $body = substr($response, $header_size);

		if ($curl_info['http_code'] == 200) {

			return json_encode(array(
				"error" => 0,
				"user" => $response,
			));
		} else {

			$error = array(
				"error" => 1,
				"error_code" => $curl_info['http_code'],
			);
			return json_encode($error);
		}
	}

	public function checkAccessTokenValid(string $subDomain, string $accessToken): bool{
		$userInfoStr = $this->obtainUserInfo($subDomain, $accessToken);

		// convert to array
		$userInfoArray = json_decode($userInfoStr, true);

		if ($userInfoArray['error'] == 0) {
			return true;
		} else {
			return false;
		}

	}
}
