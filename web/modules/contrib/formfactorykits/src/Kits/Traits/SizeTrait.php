<?php

namespace Drupal\formfactorykits\Kits\Traits;

/**
 * Trait SizeTrait
 *
 * @package Drupal\formfactorykits\Kits\Traits
 */
trait SizeTrait {
  /**
   * @param int $size
   *
   * @return static
   */
  public function setSize($size) {
    return $this->set(self::SIZE_KEY, (int) $size);
  }
}
