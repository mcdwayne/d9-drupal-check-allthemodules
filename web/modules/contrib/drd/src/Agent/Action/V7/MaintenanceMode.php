<?php

namespace Drupal\drd\Agent\Action\V7;

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
      return array(
        'data' => variable_get('maintenance_mode', FALSE),
      );
    }
    else {
      variable_set('maintenance_mode', ($args['mode'] == 'on'));
      cache_clear_all('*', 'cache_page', TRUE);
    }
    return array();
  }

}
