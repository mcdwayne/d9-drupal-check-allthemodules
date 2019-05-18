<?php

namespace Drupal\formfactorykits\Kits\Traits;

/**
 * Trait MultipleTrait
 *
 * @package Drupal\formfactorykits\Kits\Traits
 */
trait MultipleTrait {
  /**
   * @param bool $isMultiple
   *
   * @return static
   */
  public function setMultiple($isMultiple = TRUE) {
    return $this->set(self::MULTIPLE_KEY, (bool) $isMultiple);
  }
}
