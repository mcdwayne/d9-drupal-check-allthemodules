<?php

namespace Drupal\oLogin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;

Class oLoginWeixinController extends ControllerBase {

  public function main() {
    // Disable cache to allow dynamic redirect.
    \Drupal::service('page_cache_kill_switch')->trigger();

    $url = $this->get_auth_url();
    $response = new TrustedRedirectResponse($url, 303);
    return $response;
  }


  // Get authentication url.
  public function get_auth_url() {
    $uri = 'https://open.weixin.qq.com/connect/qrconnect';

    $appkey = \Drupal::config('ologin.settings')->get('ologin_weixin_appkey');
    $callback = \Drupal::config('ologin.settings')->get('ologin_weixin_callback');

    $options = array(
      'query' => [
        'appid'         => $appkey,
        'redirect_uri'  => $callback,
        'response_type' => 'code',
        'scope'         => 'snsapi_login',
      ]
    );
    $url = Url::fromUri($uri, $options)->toString();
    return $url;
  }

}