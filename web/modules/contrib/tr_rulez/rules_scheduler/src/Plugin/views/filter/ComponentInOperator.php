<?php

namespace Drupal\rules_scheduler\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter handler for scheduler components.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("component_in_operator")
 */
class ComponentInOperator extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $this->valueTitle = $this->t('Component');
      $result = db_select('rules_scheduler', 'r')
        ->fields('r', ['config'])
        ->distinct()
        ->execute();
      $config_names = [];
      foreach ($result as $record) {
        $config_names[$record->config] = $record->config;
      }
      $this->valueOptions = $config_names;
    }
    return $this->valueOptions;
  }

}
