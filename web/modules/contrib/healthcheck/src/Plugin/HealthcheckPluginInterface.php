<?php

namespace Drupal\healthcheck\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines an interface for Healthcheck plugin plugins.
 */
interface HealthcheckPluginInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Do the check.
   *
   * @return array
   *   An array of Finding objects.
   */
  public function getFindings();

  /**
   * Gets the label of check.
   *
   * @return string
   */
  public function label();

  /**
   * Gets the tags of the check.
   *
   * @return array
   */
  public function getTags();

  /**
   * Gets the description of the check.
   *
   * @return string
   */
  public function getDescription();
}
