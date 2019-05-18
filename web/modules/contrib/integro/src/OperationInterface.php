<?php

namespace Drupal\integro;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an integration operations.
 */
interface OperationInterface extends PluginInspectionInterface, ConfigurablePluginInterface {

  /**
   * Executes the operation.
   *
   * @return array
   */
  public function execute();

}
