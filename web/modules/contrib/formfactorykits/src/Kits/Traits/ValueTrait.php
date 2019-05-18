<?php

namespace Drupal\formfactorykits\Kits\Traits;

/**
 * Trait ValueTrait
 *
 * @package Drupal\formfactorykits\Kits\Traits
 */
trait ValueTrait {
  use DefaultValueTrait;

  /**
   * @param mixed $default
   *
   * @return mixed
   */
  public function getValue($default = NULL) {
    return $this->get(static::VALUE_KEY, $default);
  }

  /**
   * @param mixed $value
   *
   * @return static
   */
  public function setValue($value) {
    return $this->set(static::VALUE_KEY, $value);
  }
}
