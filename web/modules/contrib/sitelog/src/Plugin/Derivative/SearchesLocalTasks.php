<?php

namespace Drupal\sitelog\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Generates local tasks.
 */
class SearchesLocalTasks extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('search')) {
      $this->derivatives['sitelog.searches'] = $base_plugin_definition;
      $this->derivatives['sitelog.searches']['route_name'] = 'sitelog.searches';
      $this->derivatives['sitelog.searches']['base_route'] = 'sitelog.comments';
      $this->derivatives['sitelog.searches']['title'] = 'Searches';
    }
    return $this->derivatives;
  }
}
