<?php

require_once('SSClientBase.php');
require_once('SSHttpRequest.php');
require_once('requests/SSHttpInterface.php');
require_once('requests/SSHttpRequestCurl.php');
require_once('requests/SSHttpRequestMultiCurl.php');
require_once('requests/SSHttpRequestSockets.php');
require_once('requests/SSHttpRequestThread.php');
require_once('SSLogsTracker.php');
require_once('SSUtilities.php');
require_once('platforms/SSWordpressClient.php');

define('DOCS_URL', 'http://stacksight.io/docs/#wordpress-installation');

class WPBootstrap{

	public $options = array('stacksight_opt', 'stacksight_opt_features', 'stacksight_state');

	private $multisite = false;
	private $blog_id = false;

	private $table_prefix = false;

	private $ready = false;

	private $connection;
	private $total_state = array();

	private $_mysqli_support = false;
	private $total_db = null;

	public $defaultDefines = array(
		'STACKSIGHT_INCLUDE_LOGS' => false,
		'STACKSIGHT_INCLUDE_HEALTH' => true,
		'STACKSIGHT_INCLUDE_INVENTORY' => true,
		'STACKSIGHT_INCLUDE_EVENTS' => true,
		'STACKSIGHT_INCLUDE_UPDATES' => true
	);

	const CONST_ENABLE_LOGS = 'logs';
	const CONST_ENABLE_INVENTORY = 'inventory';
	const CONST_ENABLE_HEALTH_SEO = 'health_seo';
	const CONST_ENABLE_HEALTH_SECURITY = 'health_security';
	const CONST_ENABLE_HEALTH_BACKUPS = 'health_backup';

	public function __construct($defined_prefix){
		if(defined('DB_NAME') && defined('DB_USER') && defined('DB_PASSWORD') && defined('DB_HOST')){
			if (function_exists('mysqli_connect')){
				$this->_mysqli_support = true;
			}
			$this->_initDB($defined_prefix);
		}
		if(file_exists(ABSPATH .'wp-content/plugins/aryo-activity-log/aryo-activity-log.php')){
			define('STACKSIGHT_DEPENDENCY_AAL', true);
		} else{
			// AAL doesn't exist
			define('STACKSIGHT_DEPENDENCY_AAL', false);
		}
		define('STACKSIGHT_PHP_SDK_INCLUDE', true);
	}

	public function init(){
		if($this->ready == true){
            define('STACKSIGHT_SETTINGS_IN_DB', true);
            $defines_from_db = $this->defineVars();
			if(is_array($defines_from_db) && !empty($defines_from_db)){
				foreach($defines_from_db as $key => $config_section){
					if(is_array($config_section) && !empty($config_section)){
//					General options
						if($key == 'stacksight_opt'){
							foreach($config_section as $key => $option){
								switch($key){
									case '_id':
										if(defined('STACKSIGHT_SETTINGS_IN_DB') && STACKSIGHT_SETTINGS_IN_DB === true) {
											if (!defined('STACKSIGHT_APP_ID') && $option) {
												define('STACKSIGHT_APP_ID', $option);
											}
										}
										break;
									case 'token':
										if(defined('STACKSIGHT_SETTINGS_IN_DB') && STACKSIGHT_SETTINGS_IN_DB === true) {
											if (!defined('STACKSIGHT_TOKEN') && $option) {
												define('STACKSIGHT_TOKEN', $option);
											}
										}
										break;
								}
							}
						}
//					Features integration options
						elseif($key == 'stacksight_opt_features'){
							foreach($config_section as $key => $option){
								switch($key){
									case 'include_logs':
										if(!defined('STACKSIGHT_INCLUDE_LOGS') && $option){
											define('STACKSIGHT_INCLUDE_LOGS', $option);
										}
										break;
									case 'include_health':
										if(!defined('STACKSIGHT_INCLUDE_HEALTH')){
											define('STACKSIGHT_INCLUDE_HEALTH', $option);
										}
										break;
									case 'include_inventory':
										if(!defined('STACKSIGHT_INCLUDE_INVENTORY')){
											define('STACKSIGHT_INCLUDE_INVENTORY', $option);
										}
										break;
									case 'include_events':
										if(!defined('STACKSIGHT_INCLUDE_EVENTS')){
											if(STACKSIGHT_DEPENDENCY_AAL === false){
												define('STACKSIGHT_INCLUDE_EVENTS', false);
											} else{
												define('STACKSIGHT_INCLUDE_EVENTS', $option);
											}
										}
										break;
									case 'include_updates':
										if(!defined('STACKSIGHT_INCLUDE_UPDATES')){
											define('STACKSIGHT_INCLUDE_UPDATES', $option);
										}
										break;
								}
							}
						}elseif($key == 'stacksight_state'){
							foreach($config_section as $key => $option){
								switch($key){
									case 'hash_of_state':
										define('STACKSIGHT_STATE_OF_HASH', $option);
										break;
									case 'date_of_set':
										define('STACKSIGHT_DATE_OF_HASH_SET', $option);
										break;
								}
							}
						}
					}
				}
			}

			// Define default values
			foreach($this->defaultDefines as $key => $default_define){
				if(!defined($key)){
					define($key, $default_define);
				}
			}

			if(defined('STACKSIGHT_TOKEN')){
				$app_id = (defined('STACKSIGHT_APP_ID')) ? STACKSIGHT_APP_ID : false;
//				Enable slack integration
				if(defined('STACKSIGHT_INCOMING_SLACK_URL') && (defined('STACKSIGHT_SLACK_NOTIFY_LOGS') && STACKSIGHT_SLACK_NOTIFY_LOGS == true) && defined('STACKSIGHT_SLACK_NOTIFY_LOGS_OPTIONS')){
					define('STACKSIGHT_SEND_TO_SLACK_EVENTS', STACKSIGHT_SLACK_NOTIFY_LOGS_OPTIONS);
				}

				$ss_client = new SSWordpressClient(STACKSIGHT_TOKEN, SSClientBase::PLATFORM_WORDPRESS, $app_id);
				if(defined('STACKSIGHT_INCLUDE_LOGS') && STACKSIGHT_INCLUDE_LOGS === true) {
					new SSLogsTracker($ss_client);
				}

				define('STACKSIGHT_BOOTSTRAPED', TRUE);
			}
		}
	}

