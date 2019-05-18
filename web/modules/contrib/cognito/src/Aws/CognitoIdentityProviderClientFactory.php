<?php

namespace Drupal\cognito\Aws;

use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Drupal\Core\Site\Settings;

/**
 * Factory to construct identity provider clients.
 */
class CognitoIdentityProviderClientFactory {

  /**
   * Creates a new client.
   *
   * @param \Drupal\Core\Site\Settings $settings
   *   The settings.
   *
   * @return \Aws\CognitoIdentityProvider\CognitoIdentityProviderClient
   *   The created client.
   */
  public static function create(Settings $settings) {
    $cognitoSettings = $settings->get('cognito');
    $settings = [
      'region' => $cognitoSettings['region'],
      'credentials' => $cognitoSettings['credentials'],
    ];
    return new CognitoIdentityProviderClient($settings + [
      'debug' => FALSE,
      'version' => '2016-04-18',
    ]);
  }

}
