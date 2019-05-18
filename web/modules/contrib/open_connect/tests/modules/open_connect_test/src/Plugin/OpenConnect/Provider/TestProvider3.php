<?php

namespace Drupal\open_connect_test\Plugin\OpenConnect\Provider;

use Drupal\open_connect\Plugin\OpenConnect\Provider\ProviderBase;

/**
 * Define a Test identity provider.
 *
 * @OpenConnectProvider(
 *   id = "test_provider3",
 *   label = @Translation("Test Provider 3"),
 *   homepage = "http://text3.example.com",
 *   urls = {
 *     "authorization" = "https://test3.example.com/oauth2/authorize",
 *     "access_token" = "https://test3.example.com/oauth2/token",
 *     "openid" = "https://test3.example.com/oauth2/openid",
 *     "user_info" = "https://test3.example.com/userinfo",
 *   },
 * )
 */
class TestProvider3 extends ProviderBase {

  public $fetchTokenResponse;

  /**
   * {@inheritdoc}
   */
  public function doFetchToken($url, array $params) {
    if ($this->fetchTokenResponse) {
      return $this->fetchTokenResponse;
    }
    return [
      'errcode' => 40029,
      'errmsg' => 'invalid code',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function isResponseSuccessful(array $response) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getResponseError(array $response) {
    return sprintf('%s: %s', $response['errcode'], $response['errmsg']);
  }

}
