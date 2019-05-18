<?php

namespace Drupal\formfactorykits\Kits\Traits;

/**
 * Trait DescriptionTrait
 *
 * @package Drupal\formfactorykits\Kits\Traits
 */
trait DescriptionTrait {
  /**
   * @param mixed $default
   *
   * @return mixed
   */
  public function getDescription($default = NULL) {
    return $this->get(static::DESCRIPTION_KEY, $default);
  }

  /**
   * @param string $description
   *
   * @return static
   */
  public function setDescription($description) {
    return $this->set(static::DESCRIPTION_KEY, $description);
  }
}
