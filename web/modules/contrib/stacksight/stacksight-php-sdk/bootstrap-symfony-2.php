<?php
namespace {
    require_once('SSClientBase.php');
    require_once('SSHttpRequest.php');
    require_once('requests/SSHttpInterface.php');
    require_once('requests/SSHttpRequestCurl.php');
    require_once('requests/SSHttpRequestMultiCurl.php');
    require_once('requests/SSHttpRequestSockets.php');
    require_once('requests/SSHttpRequestThread.php');
    require_once('SSLogsTracker.php');
    require_once('SSUtilities.php');
    require_once('platforms/SSSymfony2Client.php');

    global $ss_client;

    define('DOCS_URL', 'http://stacksight.io/docs/#getting-started');

    class SymfonyBootstrap{
        protected $ss_client;

        public function __construct(){
            global $ss_client;
            $this->ss_client = & $ss_client;
            if(defined('STACKSIGHT_TOKEN')){
                if(defined('STACKSIGHT_APP_ID'))
                    $this->ss_client = new \SSSymfony2Client(STACKSIGHT_TOKEN, SSClientBase::PLATFORM_SYMFONY_2, STACKSIGHT_APP_ID);
                else
                    $this->ss_client = new \SSSymfony2Client(STACKSIGHT_TOKEN, SSClientBase::PLATFORM_SYMFONY_2);
                
                define('STACKSIGHT_BOOTSTRAPED', TRUE);
            }
        }

        public function getClient(){
            return $this->ss_client;
        }
    }
}

