<?php

namespace Drupal\drd\Agent\Action\V8;

/**
 * Provides a 'MaintenanceMode' code.
 */
class MaintenanceMode extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $args = $this->getArguments();
    if ($args['mode'] == 'getStatus') {
      return [
        'data' => \Drupal::state()->get('system.maintenance_mode'),
      ];
    }
    else {
      \Drupal::state()->set('system.maintenance_mode', ($args['mode'] == 'on'));
      drupal_flush_all_caches();
    }
    return [];
  }

}
