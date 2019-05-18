<?php

namespace Drupal\gapi\Client;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\key\KeyInterface;
use \Google_Client;
use Psr\Log\LoggerInterface;

interface ClientAuthenticatorInterface {

  /**
   * Authenticates the given client instance using the given key.
   *
   * @param \Google_Client
   *   A newly instantiated client.
   * @param \Drupal\key\KeyInterface
   *   A key with which to authenticate the client.
   * @param \Drupal\Core\Config\ImmutableConfig
   *   The module configuration object.
   * @param \Psr\Log\LoggerInterface
   *   A logger with which to log any authentication failures.
   *
   * @return boolean
   *   Whether the client was succussfully authenticated.
   */
  public static function authenticate(Google_Client $client, KeyInterface $key, ImmutableConfig $config, LoggerInterface $logger);

}
