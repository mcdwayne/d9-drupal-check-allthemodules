<?php

namespace Drupal\open_connect\Plugin\OpenConnect\Provider;

/**
 * Define the WeChat Media Platform identity provider.
 *
 * @OpenConnectProvider(
 *   id = "wechat_op",
 *   label = @Translation("WeChat OP"),
 *   description = @Translation("WeChat Open Platform"),
 *   homepage = "https://open.weixin.qq.com",
 *   urls = {
 *     "authorization" = "https://open.weixin.qq.com/connect/qrconnect",
 *     "access_token" = "https://api.weixin.qq.com/sns/oauth2/access_token",
 *     "user_info" = "https://api.weixin.qq.com/sns/userinfo",
 *   },
 *   keys = {
 *     "client_id" = "appid",
 *     "client_secret" = "secret",
 *   },
 * )
 */
class WeChatOP extends WeChatMP {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'scope' => 'snsapi_login',
    ] + parent::defaultConfiguration();
  }

}
