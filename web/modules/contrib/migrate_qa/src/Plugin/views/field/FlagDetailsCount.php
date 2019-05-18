<?php

namespace Drupal\migrate_qa\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Handler to display flag details count.
 *
 * @ViewsField("migrate_qa_flag_details_count")
 */
class FlagDetailsCount extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return $values->_entity->details->count();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing, because the field is computed only in the render method.
  }

}
