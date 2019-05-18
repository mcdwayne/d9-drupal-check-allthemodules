<?php

namespace Drupal\sdk_twitter\Plugin\Sdk;

use Drupal\sdk\SdkPluginBase;
use Abraham\TwitterOAuth\TwitterOAuth as TwitterSdk;

/**
 * SDK definition.
 *
 * @Sdk(
 *   id = "twitter",
 *   label = @Translation("Twitter"),
 * )
 */
class Twitter extends SdkPluginBase {

  /**
   * SDK instance.
   *
   * @var TwitterSdk
   */
  private $instance;

  /**
   * {@inheritdoc}
   */
  protected function getInstance() {
    if (NULL === $this->instance) {
      $config = $this->getConfig();

      $this->instance = new TwitterSdk(
        $config->settings['consumer_key'],
        $config->settings['consumer_secret'],
        $config->settings['access_key'],
        $config->settings['access_secret']
      );
    }

    return $this->instance;
  }

}
