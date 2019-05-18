<?php

/**
 * @file
 * Definition of PrefetcherLabel
 */

namespace Drupal\prefetcher\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to flag the node type.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("prefetcher_label")
 */
class PrefetcherLabel extends FieldPluginBase {

  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * Define the available options
   * @return array
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    return $options;
  }


  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    return check_markup($values->_entity->label());
  }
}