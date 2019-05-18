<?php

namespace Drupal\formfactorykits\Kits\Markup;

/**
 * Class TextKit
 *
 * @package Drupal\formfactorykits\Kits\Markup
 */
class TextKit extends MarkupKit {
  const ID = 'text';

  /**
   * @param string $value
   *
   * @return static
   */
  public function setValue($value) {
    return $this->setMarkup(strip_tags($value));
  }
}
