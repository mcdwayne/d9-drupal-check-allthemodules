<?php

namespace Drupal\simple_a_b;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for simple a/b test plugins.
 */
interface SimpleABReportingInterface extends PluginInspectionInterface {

  /**
   * Return the id of the report.
   */
  public function getId();

  /**
   * Return the name report.
   */
  public function getName();

  /**
   * Returns the reporting method.
   */
  public function getReportingMethod();

}
