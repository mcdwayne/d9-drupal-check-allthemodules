<?php

namespace Drupal\gapi\Client;

use Drupal\Core\Config\ConfigFactory;
use Drupal\key\KeyRepositoryInterface;
use \Google_Client;
use Psr\Log\LoggerInterface;

class ClientFactory {

  /**
   * Map of authentication methods to authenticator classes.
   *
   * TODO: This should really be handled by a plugin manager.
   *
   * @var array
   */
  protected static $authenticators = [
    'developer_key' => DeveloperKeyAuthenticator::class,
    'application_credentials' => ApplicationCredentialsAuthenticator::class,
  ];

  /**
   * Creates a new ClientFactory.
   */
  public function __construct(ConfigFactory $config_factory, KeyRepositoryInterface $key_repository, LoggerInterface $logger) {
    $this->config = $config_factory->get('gapi.settings');
    $this->keyRepo = $key_repository;
    $this->logger = $logger;
  }

  /**
   * Creates an authenticated client instance.
   *
   * @return \Google_Client|FALSE
   *   An authenticated client or FALSE on failure.
   */
  public function getClient() {
    $client = new \Google_Client();
    $success = $this->authenticate($client);
    if (!$success) {
      $this->logger->notice("Unable to authenticate Google API Client");
    }
    return $client;
  }

  /**
   * Helper for authenticating a client with appropriate credentials.
   */
  protected function authenticate(\Google_Client $client) {
    $authentication_method = $this->config->get('authentication_method');

    if (empty($authentication_method)) {
      return FALSE;
    }

    try {
      $authenticator_class = static::$authenticators[$authentication_method];
      return $authenticator_class::authenticate(
        $client,
        $this->getKey($authentication_method),
        $this->config,
        $this->logger
      );
    }
    catch (\Exception $e) {
      $this->logger->notice($e->getMessage());
      return FALSE;
    }
  }

  /**
   * Loads a key based on the configured key id.
   */
  protected function getKey($config_key) {
    return $this->keyRepo->getKey($this->config->get($config_key));
  }

}
