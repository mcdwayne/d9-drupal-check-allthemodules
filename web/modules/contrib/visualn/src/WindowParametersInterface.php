<?php

namespace Drupal\visualn;

/**
 * Provides an interface for classes with drawing window parameters support.
 *
 * @ingroup ckeditor_integration
 */
interface WindowParametersInterface {

  /**
   * Get drawing window parameters.
   *
   * @return array
   *   An array of window parameters for the current drawing.
   *
   * @see \Drupal\visualn\WindowParametersTrait
   */
  public function getWindowParameters();

  /**
   * Set drawing window parameters.
   *
   * @param array $window_parameters
   *   An array of window parameters for the current drawing.
   *
   * @return $this
   *
   * @see \Drupal\visualn\WindowParametersTrait
   */
  public function setWindowParameters(array $window_parameters);

  /**
   * Clean window_parameters values.
   */
  public function cleanWindowParameters();

}
