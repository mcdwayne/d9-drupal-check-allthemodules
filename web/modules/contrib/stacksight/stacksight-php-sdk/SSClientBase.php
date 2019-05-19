<?php

abstract class SSClientBase {

	private $_app_id = false;
	private $token;
	private $_platform;
	private $_group = false;
	private $request_curl;
	private $request_socket;
	private $request_thread;

	private $socket_limit = 4096;

	const GROUP_PLATFORM_SH = 'platform';
	const GROUP_HEROKU = 'heroku';

	const PLATFORM_MEAN = 'mean';
	const PLATFORM_DRUPAL = 'drupal';
	const PLATFORM_SYMFONY_2 = 'symfony2';
	const PLATFORM_MAGENTO_2 = 'magento2';
	const PLATFORM_WORDPRESS = 'wordpress';
	const PLATFORM_METEOR = 'meteor';
	const PLATFORM_NODEJS = 'nodejs';
	const PLATFORM_PHP = 'php';

	const DOMAIN_NOT_DETECT = 'DOMAIN_NOT_DETECT';

	private $curl_obj = array();

	public $stacksight_bot_name = 'Stacksight BOT';
	public $stacksight_bot_ico = 'https://s3-us-west-2.amazonaws.com/slack-files2/avatars/2015-08-26/9685505874_5c499cefd86aa883b7f4_68.jpg';

	public function __construct($token, $platform, $app_id = false, $group = false) {
		$this->token = $token;
		if($app_id)
			$this->_app_id = $app_id;

		if($group)
			$this->_group = $group;

		switch($platform){
			case self::PLATFORM_MEAN:
				$this->_platform = self::PLATFORM_MEAN;
				break;
			case self::PLATFORM_DRUPAL:
				$this->_platform = self::PLATFORM_DRUPAL;
				break;
			case self::PLATFORM_WORDPRESS:
				$this->_platform = self::PLATFORM_WORDPRESS;
				break;
			case self::PLATFORM_METEOR:
				$this->_platform = self::PLATFORM_METEOR;
				break;
			case self::PLATFORM_NODEJS:
				$this->_platform = self::PLATFORM_NODEJS;
				break;
			case self::PLATFORM_PHP:
				$this->_platform = self::PLATFORM_PHP;
				break;
			case self::PLATFORM_MAGENTO_2:
				$this->_platform = self::PLATFORM_MAGENTO_2;
				break;
			case self::PLATFORM_SYMFONY_2:
				$this->_platform = self::PLATFORM_SYMFONY_2;
				break;
			default:
				$this->_platform = self::PLATFORM_MEAN;
				break;
		}

		$this->request_curl = new SSHttpRequestCurl();
		$this->request_multicurl = new SSHttpRequestMultiCurl();
		$this->request_socket = new SSHttpRequestSockets();
		$this->request_thread = new SSHttpRequestThread();
	}

	public function publishEvent($data, $isMulticURL = false, $host = false) {
		$data['index'] = 'events';
		$data['eType'] = 'event';
		$this->_setAppParams($data, $host);
		if (!isset($data['created'])) $data['created'] = SSUtilities::timeJSFormat();
		if($isMulticURL == false) {
			if (strlen(json_encode($data)) > $this->socket_limit)
				$response = $this->request_curl->publishEvent($data);
			else
				$response = $this->request_socket->publishEvent($data);
			return $response;
		} else{
			$this->curl_obj[] = array(
				'type' => 'events',
				'data' => $data,
				'url' => false
			);
		}
	}

	public function sendLog($message, $level = 'log', $isMulticURL = false, $host = false) {
		$data['index'] = 'logs';
		$data['type'] = 'console';
		$data['eType'] = 'log';
		$data['method'] = $level;
		$data['content'] = $message;
		$this->_setAppParams($data, $host);
		if (!isset($data['created'])) $data['created'] = SSUtilities::timeJSFormat();
		if($isMulticURL == false){
			if (strlen(json_encode($data)) > $this->socket_limit)
				$response = $this->request_curl->sendLog($data);
			else
				$response = $this->request_socket->sendLog($data);
			return $response;
		} else{
			$this->curl_obj[] = array(
				'type' => 'logs',
				'data' => $data,
				'url' => false
			);
		}
	}