	private function defineVars(){
		$results = array();
		$where = 'option_name IN ("'.implode('","', $this->options).'")';
		$sql = 'SELECT * FROM '.$this->table_prefix.'options WHERE '.$where;
		if ($query = $this->_query($sql)) {
			while ($row = $this->_result($query, 'fetch_array', (defined('MYSQLI_ASSOC') && $this->_mysqli_support) ? MYSQLI_ASSOC : MYSQL_ASSOC)) {
				$results[$row['option_name']] = unserialize($row['option_value']);
			}
		}
		return $results;
	}

	private function setBlogId($defined_prefix){
		if($this->is_multisite()){
			if($blog_id = $this->getBlogId($defined_prefix)){
				if($blog_id == 1)
					$this->table_prefix = $defined_prefix;
				else
					$this->table_prefix = $defined_prefix.$blog_id.'_';
			} else{
				$this->table_prefix = $defined_prefix;
			}
		} else{
			$this->table_prefix = $defined_prefix;
		}
	}

	private function getBlogId($defined_prefix){
		$dm_domain = $_SERVER[ 'HTTP_HOST' ];
		if( ( $nowww = preg_replace( '|^www\.|', '', $dm_domain ) ) != $dm_domain )
			$where = 'domain IN ("'.$dm_domain.'","'.$nowww.'")';
		else
			$where = 'domain = "'.$dm_domain.'"';

		$sql = "SELECT blog_id FROM ".$defined_prefix."blogs WHERE $where ORDER BY CHAR_LENGTH(domain) DESC LIMIT 1";
		if ($query =  $this->_query($sql)) {
			if($blog_id = $this->_result($query, 'result', 0)){
				return $blog_id;
			} else{
				$sql = "SELECT blog_id FROM ".$defined_prefix."domain_mapping WHERE $where ORDER BY CHAR_LENGTH(domain) DESC LIMIT 1";
				if ($query = $this->_query($sql)) {
					if($blog_id = $this->_result($query, 'result', 0)){
						return $blog_id;
					} else{
						// Domain not found
					}
				}
			}
		}
		return false;
	}

	private function is_multisite() {
		if ( defined( 'MULTISITE' ) )
			return MULTISITE;
		if ( defined( 'SUBDOMAIN_INSTALL' ) || defined( 'VHOST' ) || defined( 'SUNRISE' ) )
			return true;
		return false;
	}

	private function _initDB($defined_prefix){
		if($this->_mysqli_support){
			if($this->connection = @mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD)){
				if($this->total_db = @mysqli_select_db($this->connection, DB_NAME)){
					$this->setBlogId($defined_prefix);
					$this->ready = true;
				}
			}
		} else{
			if($this->connection = @mysql_connect(DB_HOST, DB_USER, DB_PASSWORD)){
				if($this->total_db = @mysql_select_db(DB_NAME, $this->connection)){
					$this->setBlogId($defined_prefix);
					$this->ready = true;
				}
			}
		}
	}

	private function _query($sql){
		if($this->_mysqli_support){
			$query = mysqli_query($this->connection, $sql);
		} else{
			$query = mysql_query($sql, $this->connection);
		}
		return $query;
	}

	private function _result($query, $type='result', $additional = false){
		switch($type){
			case 'result':
				if($this->_mysqli_support){
					$data = mysqli_fetch_array($query);
					if($additional !== false){
						$result = $data[$additional];
					} else{
						$result = array_shift(array_values($data));
					}
				} else{
					$result = mysql_result($query, ($additional !== false) ? $additional : NULL);
				}
				return $result;
				break;
			case 'fetch_array':
				if($this->_mysqli_support){
					$result = mysqli_fetch_array($query, ($additional !== false) ? $additional : NULL);
				} else{
					$result = mysql_fetch_array($query, ($additional !== false) ? $additional : NULL);
				}
				return $result;
				break;
		}
	}
}

$wp_stacksight = new WPBootstrap($table_prefix);
$wp_stacksight->init();