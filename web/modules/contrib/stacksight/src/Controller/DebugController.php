<?php
namespace Drupal\stacksight\Controller;
use Drupal\Core\Controller\ControllerBase;

require_once(dirname(__FILE__) . '/../../stacksight-php-sdk/SSHttpRequest.php');

class DebugController extends ControllerBase
{
    protected $tempStore;
    // Pass the dependency to the object constructor
    public function __construct() {
        $this->tempStore = \Drupal::service('user.private_tempstore')->get('stacksight');
        $this->tempStore->set('stacksight', []);

        $twig = \Drupal::service('twig');
        $function = new \Twig_SimpleFunction('curl_info', function ($key_request) {
            if($result = \SSUtilities::getCurlDescription($key_request)){
                return$result;
            } else{
                return '';
            }

        });

        $filter = new \Twig_SimpleFunction('curl_filter', function ($key_request) {
            return in_array($key_request, \SSUtilities::getCurlInfoFields());
        });
        $twig->addFunction($function);
        $twig->addFunction($filter);
    }

    public function debug() {
        if(defined('STACKSIGHT_DEBUG') && STACKSIGHT_DEBUG === true){
            $this->tempStore->set('stacksight_debug', false);
            stacksight_cron();
            if(isset($_SESSION['stacksight_debug'])){
                $this->tempStore->set('stacksight_debug', $_SESSION['stacksight_debug']);
            }
            $_SESSION['stacksight_debug'] = [];
        }
        if((defined('STACKSIGHT_DEBUG') && STACKSIGHT_DEBUG === true) && defined('STACKSIGHT_DEBUG_MODE') && STACKSIGHT_DEBUG_MODE === true) {
            return array('debug' => array(
                '#theme' => 'debug_page',
                '#session' => $this->tempStore->get('stacksight_debug'),
            ));
        } else{
            return array();
        }
    }
}
