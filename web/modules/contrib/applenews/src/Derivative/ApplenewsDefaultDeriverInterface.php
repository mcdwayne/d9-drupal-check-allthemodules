<?php

namespace Drupal\applenews\Derivative;

/**
 * Interface ApplenewsDefaultDeriverInterface.
 *
 * @package Drupal\applenews\Derivative
 */
interface ApplenewsDefaultDeriverInterface {

  /**
   * Get the list of Apple News component types.
   *
   * Retrieves compontent types with their underlying class from
   * the AppleNews API.
   *
   * @see https://github.com/chapter-three/AppleNewsAPI
   *
   * @return array
   *   An array keyed by the "role" of the Apple News component, and containing
   *   the following:
   *    - component_class - the fully-qualified name of the AppleNewsAPI class
   *    - label
   *    - description
   */
  public function getComponentClasses();

}
