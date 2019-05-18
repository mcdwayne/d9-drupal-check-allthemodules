<?php

namespace Drupal\preprocess;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface for preprocess plugins.
 *
 * @package Drupal\preprocess
 */
interface PreprocessInterface extends PluginInspectionInterface {

  /**
   * Preprocess function for this plugin.
   *
   * @param array $variables
   *   The variables for preprocessing.
   *
   * @return array
   *   The preprocessed variables.
   */
  public function preprocess(array $variables): array;

}
