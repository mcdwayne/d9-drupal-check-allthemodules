<?php

namespace Drupal\formfactorykits\Kits\Traits;

/**
 * Trait TitleTrait
 *
 * @package Drupal\formfactorykits\Kits\Traits
 */
trait TitleTrait {
  /**
   * @param null $default
   *
   * @return string|null
   */
  public function getTitle($default = NULL) {
    return $this->get(self::TITLE_KEY, $default);
  }

  /**
   * @param string $title
   *
   * @return static
   */
  public function setTitle($title) {
    return $this->set(self::TITLE_KEY, $title);
  }
}
