<?php

/**
 * @file
 * Contains \Drupal\efq_views\Plugin\views\filter\String.
 */

namespace Drupal\efq_views\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\String as ViewsString;

/**
 * Filter handler for string.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("efq_string")
 */
class String extends ViewsString {

  /**
   * We don't support every operator from the parent class ("not between", for example),
   * hence the need to define only the operators we do support.
   */
  function operators() {
    $operators = array(
      '=' => array(
        'title' => t('Is equal to'),
        'short' => t('='),
        'method' => 'op_simple',
        'values' => 1,
      ),
      '<>' => array(
        'title' => t('Is not equal to'),
        'short' => t('!='),
        'method' => 'op_simple',
        'values' => 1,
      ),
      'CONTAINS' => array(
        'title' => t('Contains'),
        'short' => t('contains'),
        'method' => 'op_simple',
        'values' => 1,
      ),
      'STARTS_WITH' => array(
        'title' => t('Starts with'),
        'short' => t('begins'),
        'method' => 'op_simple',
        'values' => 1,
      ),
    );

    return $operators;
  }

  /**
   * {@inheritdoc}
   */
  function query() {
    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}($this->real_field);
    }
  }
}
