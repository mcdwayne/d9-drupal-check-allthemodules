<?php

namespace Drupal\open_connect_test\Plugin\OpenConnect\Provider;

use Drupal\open_connect\Plugin\OpenConnect\Provider\ProviderBase;

/**
 * Define a Test identity provider.
 *
 * @OpenConnectProvider(
 *   id = "test_provider2",
 *   label = @Translation("Test Provider 2"),
 *   homepage = "http://text2.example.com",
 *   urls = {
 *     "authorization" = "https://test2.example.com/oauth2/authorize",
 *     "access_token" = "https://test2.example.com/oauth2/token",
 *     "openid" = "https://test2.example.com/oauth2/openid",
 *     "user_info" = "https://test2.example.com/userinfo",
 *   },
 * )
 */
class TestProvider2 extends ProviderBase {

  public $fetchTokenResponse;

  /**
   * {@inheritdoc}
   */
  public function doFetchToken($url, array $params) {
    if ($this->fetchTokenResponse) {
      return $this->fetchTokenResponse;
    }
    return [
      'access_token' => 'test_access_token2',
      'expires_in' => 3600,
      'refresh_token' => 'test_refresh_token2',
      'openid' => 'test_openid2',
      'unionid' => 'test_unionid2',
      'scope' => 'test scope2',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function isResponseSuccessful(array $response) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getResponseError(array $response) {
    return '';
  }

}
