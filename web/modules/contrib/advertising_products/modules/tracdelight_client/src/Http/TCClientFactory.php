<?php

/**
 * @file
 * Contains \Drupal\tracdelight_client\Http\TCClientFactory.
 */

namespace Drupal\tracdelight_client\Http;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Site\Settings;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

/**
 * Helper class to construct a HTTP client with Drupal specific config.
 */
class TCClientFactory {

  /**
   * The handler stack.
   *
   * @var \GuzzleHttp\HandlerStack
   */
  protected $stack;

  /**
   * Constructs a new ClientFactory instance.
   *
   * @param \GuzzleHttp\HandlerStack $stack
   *   The handler stack.
   */
  public function __construct(HandlerStack $stack) {
    $this->stack = $stack;
  }

  /**
   * Constructs a new client object from some configuration.
   *
   * @param array $config
   *   The config for the client.
   *
   * @return \GuzzleHttp\Client
   *   The HTTP client.
   */
  public function fromOptions(array $config = []) {
    $client_config = \Drupal::config('tracdelight_client.config');
    $tracdelight_api = $client_config->get('api');
    if (!$tracdelight_api) {
      $tracdelight_api = 'https://api.tracdelight.com/v1/';
    }
    $default_config = [
      // Security consideration: we must not use the certificate authority
      // file shipped with Guzzle because it can easily get outdated if a
      // certificate authority is hacked. Instead, we rely on the certificate
      // authority file provided by the operating system which is more likely
      // going to be updated in a timely fashion. This overrides the default
      // path to the pem file bundled with Guzzle.
      'verify' => TRUE,
      'timeout' => 60,
      'headers' => [
        'User-Agent' => 'Drupal/' . \Drupal::VERSION . ' (+https://www.drupal.org/) ' . \GuzzleHttp\default_user_agent(),
      ],
      'handler' => $this->stack,
      'version' => 1.0,
      'http_errors' => TRUE,
      'base_uri' => $tracdelight_api,
    ];

    $config = NestedArray::mergeDeep($default_config, Settings::get('http_client_config', []), $config);

    return new Client($config);
  }

}
