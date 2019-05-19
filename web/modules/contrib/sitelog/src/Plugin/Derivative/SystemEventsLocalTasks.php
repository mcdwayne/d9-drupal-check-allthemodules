<?php

namespace Drupal\sitelog\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Generates local tasks.
 */
class SystemEventsLocalTasks extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('dblog')) {
      $this->derivatives['sitelog.system_events'] = $base_plugin_definition;
      $this->derivatives['sitelog.system_events']['route_name'] = 'sitelog.system_events';
      $this->derivatives['sitelog.system_events']['base_route'] = 'sitelog.comments';
      $this->derivatives['sitelog.system_events']['title'] = 'System events';
    }
    return $this->derivatives;
  }
}
