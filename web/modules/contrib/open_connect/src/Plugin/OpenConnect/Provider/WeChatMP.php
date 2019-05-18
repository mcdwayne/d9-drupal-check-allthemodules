<?php

namespace Drupal\open_connect\Plugin\OpenConnect\Provider;

use Drupal\Core\Form\FormStateInterface;

/**
 * Define the WeChat Media Platform identity provider.
 *
 * @OpenConnectProvider(
 *   id = "wechat_mp",
 *   label = @Translation("WeChat MP"),
 *   description = @Translation("WeChat Media Platform"),
 *   homepage = "https://mp.weixin.qq.com",
 *   urls = {
 *     "authorization" = "https://open.weixin.qq.com/connect/oauth2/authorize",
 *     "access_token" = "https://api.weixin.qq.com/sns/oauth2/access_token",
 *     "user_info" = "https://api.weixin.qq.com/sns/userinfo",
 *   },
 *   keys = {
 *     "client_id" = "appid",
 *     "client_secret" = "secret",
 *   },
 * )
 */
class WeChatMP extends ProviderBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'scope' => 'snsapi_base',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    $values = $form_state->getValues();
    if (empty($values['scope'])) {
      $form_state->setError($form['scope'], 'Scope cannot be empty.');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function processRedirectUrlOptions(array &$options) {
    // The parameter orders are critical, see:
    // https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140842
    $client_id_key = $this->getKey('client_id');
    $param_orders = array_flip([
      $client_id_key,
      'redirect_uri',
      'response_type',
      'scope',
      'state',
    ]);
    $query = &$options['query'];
    uksort($query, function ($a, $b) use($param_orders) {
      return $param_orders[$a] - $param_orders[$b];
    });
    $options['fragment'] = 'wechat_redirect';
  }

  /**
   * {@inheritdoc}
   *
   * Response examples:
   *
   * success:
   * {
   *   "access_token": "ACCESS_TOKEN",
   *   "expires_in": 7200,
   *   "refresh_token": "REFRESH_TOKEN",
   *   "openid": "OPENID",
   *   "scope": "SCOPE"
   * }
   *
   * failure:
   * {
   *   "errcode": 40029,
   *   "errmsg": "invalid code"
   * }
   */
  protected function doFetchToken($url, array $params) {
    // Perform a get request.
    $response = $this->httpClient->get($url, [
      'query' => $params,
    ]);

    return \GuzzleHttp\json_decode($response->getBody(), TRUE);
  }

  /**
   * {@inheritdoc}
   *
   * Response examples:
   *
   * success:
   * {
   *   "openid":" OPENID",
   *   "nickname": NICKNAME,
   *   "sex":"1",
   *   "province":"PROVINCE"
   *   "city":"CITY",
   *   "country":"CN",
   *   "headimgurl": "http://wx.qlogo.cn/mmopen/g3MonUZtNHqxibJxCfHe/46",
   *   "privilege": ["PRIVILEGE1", "PRIVILEGE2"],
   *   "unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"
   * }
   *
   * failure:
   * {
   *   "errcode": 40003,
   *   "errmsg": "invalid openid"
   * }
   */
  protected function doFetchUserInfo($url, array $params) {
    // Add in the lang parameter, available languages:
    // - zh_CN: Chinese Simplified
    // - zh_TW: Chinese Traditional
    // - en: English
    $params['lang'] = 'zh_CN';
    $response = $this->httpClient->get($url, [
      'query' => $params,
    ]);
    return \GuzzleHttp\json_decode($response->getBody(), TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function isResponseSuccessful(array $response) {
    return empty($response['errcode']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getResponseError(array $response) {
    if (isset($response['errcode'], $response['errmsg'])) {
      return sprintf('%s: %s', $response['errcode'], $response['errmsg']);
    }
    return '';
  }

}
