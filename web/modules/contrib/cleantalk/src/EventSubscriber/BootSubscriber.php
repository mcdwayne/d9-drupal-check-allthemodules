<?php

namespace Drupal\cleantalk\EventSubscriber;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Drupal\cleantalk\CleantalkSFW;
use Drupal\cleantalk\CleantalkFuncs;
use \Drupal\Component\Utility\Html;

class BootSubscriber implements HttpKernelInterface  {

  /**
   * The wrapped HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */

  protected $httpKernel;

  /**
   * Creates a HTTP middleware handler.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $kernel
   *   The HTTP kernel.
   */

  public function __construct(HttpKernelInterface $kernel) {

    $this->httpKernel = $kernel;

  }

  /**
   * {@inheritdoc}
   */

  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    
    if (strpos($request->server->get('REQUEST_URI'), '/admin/') === false)
    {
      // Cookie names to validate

      $cookie_test_value = array(
          'cookies_names' => array(),
          'check_value' => trim(\Drupal::config('cleantalk.settings')->get('cleantalk_authkey')),
      );  

      // Submit time

      $apbct_timestamp = time();
      setcookie('apbct_timestamp', $apbct_timestamp, 0, '/');
      $cookie_test_value['cookies_names'][] = 'apbct_timestamp';
      $cookie_test_value['check_value'] .= $apbct_timestamp;

      // Pervious referer

      if($request->server->get('HTTP_REFERER')) {

        setcookie('apbct_prev_referer', $request->server->get('HTTP_REFERER'), 0, '/');
        $cookie_test_value['cookies_names'][] = 'apbct_prev_referer';
        $cookie_test_value['check_value'] .= $request->server->get('HTTP_REFERER');

      }
      // Remote calls
      
      if(isset($_GET['spbc_remote_call_token'], $_GET['spbc_remote_call_action'], $_GET['plugin_name']) && in_array($_GET['plugin_name'], array('antispam','anti-spam', 'apbct'))) {

          CleantalkFuncs::_cleantalk_apbct_remote_call__perform();

      }    
       // Cookies test

      $cookie_test_value['check_value'] = md5($cookie_test_value['check_value']);
      setcookie('apbct_cookies_test', json_encode($cookie_test_value), 0, '/');

      if (\Drupal::config('cleantalk.settings')->get('cleantalk_sfw') == 1) {  

        $sfw = new CleantalkSFW();
        $ct_key = trim(\Drupal::config('cleantalk.settings')->get('cleantalk_authkey'));
        
        if($ct_key != '') {  

          if(time() - \Drupal::state()->get('cleantalk_sfw_last_send_log') > 3600) {

            $sfw->send_logs($ct_key);
            \Drupal::state()->set('cleantalk_sfw_last_send_log',time());   

          }
          
          if(time() - \Drupal::state()->get('cleantalk_sfw_last_check') > 86400) {

            $sfw->sfw_update($ct_key);
            \Drupal::state()->set('cleantalk_sfw_last_check',time());

          }

          $is_sfw_check = true;
          $sfw->ip_array = (array)CleantalkSFW::ip_get(array('real'), true);  

          foreach($sfw->ip_array as $key => $value) {

            if($request->cookies->get('apbct_sfw_pass_key') == md5($value . trim($ct_key))) {

              $is_sfw_check=false;

              if(isset($_COOKIE['apbct_sfw_passed'])) {

                @setcookie ('apbct_sfw_passed'); 
                $sfw->sfw_update_logs($value, 'passed');

              }

            }

          }

          unset($key, $value);  

          if($is_sfw_check) {

            $sfw->check_ip();

            if($sfw->result) {

              $sfw->sfw_update_logs($sfw->blocked_ip, 'blocked');
              $sfw->sfw_die(trim($ct_key));

            }

          } 

        }

      }

      //Custom Contact forms

      if (sizeof($_POST) > 0 && !$request->get('form_build_id') && !$request->get('form_id') && \Drupal::config('cleantalk.settings')->get('cleantalk_check_ccf') == 1) {

        $ct_temp_msg_data = CleantalkFuncs::_cleantalk_get_fields_any($request->all());
        $spam_check = array();
        $spam_check['type'] = 'custom_contact_form';
        $spam_check['sender_email'] = ($ct_temp_msg_data['email']    ? $ct_temp_msg_data['email']    : '');
        $spam_check['sender_nickname'] = ($ct_temp_msg_data['nickname'] ? $ct_temp_msg_data['nickname'] : '');
        $spam_check['message_title'] = ($ct_temp_msg_data['subject']  ? $ct_temp_msg_data['subject']  : '');
        $spam_check['message_body'] = ($ct_temp_msg_data['message']  ? implode("\n", $ct_temp_msg_data['message'])  : '');

        if ($spam_check['sender_email'] != '' || $spam_check['message_title'] != '' || $spam_check['message_body'] != '') {

          $result = CleantalkFuncs::_cleantalk_check_spam($spam_check);

          if (isset($spam_result) && is_array($spam_result) && $spam_result['errno'] == 0 && $spam_result['allow'] != 1) {

            drupal_set_message(HTML::escape($result['ct_result_comment']), 'error');

          }

        }

      } 
    }


    return $this->httpKernel->handle($request, $type, $catch);

  }
      
}
