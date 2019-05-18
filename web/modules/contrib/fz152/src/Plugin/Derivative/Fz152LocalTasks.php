<?php

namespace Drupal\fz152\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Defines dynamic local tasks.
 */
class Fz152LocalTasks extends DeriverBase {

  /**
   * Implement dynamic logic to provide values for the same keys as in
   * fz152.links.task.yml.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $plugin_service = \Drupal::service('plugin.manager.fz152');
    foreach ($plugin_service->getDefinitions() as $plugin_id => $plugin) {
      $instance = $plugin_service->createInstance($plugin_id);
      $route_info = $instance->getSettingsPage();
      $this->derivatives['fz152.admin.' . $plugin_id] = $base_plugin_definition;
      $this->derivatives['fz152.admin.' . $plugin_id]['title'] = $route_info['title'];
      $this->derivatives['fz152.admin.' . $plugin_id]['route_name'] = 'fz152.settings.' . $plugin_id;
      $this->derivatives['fz152.admin.' . $plugin_id]['base_route'] = 'fz152.settings';
      $this->derivatives['fz152.admin.' . $plugin_id]['weight'] = $route_info['weight'];
    }

    return $this->derivatives;
  }

}