<?php

namespace Drupal\integro;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Defines a plugin manager.
 */
interface IntegrationManagerInterface extends PluginManagerInterface {

  /**
   * Checks whether an integration is known.
   *
   * @param string $id
   *   The integration ID.
   *
   * @return bool
   */
  public function hasIntegration($id);

  /**
   * Gets a known integration.
   *
   * @param string $id
   *   The integration ID.
   *
   * @return \Drupal\integro\IntegrationInterface
   *
   * @throws \InvalidArgumentException
   *   Thrown if the integration is unknown.
   */
  public function getIntegration($id);

  /**
   * Gets the known integrations.
   *
   * @return \Drupal\integro\IntegrationInterface[]
   */
  public function getIntegrations();

  /**
   * Gets the known integrations as an array options.
   *
   * @return string[]
   */
  public function getOptions();

}
