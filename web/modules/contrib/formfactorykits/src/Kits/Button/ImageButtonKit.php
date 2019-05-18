<?php

namespace Drupal\formfactorykits\Kits\Button;

use Drupal\formfactorykits\Kits\Traits\AttributesTrait;

/**
 * Class ImageButtonKit
 *
 * @package Drupal\formfactorykits\Kits\Button
 */
class ImageButtonKit extends ButtonKit {
  use AttributesTrait;
  const ID = 'image_button';
  const TYPE = 'image_button';
  const SOURCE_KEY = 'src';

  /**
   * @param string $title
   *
   * @return static
   */
  public function setAlternativeText($title) {
    return $this->setAttribute('alt', $title);
  }

  /**
   * @param string $source
   *
   * @return static
   */
  public function setSource($source) {
    return $this->set(self::SOURCE_KEY, $source);
  }
}
