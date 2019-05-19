<?php

namespace Drupal\sample_data;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * A common interface to be extended by all sample data generators.
 *
 * @package Drupal\sample_data
 */
interface SampleDataGeneratorInterface extends PluginInspectionInterface {

  /**
   * A uniform execution function for all plugins to implement.
   *
   * @return mixed
   *   The expected result of the specific plugin implementation.
   */
  public function execute();

}
