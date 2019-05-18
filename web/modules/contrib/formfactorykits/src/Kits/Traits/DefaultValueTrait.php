<?php

namespace Drupal\formfactorykits\Kits\Traits;

/**
 * Trait ValueTrait
 *
 * @package Drupal\formfactorykits\Kits\Traits
 */
trait DefaultValueTrait {
  /**
   * @param mixed $default
   *
   * @return mixed
   */
  public function getDefaultValue($default = NULL) {
    return $this->get(static::VALUE_DEFAULT_KEY, $default);
  }

  /**
   * @param mixed $value
   *
   * @return static
   */
  public function setDefaultValue($value) {
    return $this->set(static::VALUE_DEFAULT_KEY, $value);
  }
}
