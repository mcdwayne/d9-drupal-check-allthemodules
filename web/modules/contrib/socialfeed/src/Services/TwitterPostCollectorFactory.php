<?php

namespace Drupal\socialfeed\Services;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class TwitterPostCollectorFactory.
 *
 * @package Drupal\socialfeed\Services
 */
class TwitterPostCollectorFactory {

  /**
   * Twitter application consumer key.
   *
   * @var string
   */
  protected $defaultConsumerKey;

  /**
   * Twitter application consumer secret.
   *
   * @var string
   */
  protected $defaultConsumerSecret;

  /**
   * Twitter application access token.
   *
   * @var string
   */
  protected $defaultAccessToken;

  /**
   * Twitter application access token secret.
   *
   * @var string
   */
  protected $defaultAccessTokenSecret;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $config = $configFactory->get('socialfeed.twittersettings');
    $this->defaultConsumerKey = $config->get('consumer_key');
    $this->defaultConsumerSecret = $config->get('consumer_secret');
    $this->defaultAccessToken = $config->get('access_token');
    $this->defaultAccessTokenSecret = $config->get('access_token_secret');
  }

  /**
   * Factory method for the TwitterPostCollector class.
   *
   * @param string $consumerKey
   *   $consumerKey.
   * @param string $consumerSecret
   *   $consumerSecret.
   * @param string $accessToken
   *   $accessToken.
   * @param string $accessTokenSecret
   *   $accessTokenSecret.
   *
   * @return \Drupal\socialfeed\Services\TwitterPostCollector
   *   TwitterPostCollector.
   */
  public function createInstance($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret) {
    return new TwitterPostCollector(
      $consumerKey ?: $this->defaultConsumerKey,
      $consumerSecret ?: $this->defaultConsumerSecret,
      $accessToken ?: $this->defaultAccessToken,
      $accessTokenSecret ?: $this->defaultAccessTokenSecret
    );
  }

}
