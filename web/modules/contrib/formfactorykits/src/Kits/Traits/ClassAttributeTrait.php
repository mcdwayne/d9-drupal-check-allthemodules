<?php

namespace Drupal\formfactorykits\Kits\Traits;

/**
 * Trait ClassAttributeTrait
 *
 * @package Drupal\formfactorykits\Kits\Traits
 */
trait ClassAttributeTrait {
  use AttributesTrait;

  /**
   * @param $class
   *
   * @return static
   */
  public function appendClass($class) {
    $classes = $this->getClasses();
    $classes[] = $class;
    return $this->setAttribute('class', $classes);
  }

  /**
   * @return array
   */
  public function getClasses() {
    return $this->getAttribute('class');
  }

  /**
   * @return static
   */
  public function clearClasses() {
    return $this->setAttribute('class', []);
  }
}
