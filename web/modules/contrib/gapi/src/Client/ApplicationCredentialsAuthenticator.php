<?php

namespace Drupal\gapi\Client;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\key\KeyInterface;
use \Google_Client;
use Psr\Log\LoggerInterface;

/**
 * Authenticates a Google_Client instance using service account credentials.
 */
class ApplicationCredentialsAuthenticator implements ClientAuthenticatorInterface {

  /**
   * {@inheritdoc}
   */
  public static function authenticate(Google_Client $client, KeyInterface $key, ImmutableConfig $config, LoggerInterface $logger) {
    try {
      $credentials = json_decode($key->getKeyValue(), TRUE);
      $client->setAuthConfig($credentials);
      $client->setAccessType('offline');
      $client->setScopes(explode(',', $config->get('application_scopes')));
    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      return FALSE;
    }
    return TRUE;
  }

}
