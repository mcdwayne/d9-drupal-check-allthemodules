<?php

namespace Drupal\environmental_config;

/**
 * Interface EnvironmentDetectorInterface.
 *
 * @package Drupal\environmental_config
 */
interface EnvironmentDetectorInterface {

  /**
   * Returns a string with an environment name or null.
   *
   * @param string $arg
   *   An optional argument.
   *
   * @return string|null
   *   The environment name.
   */
  public function getEnvironment($arg = NULL);

  /**
   * Returns the weight of this plugin as a signed integer.
   *
   * @return int
   *   The weight.
   */
  public function getWeight();

}
