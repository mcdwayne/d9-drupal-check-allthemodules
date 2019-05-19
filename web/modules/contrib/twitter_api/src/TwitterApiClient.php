<?php

namespace Drupal\twitter_api;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class TwitterApiClient.
 *
 * Drupal wrapper for TwitterAPIExchange.
 */
class TwitterApiClient implements TwitterApiClientInterface {

  /**
   * This module's configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * TwitterApiClient constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('twitter_api.settings');
  }

  /**
   * Gets an array of settings for passing to the exchange.
   *
   * @return array
   *   An array of settings.
   */
  protected function getClientSettings() {
    return [
      'oauth_access_token' => $this->config->get('oauth_access_token'),
      'oauth_access_token_secret' => $this->config->get('oauth_access_token_secret'),
      'consumer_key' => $this->config->get('consumer_key'),
      'consumer_secret' => $this->config->get('consumer_secret'),
    ];
  }

  /**
   * Returns the API url from config ensuring it has a trailing slash.
   *
   * @return string
   *   The api url.
   */
  protected function getApiUrl() {
    return rtrim($this->config->get('api_url'), ' /') . '/';
  }

  /**
   * {@inheritdoc}
   */
  public function doGet($end_point, array $query_params) {
    $exchange = new \TwitterAPIExchange($this->getClientSettings());
    $response = $exchange->setGetfield(http_build_query($query_params))
      ->buildOauth($this->getApiUrl() . $end_point, 'GET')
      ->performRequest();

    return Json::decode($response);
  }

  /**
   * {@inheritdoc}
   */
  public function getTweets(array $params) {
    return $this->doGet('statuses/user_timeline.json', $params);
  }

}
