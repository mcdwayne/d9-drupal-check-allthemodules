<?php

namespace Drupal\sitelog\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Generates local tasks.
 */
class FilesLocalTasks extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('file')) {
      $this->derivatives['sitelog.files'] = $base_plugin_definition;
      $this->derivatives['sitelog.files']['route_name'] = 'sitelog.files';
      $this->derivatives['sitelog.files']['base_route'] = 'sitelog.comments';
      $this->derivatives['sitelog.files']['title'] = 'Files';
    }
    return $this->derivatives;
  }
}
