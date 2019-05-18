<?php

namespace Drupal\formfactorykits\Kits\Markup;

use Drupal\formfactorykits\Kits\FormFactoryKit;

/**
 * Class ItemKit
 *
 * @package Drupal\formfactorykits\Kits\Markup
 */
class ItemKit extends FormFactoryKit {
  const ID = 'item';
  const MARKUP_KEY = 'markup';

  /**
   * @return string
   */
  public function getMarkup() {
    return (string) parent::get(static::MARKUP_KEY);
  }

  /**
   * @param string $markup
   *
   * @return static
   */
  public function setMarkup($markup) {
    return $this->set(static::MARKUP_KEY, $markup);
  }
}
