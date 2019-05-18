<?php

namespace Drupal\open_connect_test\Plugin\OpenConnect\Provider;

use Drupal\open_connect\Plugin\OpenConnect\Provider\ProviderBase;

/**
 * Define a Test identity provider.
 *
 * @OpenConnectProvider(
 *   id = "test_provider",
 *   label = @Translation("Test Provider"),
 *   homepage = "http://text.example.com",
 *   urls = {
 *     "authorization" = "https://test.example.com/oauth2/authorize",
 *     "access_token" = "https://test.example.com/oauth2/token",
 *     "openid" = "https://test.example.com/oauth2/openid",
 *     "user_info" = "https://test.example.com/userinfo",
 *   },
 * )
 */
class TestProvider extends ProviderBase {

  public $fetchTokenResponse;

  /**
   * {@inheritdoc}
   */
  public function doFetchToken($url, array $params) {
    if ($this->fetchTokenResponse) {
      return $this->fetchTokenResponse;
    }
    return [
      'access_token' => 'test_access_token',
      'expires_in' => 3600,
      'refresh_token' => 'test_refresh_token',
      'openid' => 'test_openid',
      'unionid' => 'test_unionid',
      'scope' => 'test scope',
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
