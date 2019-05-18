<?php

/**
 * @file
 * Importing Oauth2 Server configuration for Desk-Net module.
 */

namespace Drupal\desk_net;

use Drupal\desk_net\Controller\ModuleSettings;

class DefaultModuleConfiguration {
  /**
   * Perform generate API credentials.
   *
   * @param bool $view_message
   *   The output message.
   */
  public static function generateApiCredentials($view_message = TRUE) {
    // Generate a unique ID.
    $drupal_desk_net_api_key = str_replace('.', '', uniqid('api_', TRUE));
    $drupal_desk_net_api_secret = str_replace('.', '', uniqid('', TRUE));

    // Generate new clients.
    ModuleSettings::variableSet('drupal_desk_net_api_key', $drupal_desk_net_api_key);
    ModuleSettings::variableSet('drupal_desk_net_api_secret', $drupal_desk_net_api_secret);

    DefaultModuleConfiguration::uploadClientsToOauth2Server();

    if ($view_message) {
      drupal_set_message(t('New credentials successfully generated.'), 'status');
    }
  }

  /**
   * Performs upload Desk-Net clients to oauth2 server.
   */
  public static function uploadClientsToOauth2Server() {
    global $base_url;
    // Import Desk-Net Oauth2 Server.
    $server = \Drupal::entityTypeManager()->getStorage('oauth2_server')->create([
      'server_id' => 'desk_net_module',
      'name' => 'Desk-Net',
      'settings' => [
        'default_scope' => 'desk_net_module_token',
        'enforce_state' => TRUE,
        'allow_implicit' => TRUE,
        'use_openid_connect' => FALSE,
        'use_crypto_tokens' => FALSE,
        'store_encrypted_token_string' => FALSE,
        'grant_types' => [
          'authorization_code' => 'authorization_code',
          'client_credentials' => 'client_credentials',
          'refresh_token' => 'refresh_token',
          'password' => 'password',
        ],
        'always_issue_new_refresh_token' => TRUE,
        'advanced_settings' => [
          'require_exact_redirect_uri' => TRUE,
          'access_lifetime' => 3600,
          'id_lifetime' => 3600,
          'refresh_token_lifetime' => 259200,
        ],
      ],
    ]);
    $server->save();

    // Import scope.
    $scope = \Drupal::entityTypeManager()->getStorage('oauth2_server_scope')->create([
      'scope_id' => 'token',
      'server_id' => $server->id(),
      'description' => 'Desk-Net Token',
    ]);
    $scope->save();

    // Create client.
    /** @var \Drupal\oauth2_server\ClientInterface $client */
    $client = \Drupal::entityTypeManager()->getStorage('oauth2_server_client')->create([
      'client_id' => ModuleSettings::variableGet('drupal_desk_net_api_key'),
      'server_id' => $server->id(),
      'name' => 'Desk-Net',
      'unhashed_client_secret' => ModuleSettings::variableGet('drupal_desk_net_api_secret'),
      'redirect_uri' => $base_url,
      'automatic_authorization' => FALSE,
    ]);
    $client->save();
  }
}
