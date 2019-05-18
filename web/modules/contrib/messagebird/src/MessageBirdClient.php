<?php

namespace Drupal\messagebird;

use Drupal\Core\Config\ConfigFactoryInterface;
use MessageBird\Client;

/**
 * Class MessageBirdClient.
 *
 * @package Drupal\messagebird
 */
class MessageBirdClient implements MessageBirdClientInterface {

  /**
   * MessageBird Configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * MessageBirdClient constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *    Configuration factory object.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('messagebird.settings');
  }

  /**
   * Get an authenticated Client connection with MessageBird.
   *
   * @param string|null $api_key
   *   (optional) The API-key supplied by MessageBird.
   *
   * @return \MessageBird\Client
   *   The Client object.
   */
  public function getClient($api_key = NULL) {
    if (is_null($api_key)) {
      $api_key = $this->config->get('api.key');
    }

    return new Client($api_key);
  }

}
