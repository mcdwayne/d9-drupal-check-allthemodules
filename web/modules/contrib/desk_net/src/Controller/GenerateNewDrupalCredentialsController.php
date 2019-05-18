<?php

/**
 * @file
 * Generating new credentials for Desk-Net Oauth2 Clients.
 */

namespace Drupal\desk_net\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the Example module.
 */
class GenerateNewDrupalCredentialsController extends ControllerBase {

  /**
   * The generate new API credentials.
   */
  public function generateNewCredentials() {
    global $base_url;

    // Delete old Client.
    $oauth2_server_client = \Drupal::entityTypeManager()->getStorage('oauth2_server_client')->load(ModuleSettings::variableGet('drupal_desk_net_api_key'));
    if ($oauth2_server_client) {
      $oauth2_server_client->delete();
    }

    // Generate a unique ID.
    $drupal_desk_net_api_key = str_replace('.', '', uniqid('api_', TRUE));
    $drupal_desk_net_api_secret = str_replace('.', '', uniqid('', TRUE));

    // Generate new clients.
    ModuleSettings::variableSet('drupal_desk_net_api_key', $drupal_desk_net_api_key);
    ModuleSettings::variableSet('drupal_desk_net_api_secret', $drupal_desk_net_api_secret);

    // Load Desk-Net Oauth2 Server.
    $server = \Drupal::entityTypeManager()->getStorage('oauth2_server')->load('desk_net_module');
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

    return new \Symfony\Component\HttpFoundation\RedirectResponse(\Drupal\Core\Url::fromUserInput('/admin/config/desk-net?dn-credentials=generate')->toString());
  }
}