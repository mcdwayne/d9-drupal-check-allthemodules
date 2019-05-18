<?php

namespace Drupal\open_connect\Plugin\OpenConnect\Provider;

/**
 * Define the Weibo identity provider.
 *
 * @OpenConnectProvider(
 *   id = "weibo",
 *   label = @Translation("Weibo"),
 *   description = @Translation("Weibo Open Platform"),
 *   homepage = "http://open.weibo.com",
 *   urls = {
 *     "authorization" = "https://api.weibo.com/oauth2/authorize",
 *     "access_token" = "https://api.weibo.com/oauth2/access_token",
 *     "user_info" = "https://api.weibo.com/2/users/show.json",
 *   },
 *   keys = {
 *     "openid" = "uid",
 *   },
 * )
 */
class Weibo extends ProviderBase {

//  /**
//   * {@inheritdoc}
//   */
//  protected function processRedirectUrlOptions(array &$options) {
//    // (Optional) Add in the display parameter, available values:
//    // - default
//    // - popup
//    // - mobile
//    // - js
//    // - apponweibo
//    // $options['query']['display'] = 'mobile';
//  }

  /**
   * {@inheritdoc}
   *
   * Response examples
   *
   * success (get access token):
   * {
   *   "access_token": "ACCESS_TOKEN",
   *   "expires_in": 1234,
   *   "remind_in":"798114",
   *   "uid":"12341234"
   * }
   *
   * failure:
   * {
   *   "error": "invalid_client",
   *   "error_code": 21324,
   *   "request": "/oauth2/access_token",
   *   ...
   * }
   */
  protected function isResponseSuccessful(array $response) {
    return empty($response['error']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getResponseError(array $response) {
    if (isset($response['error_code'], $response['error'])) {
      return sprintf('%s: %s', $response['error_code'], $response['error']);
    }
  }

}
