<?php

namespace Drupal\formfactorykits\Kits\Markup;

use Drupal\formfactorykits\Kits\FormFactoryKit;

/**
 * Class MarkupKit
 *
 * @package Drupal\formfactorykits\Kits\Markup
 */
class MarkupKit extends FormFactoryKit {
  const ID = 'markup';
  const TYPE = 'markup';
  const MARKUP_KEY = 'markup';

  /**
   * @param mixed $default
   *
   * @return string
   */
  public function getMarkup($default = NULL) {
    return parent::get(static::MARKUP_KEY, $default);
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
