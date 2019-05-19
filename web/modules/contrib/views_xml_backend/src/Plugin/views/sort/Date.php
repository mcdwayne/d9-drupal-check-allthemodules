<?php

/**
 * @file
 * Contains \Drupal\views_xml_backend\Plugin\views\sort\Date.
 */

namespace Drupal\views_xml_backend\Plugin\views\sort;

use Drupal\views_xml_backend\Sorter\DateSorter;

/**
 * Date sort plugin for views_xml_backend.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("views_xml_backend_date")
 */
class Date extends Standard {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $alias = 'sort_date_' . $this->options['id'];
    $this->query->addField($alias, $this->options['xpath_selector']);
    $this->query->addSort(new DateSorter($alias, $this->options['order']));
  }

}
