<?php

namespace Drupal\sortableviews\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Renders a draggable handle.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("sortable_views_handle")
 */
class SortableViewsHandle extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {}

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return [
      '#theme' => 'sortableviews_handle',
      '#dataid' => $values->_entity->id(),
    ];
  }

}
