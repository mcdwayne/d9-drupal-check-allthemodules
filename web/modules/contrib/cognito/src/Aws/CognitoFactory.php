<?php

namespace Drupal\cognito\Aws;

use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Drupal\Core\Site\Settings;
use GuzzleHttp\Client;

/**
 * Constructs cognito services.
 */
class CognitoFactory {

  /**
   * {@inheritdoc}
   */
  public static function create(Settings $settings, CognitoIdentityProviderClient $client, Client $httpClient) {
    $cognito = $settings->get('cognito');
    return new Cognito(
      $client,
      $cognito['client_id'],
      $cognito['user_pool_id'],
      $httpClient
    );
  }

}
