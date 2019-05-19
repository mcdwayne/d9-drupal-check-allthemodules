<?php

declare(strict_types = 1);

namespace Drupal\views_parity_row\Plugin\views\Entity\Render;

use Drupal\views\ResultRow;

/**
 * Renders entities in the current language.
 */
abstract class DefaultLanguageRenderer extends RendererBase {

  /**
   * Returns the language code associated to the given row.
   *
   * @param \Drupal\views\ResultRow $row
   *   The result row.
   *
   * @return string
   *   A language code.
   */
  public function getLangcode(ResultRow $row) {
    return $row->_entity->getUntranslated()->language()->id;
  }

}
