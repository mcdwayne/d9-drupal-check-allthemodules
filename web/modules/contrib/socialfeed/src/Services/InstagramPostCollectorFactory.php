<?php

namespace Drupal\socialfeed\Services;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class InstagramPostCollectorFactory.
 *
 * @package Drupal\socialfeed
 */
class InstagramPostCollectorFactory {

  /**
   * Default Instagram application api key.
   *
   * @var string
   */
  protected $defaultApiKey;

  /**
   * Default Instagram application access token.
   *
   * @var string
   */
  protected $defaultAccessToken;

  /**
   * InstagramPostCollectorFactory constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $config = $configFactory->get('socialfeed.instagramsettings');
    $this->defaultApiKey = $config->get('client_id');
    $this->defaultAccessToken = $config->get('access_token');
  }

  /**
   * Factory method for the InstagramPostCollector class.
   *
   * @param string|null $apiKey
   *   $apiKey.
   * @param string|null $accessToken
   *   $accessToken.
   *
   * @return \Drupal\socialfeed\Services\InstagramPostCollector
   *   InstagramPostCollector.
   *
   * @throws \Exception
   */
  public function createInstance($apiKey, $accessToken) {
    return new InstagramPostCollector(
      $apiKey ?: $this->defaultApiKey,
      $accessToken ?: $this->defaultAccessToken
    );
  }

}
