<?php

namespace Drupal\mattermost_integration\Services;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Class MattermostApiAccessToken.
 *
 * @package Drupal\mattermost_integration\Services
 */
class MattermostApiAccessToken implements MattermostApiAccessTokenInterface {

  protected $config;
  protected $httpClient;
  protected $cache;

  /**
   * MattermostApiAccessToken constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   HTTP client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache backend service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, ClientInterface $httpClient, CacheBackendInterface $cache) {
    $this->config = $configFactory->get('mattermost_integration.settings');
    $this->httpClient = $httpClient;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessToken($reset = FALSE) {
    if (!$reset && $cache = $this->cache->get('mattermost_integration:access_token')) {
      return $cache->data;
    }
    $token = $this->requestAccessToken();

    $expire = strtotime('TODAY +30 days');
    $this->cache->set('mattermost_integration:access_token', $token, $expire);
    return $token;
  }

  /**
   * Get the access token from Mattermost server.
   *
   * @return string
   *   With access token.
   *
   * @throws \Exception
   *   Error when token can't be loaded.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  protected function requestAccessToken() {
    $data = [
      'login_id' => $this->config->get('credentials.username'),
      'password' => $this->config->get('credentials.password'),
    ];
    $url = $this->config->get('api_url') . '/api/v3/users/login';

    $data = $this->httpClient->request('POST', $url, [
      'body' => Json::encode($data),
    ]);
    $token = $data->getHeader('TOKEN');

    if (empty($token)) {
      throw new \Exception('Unable to get the access token from Mattermost');
    }
    return reset($token);
  }

}
