<?php

namespace Drupal\integro\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for entity.
 */
interface ConnectorInterface extends ConfigEntityInterface {

  /**
   * Gets the client.
   *
   * @return \Drupal\integro\ClientInterface
   *   The client.
   */
  public function getClient();

  /**
   * Gets the client configuration.
   *
   * @return array
   *   The client configuration.
   */
  public function getClientConfiguration();

  /**
   * Gets the integration.
   *
   * @return \Drupal\integro\IntegrationInterface
   *   The integration.
   */
  public function getIntegration();

  /**
   * Authorizes the connector.
   *
   * @return mixed
   */
  public function auth();

  /**
   * Cleans up auth data.
   *
   * @return \Drupal\integro\Entity\ConnectorInterface
   */
  public function cleanupAuthData();

}
