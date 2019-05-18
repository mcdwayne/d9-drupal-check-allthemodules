<?php

/**
 * @file
 * Contains \Drupal\efq_views\Plugin\views\filter\InOperator.
 */

namespace Drupal\efq_views\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator  as ViewsInOperator;

/**
 * Simple filter to handle matching of multiple options selectable via checkboxes
 *
 * Definition items:
 * - options callback: The function to call in order to generate the value options. If omitted, the options 'Yes' and 'No' will be used.
 * - options arguments: An array of arguments to pass to the options callback.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("efq_in_operator")
 */
class InOperator extends ViewsInOperator {

  /**
   * This kind of construct makes it relatively easy for a child class to add or
   * remove functionality by overriding this function and adding/removing items
   * from this array.
   */
  function operators() {
    $operators = array(
      'IN' => array(
        'title' => t('Is one of'),
        'short' => t('in'),
        'short_single' => t('='),
        'method' => 'op_simple',
        'values' => 1,
      ),
      'NOT IN' => array(
        'title' => t('Is not one of'),
        'short' => t('not in'),
        'short_single' => t('<>'),
        'method' => 'op_simple',
        'values' => 1,
      ),
    );
    return $operators;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['operator']['default'] = 'IN';
    return $options;
  }

}
