<?php

namespace Drupal\wsdata\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Web Service Server entities.
 */
interface WSServerInterface extends ConfigEntityInterface {

  /**
   * Getter for Language Plugins.
   */
  public function getEnabledLanguagePlugin();

  /**
   * Set the endpoint.
   */
  public function setEndpoint($endpoint);

  /**
   * Get the endpoint.
   */
  public function getEndpoint();

  /**
   * Disabled the wsserver.
   */
  public function disable($degraded = FALSE);

  /**
   * Enable the wsserver.
   */
  public function enable($degraded = FALSE);

  /**
   * Check if wsserver is disabled.
   */
  public function isDisabled();

  /**
   * Cause the WSServer to become degraded.
   */
  public function getDegraded();

  /**
   * Return types of methods supported by the connector.
   */
  public function getMethods();

  /**
   * Return the default method if called.
   */
  public function getDefaultMethod();

}