	public function sendSlackNotify($message, $type){
		$color = false;
		switch($type){
			case 'error':
				$pretext = "Error";
				$color = 'danger';
				break;
			case 'warn':
				$pretext = "Warning";
				$color = 'warning';
				break;
			default:
				$pretext = "Info";
				$color = '#28D7E5';
				break;
		}

		$data = array(
			"attachments" => array(
				array(
					"pretext" => SSUtilities::currentPageURL(),
//					"author_name" => $this->stacksight_bot_name,
//					"author_icon" => $this->stacksight_bot_ico,
					"text" => $message,
					"title" => $pretext,
					"color" => $color
				)
			)
		);
		if (strlen(json_encode($data)) > $this->socket_limit)
			$response = $this->request_curl->sendSlackNotify($data);
		else
			$response = $this->request_socket->sendSlackNotify($data);
	}

	public function sendUpdates($data, $isMulticURL = false, $host = false) {
		$this->_setAppParams($data, $host);
		if($isMulticURL == false){
			if (strlen(json_encode($data)) > $this->socket_limit)
				$response = $this->request_curl->sendUpdates($data);
			else
				$response = $this->request_socket->sendUpdates($data);
			return $response;
		} else{
			$this->curl_obj[] = array(
				'type' => 'updates',
				'data' => $data,
				'url' => SSHttpRequest::UPDATE_URL
			);
		}
	}

	public function sendHealth($data, $isMulticURL = false, $host = false) {
		$this->_setAppParams($data, $host);
		if($isMulticURL == false){
			if (strlen(json_encode($data)) > $this->socket_limit)
				$response = $this->request_curl->sendHealth($data);
			else
				$response = $this->request_socket->sendHealth($data);
			return $response;
		} else{
			$this->curl_obj[] = array(
				'type' => 'health',
				'data' => $data,
				'url' => SSHttpRequest::HEALTH_URL
			);
		}
	}

	public function sendInventory($data, $isMulticURL = false, $host = false) {
		$this->_setAppParams($data, $host);
		if($isMulticURL == false){
			if (strlen(json_encode($data)) > $this->socket_limit)
				$response = $this->request_curl->sendInventory($data);
			else
				$response = $this->request_socket->sendInventory($data);
			return $response;
		} else{
			$this->curl_obj[] = array(
				'type' => 'inventory',
				'data' => $data,
				'url' => SSHttpRequest::INVENTORY_URL
			);
		}
	}

	public function sendMultiCURL(){
		if(!empty($this->curl_obj)){
			foreach($this->curl_obj as $object){
				if((defined('STACKSIGHT_DEBUG') && STACKSIGHT_DEBUG === true) && defined('STACKSIGHT_DEBUG_MODE') && STACKSIGHT_DEBUG_MODE === true){
					$_SESSION['stacksight_debug'][$object['type']] = array();
					$data = array(
						'type' => $this->request_multicurl->type,
						'data' => $object['data']
					);
					$_SESSION['stacksight_debug'][$object['type']]['data'][] = $data;
				}
				$this->request_multicurl->addObject($object['data'], $object['url'], $object['type']);
			}
			$this->request_multicurl->sendRequest();
		}
	}

	private function _setAppParams(&$data = array(), $host = false){

		if($this->_app_id && (!isset($data['appId']) || empty($data['appId']))){
			$data['appId'] = $this->_app_id;
		}

        if(!isset($data['token']) || empty($data['token'])){
            $data['token'] = $this->token;
        }

		if(defined('STACKSIGHT_HTTP_HOST')){
			$data['domain'] = STACKSIGHT_HTTP_HOST;
		} else{
			if($host){
				$data['domain'] = $host;
			} else {
				if(isset($_SERVER['HTTP_HOST'])){
					$data['domain'] = $_SERVER['HTTP_HOST'];
				} elseif(isset($_SERVER['SERVER_NAME'])){
					$data['domain'] = $_SERVER['SERVER_NAME'];
				} else{
					$data['domain'] = self::DOMAIN_NOT_DETECT;
				}
			}
		}
		$data['platform'] = $this->_platform;

		if(getenv('PLATFORM_ENVIRONMENT')){
			$data['group'] = self::GROUP_PLATFORM_SH;
		}

		if(defined('STACKSIGHT_GROUP')){
			$data['group'] = STACKSIGHT_GROUP;
		}

		if($this->_group){
			$data['group'] = $this->_group;
		}
	}

}
