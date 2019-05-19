<?php

declare(strict_types = 1);

namespace Drupal\views_parity_row\Plugin\views\Entity\Render;

use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;

/**
 * Renders entities in the current language.
 */
class CurrentLanguageRenderer extends RendererBase {

  /**
   * Returns NULL so that the current language is used.
   *
   * @param \Drupal\views\ResultRow $row
   *   The result row.
   */
  public function getLangcode(ResultRow $row) {
  }

  /**
   * {@inheritdoc}
   */
  public function query(QueryPluginBase $query, $relationship = NULL) {
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    $entity_id = $row->_entity->id();

    return $this->build[$entity_id];
  }

}
