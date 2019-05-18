<?php

namespace Drupal\oauth2_client\Service;

use Drupal\oauth2_client\Exception\InvalidOauth2ClientException;

/**
 * Base class for OAuth2 Client services.
 */
abstract class Oauth2ClientServiceBase implements Oauth2ClientServiceInterface {

  /**
   * {@inheritdoc}
   */
  public function getClient($clientId) {
    $clients = &drupal_static(__CLASS__ . '::' . __FUNCTION__, []);
    if (!isset($clients[$clientId])) {
      $definition = $this->oauth2ClientPluginManager->getDefinition($clientId);
      if (!$definition || !isset($definition['id'])) {
        throw new InvalidOauth2ClientException($clientId);
      }

      $clients[$clientId] = $this->oauth2ClientPluginManager->createInstance($definition['id']);
    }

    return $clients[$clientId];
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveAccessToken($clientId) {
    return $this->state->get('oauth2_client_access_token-' . $clientId);
  }

  /**
   * {@inheritdoc}
   */
  public function clearAccessToken($clientId) {
    return $this->state->delete('oauth2_client_access_token-' . $clientId);
  }

}
