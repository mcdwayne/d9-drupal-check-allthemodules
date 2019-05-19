<?php

namespace Drupal\sitelog\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Generates local tasks.
 */
class CommentsLocalTasks extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('comment')) {
      $this->derivatives['sitelog.comments'] = $base_plugin_definition;
      $this->derivatives['sitelog.comments']['route_name'] = 'sitelog.comments';
      $this->derivatives['sitelog.comments']['base_route'] = 'sitelog.comments';
      $this->derivatives['sitelog.comments']['title'] = 'Comments';
    }
    return $this->derivatives;
  }
}
