<?php

namespace Drupal\simple_a_b;

use Drupal\Component\Plugin\PluginBase;

/**
 * Defines an plugin base for reporting.
 */
class SimpleABReportingBase extends PluginBase implements SimpleABReportingInterface {

  /**
   * Return the name of the reporting type.
   *
   * @return string
   *   Return id
   */
  public function getId() {
    return $this->pluginDefinition['id'];
  }

  /**
   * Return the name of the reporting type.
   *
   * @return string
   *   Return name
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * Returns the reporting method.
   *
   * @return mixed
   *   Returns reporting method.
   */
  public function getReportingMethod() {
    return $this->pluginDefinition['method'];
  }

}
