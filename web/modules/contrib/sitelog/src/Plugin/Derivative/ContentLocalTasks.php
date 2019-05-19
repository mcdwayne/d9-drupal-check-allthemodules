<?php

namespace Drupal\sitelog\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Generates local tasks.
 */
class ContentLocalTasks extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('node')) {
      $this->derivatives['sitelog.content'] = $base_plugin_definition;
      $this->derivatives['sitelog.content']['route_name'] = 'sitelog.content';
      $this->derivatives['sitelog.content']['base_route'] = 'sitelog.comments';
      $this->derivatives['sitelog.content']['title'] = 'Content';
    }
    return $this->derivatives;
  }
}
