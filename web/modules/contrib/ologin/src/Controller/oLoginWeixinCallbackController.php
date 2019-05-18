<?php

namespace Drupal\oLogin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;

Class oLoginWeixinCallbackController extends ControllerBase {

  public function main() {
    // Disable cache to allow dynamic redirect.
    \Drupal::service('page_cache_kill_switch')->trigger();

    $output = '';

    if (!isset($_GET['code']) || empty($_GET['code'])) {
      $message = t('Error occurred, please retry later.');
      drupal_set_message($message, 'error');
      \Drupal::logger('ologin')->error(t('Missing argument: $_GET[\'code\']'));
      return array('#markup' => $message);
    }

    $code = $_GET['code'];

    $url = $this->get_access_token_url($code);
  
    // Get Access Token 
    $client = \Drupal::httpClient();
    $request = $client->request('POST', $url);
    $response = $request->getBody()->getContents();
    $data = Json::decode($response);

    // Error handle.
    if (isset($data['errcode'])) {
      $message = t('Error code: @errcode, Error: @errmsg', array('@errcode' => $data['errcode'], '@errmsg' => $data['errmsg']));
      drupal_set_message($message, 'error');
      \Drupal::logger('ologin')->error($message);
      return array('#markup' => $message);
    }

    // Login.
    if (isset($data['unionid']) || isset($data['openid'])) {
      $ouid = isset($data['unionid']) ? $data['unionid'] : $data['openid'];    
      ologin_login($ouid, 'weixin', $data);
    }

    return array('#markup' => $output);
  }


  private function get_access_token_url($code) {
    $uri = 'https://api.weixin.qq.com/sns/oauth2/access_token';

    $appid = \Drupal::config('ologin.settings')->get('ologin_weixin_appkey');
    $secret = \Drupal::config('ologin.settings')->get('ologin_weixin_appsecret');

    $options = array(
      'query' => [
        'appid'         => $appid,
        'secret'        => $secret,
        'grant_type'    => 'authorization_code',
        'code'          => $code,
      ]
    );
    $url = Url::fromUri($uri, $options)->toString();
    return $url;
  }

}