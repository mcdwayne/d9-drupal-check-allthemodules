<?php

namespace Drupal\akismet\Client;

use Drupal\Core\Config\ConfigFactory;
use GuzzleHttp\ClientInterface;

/**
 * Class DrupalTestInvalid
 * @package Drupal\akismet\Client
 *
 * Drupal Akismet client implementation of an invalid server.
 */
class DrupalTestInvalid extends DrupalTestClient {
  private $currentAttempt = 0;
  private $originalServer;

  /**
   * Overrides AkismetDrupalTest::__construct().
   */
  public function __construct(ConfigFactory $config_factory, ClientInterface $http_client) {
    parent::__construct($config_factory, $http_client);
    $this->originalServer = $this->server;
    $this->configuration_map['server'] = 'test_mode.invalid.api_endpoint';
    $this->saveConfiguration('server', 'fake-host');
  }

  /**
   * Overrides AkismetDrupalTest::__query().
   */
  public function query($method, $path, $data, $authenticate = TRUE) {
    $this->currentAttempt = 0;
    return parent::query($method, $path, $data, $authenticate);
  }

  /**
   * Overrides Akismet::handleRequest().
   *
   * Akismet::$server is replaced with an invalid server, so all requests will
   * result in a network error. However, if the 'akismet_testing_server_failover'
   * variable is set to TRUE, then the last request attempt will succeed.
   */
  protected function handleRequest($method, $server, $path, $data) {
    $this->currentAttempt++;

    if (\Drupal::state()->get('akismet_testing_server_failover', FALSE) && $this->currentAttempt == $this->requestMaxAttempts) {
      $server = strtr($server, [$this->server => $this->originalServer]);
    }
    return parent::handleRequest($method, $server, $path, $data);
  }

}
