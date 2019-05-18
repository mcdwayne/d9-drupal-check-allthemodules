<?php

namespace Drupal\formfactorykits\Kits\Traits;

/**
 * Trait PatternTrait
 *
 * @package Drupal\formfactorykits\Kits\Traits
 */
trait PatternTrait {
  /**
   * @param string $pattern
   *
   * @return static
   */
  public function setPattern($pattern) {
    return $this->set(self::PATTERN_KEY, $pattern);
  }
}
