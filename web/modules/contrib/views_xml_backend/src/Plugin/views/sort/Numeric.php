<?php

/**
 * @file
 * Contains \Drupal\views_xml_backend\Plugin\views\sort\Numeric.
 */

namespace Drupal\views_xml_backend\Plugin\views\sort;

use Drupal\views_xml_backend\Sorter\NumericSorter;

/**
 * Numeric sort plugin for XML.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("views_xml_backend_numeric")
 */
class Numeric extends Standard {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $alias = 'sort_numeric_' . $this->options['id'];
    $this->query->addField($alias, $this->options['xpath_selector']);
    $this->query->addSort(new NumericSorter($alias, $this->options['order']));
  }

}
