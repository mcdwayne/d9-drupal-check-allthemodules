<?php

namespace Drupal\efq_views\Plugin\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * Sort handler for entity keys.
 *
 * @ViewsSort("efq_field")
 */
class EntityField extends SortPluginBase {

  /**
   * @var \Drupal\efq_views\Plugin\views\EntityFieldQuery
   */
  protected $query;

  /**
   * {@inheritdoc}
   */
  function query() {
    $this->query->query->sort($this->real_field, $this->options['order']);
  }

}
