<?php

namespace Drupal\sitelog\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Generates local tasks.
 */
class StatisticsLocalTasks extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $moduleHandler = \Drupal::service('module_handler');

    // level one
    $this->derivatives['sitelog.statistics'] = $base_plugin_definition;
    $this->derivatives['sitelog.statistics']['route_name'] = 'sitelog.statistics';
    $this->derivatives['sitelog.statistics']['base_route'] = 'sitelog.comments';
    $this->derivatives['sitelog.statistics']['title'] = 'Statistics';

    // level two
    $this->derivatives['sitelog.statistics.referrers'] = $base_plugin_definition;
    $this->derivatives['sitelog.statistics.referrers']['route_name'] = 'sitelog.statistics';
    $this->derivatives['sitelog.statistics.referrers']['parent_id'] = 'sitelog.statistics:sitelog.statistics';
    $this->derivatives['sitelog.statistics.referrers']['title'] = 'Referrers';
    if ($moduleHandler->moduleExists('statistics')) {
      $this->derivatives['sitelog.statistics.views'] = $base_plugin_definition;
      $this->derivatives['sitelog.statistics.views']['route_name'] = 'sitelog.statistics.views';
      $this->derivatives['sitelog.statistics.views']['parent_id'] = 'sitelog.statistics:sitelog.statistics';
      $this->derivatives['sitelog.statistics.views']['title'] = 'Views';
    }
    if ($moduleHandler->moduleExists('smart_ip')) {
      $this->derivatives['sitelog.statistics.visitors'] = $base_plugin_definition;
      $this->derivatives['sitelog.statistics.visitors']['route_name'] = 'sitelog.statistics.visitors';
      $this->derivatives['sitelog.statistics.visitors']['parent_id'] = 'sitelog.statistics:sitelog.statistics';
      $this->derivatives['sitelog.statistics.visitors']['title'] = 'Visitors';
    }
    return $this->derivatives;
  }
}
