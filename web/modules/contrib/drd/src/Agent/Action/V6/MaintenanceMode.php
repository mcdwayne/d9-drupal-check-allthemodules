<?php

namespace Drupal\drd\Agent\Action\V6;

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
        'data' => variable_get('site_offline', FALSE),
      );
    }
    else {
      variable_set('site_offline', ($args['mode'] == 'on'));
      cache_clear_all('*', 'cache_page', TRUE);
    }
    return array();
  }

}
