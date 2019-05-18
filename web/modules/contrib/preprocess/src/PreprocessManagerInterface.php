<?php

namespace Drupal\preprocess;

/**
 * Interface definition for managing preprocessing.
 *
 * @package Drupal\preprocess
 */
interface PreprocessManagerInterface {

  /**
   * Preprocesses variables for a given hook.
   *
   * @param string $hook
   *   The preprocess hook.
   * @param array $variables
   *   The variables to preprocess.
   *
   * @return array
   *   The preprocessed variables.
   */
  public function preprocess(string $hook, array $variables): array;

}
