<?php

namespace Drupal\search_api_solr_pro\TwigExtension;

use Drupal\views\ResultRow;

/**
 * Class Entity.
 *
 * Twig functionality for item.
 */
class Item extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('obj_to_arr', [$this, 'objToArr']),
    ];
  }

  /**
   * Generates an array from obj
   */
  public function objToArr(ResultRow $obj) {
    return (array)$obj;
  }

}
