<?php

namespace Drupal\gapi\Client;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\key\KeyInterface;
use \Google_Client;
use Psr\Log\LoggerInterface;

/**
 * Authenticates a Google_Client instance using service account credentials.
 */
class OAuthAuthenticator implements ClientAuthenticatorInterface {

  /**
   * {@inheritdoc}
   */
  public static function authenticate(Google_Client $client, KeyInterface $key, ImmutableConfig $config, LoggerInterface $logger) {
    $logger->error('The OAuth 2.0 for Webservers authentication method is not supported.');
    return FALSE;
  }

}
