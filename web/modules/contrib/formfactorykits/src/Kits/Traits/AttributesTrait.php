<?php

namespace Drupal\formfactorykits\Kits\Traits;

/**
 * Trait AttributesTrait
 *
 * @package Drupal\formfactorykits\Kits\Traits
 */
trait AttributesTrait {
  /**
   * @return array
   */
  public function getAttributes() {
    return $this->get(self::ATTRIBUTES_KEY, []);
  }

  /**
   * @param array $attributes
   *
   * @return static
   */
  public function setAttributes(array $attributes) {
    return $this->set(self::ATTRIBUTES_KEY, $attributes);
  }

  /**
   * @param string $name
   * @return array|string|null
   */
  public function getAttribute($name) {
    $attributes = $this->getAttributes();
    return isset($attributes[$name]) ? $attributes[$name] : NULL;
  }

  /**
   * @param string $name
   * @param mixed $value
   *
   * @return static
   */
  public function setAttribute($name, $value) {
    $attributes = $this->getAttributes();
    $attributes[$name] = $value;
    return $this->setAttributes($attributes);
  }
}
