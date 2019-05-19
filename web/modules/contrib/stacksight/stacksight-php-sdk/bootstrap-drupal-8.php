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
require_once('platforms/SSDrupalClient.php');

define('DOCS_URL', 'http://stacksight.io/docs/#drupal-installation');

use Drupal\Core\Database\Database;

global $ss_client;

class DrupalBootstrap
{
    public $options = array(
        'stacksight.features',
        'stacksight.settings'
    );
    private $ready = false;
    private $connection;
    private $data_options = array();

    protected $ss_client;

    protected $database;

    private $root;

    public $defaultDefines = array(
        'STACKSIGHT_INCLUDE_LOGS' => false,
        'STACKSIGHT_INCLUDE_HEALTH' => true,
        'STACKSIGHT_INCLUDE_INVENTORY' => true,
        'STACKSIGHT_INCLUDE_EVENTS' => true,
        'STACKSIGHT_INCLUDE_UPDATES' => true
    );

    public function __construct($database){
        global $ss_client;
        $this->root = dirname(dirname(substr(__DIR__, 0, -strlen(__NAMESPACE__))));
        require_once DRUPAL_ROOT . '/core/includes/database.inc';
        Database::setMultipleConnectionInfo($database);
        $this->connection = Database::getConnection();
        $this->ss_client =& $ss_client;
        $this->database = $database;

        $query = db_select('config', 'n')->fields('n')->condition('name',$this->options, 'IN');
        $result = $query->execute();
        if($result && is_object($result)){
            foreach($result as $key => $row){
                $values = unserialize($row->data);
                foreach($values[key($values)] as $value_key => $value){
                    $this->data_options[$value_key] =  $value;
                }
            }
        }

        if(defined('STACKSIGHT_SETTINGS_IN_DB') && STACKSIGHT_SETTINGS_IN_DB === true){
            if (isset($this->data_options['token']) && isset($this->data_options['app_id'])) {
                $this->ready = true;
            }
        } else{
            $this->ready = true;
        }

        define('STACKSIGHT_PHP_SDK_INCLUDE', TRUE);
    }

    public function init(){
        if ($this->ready == true) {
            define('STACKSIGHT_SETTINGS_IN_DB', true);
            if(!empty($this->data_options) && is_array($this->data_options)){
                foreach($this->data_options as $key => $option_object){
                    $option = (isset($option_object) && !empty($option_object)) ? (bool) $option_object : false;
                    switch($key){
                        case 'app_id':
                            if(defined('STACKSIGHT_SETTINGS_IN_DB') && STACKSIGHT_SETTINGS_IN_DB === true) {
                                if (!defined('STACKSIGHT_APP_ID') && $option_object) {
                                    define('STACKSIGHT_APP_ID', $option_object);
                                }
                            }
                            break;
                        case 'token':
                            if(defined('STACKSIGHT_SETTINGS_IN_DB') && STACKSIGHT_SETTINGS_IN_DB === true) {
                                if (!defined('STACKSIGHT_TOKEN') && $option_object) {
                                    define('STACKSIGHT_TOKEN', $option_object);
                                }
                            }
                            break;
                        case 'include_logs':
                            if (!defined('STACKSIGHT_INCLUDE_LOGS') && $option) {
                                define('STACKSIGHT_INCLUDE_LOGS', $option);
                            }
                            break;
                        case 'include_health':
                            if (!defined('STACKSIGHT_INCLUDE_HEALTH')) {
                                define('STACKSIGHT_INCLUDE_HEALTH', $option);
                            }
                            break;
                        case 'include_inventory':
                            if (!defined('STACKSIGHT_INCLUDE_INVENTORY')) {
                                define('STACKSIGHT_INCLUDE_INVENTORY', $option);
                            }
                            break;
                        case 'include_events':
                            if (!defined('STACKSIGHT_INCLUDE_EVENTS')) {
                                define('STACKSIGHT_INCLUDE_EVENTS', $option);
                            }
                            break;
                        case 'include_updates':
                            if (!defined('STACKSIGHT_INCLUDE_UPDATES')) {
                                define('STACKSIGHT_INCLUDE_UPDATES', $option);
                            }
                            break;
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
                if(defined('STACKSIGHT_APP_ID'))
                    $this->ss_client = new SSDrupalClient(STACKSIGHT_TOKEN, SSClientBase::PLATFORM_DRUPAL, STACKSIGHT_APP_ID);
                else
                    $this->ss_client = new SSDrupalClient(STACKSIGHT_TOKEN, SSClientBase::PLATFORM_DRUPAL);

                $handle_errors = FALSE;
                $handle_fatal_errors = TRUE;
                if(defined('STACKSIGHT_INCLUDE_LOGS') && STACKSIGHT_INCLUDE_LOGS == true){
                    new SSLogsTracker($this->ss_client, $handle_errors, $handle_fatal_errors);
                }
                define('STACKSIGHT_BOOTSTRAPED', TRUE);
            }
        }
    }
}

$wp_stacksight = new DrupalBootstrap($databases);
$wp_stacksight->init();