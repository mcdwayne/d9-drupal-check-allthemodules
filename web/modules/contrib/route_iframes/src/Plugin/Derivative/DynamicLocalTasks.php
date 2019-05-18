<?php

namespace Drupal\route_iframes\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Defines dynamic local tasks.
 */
class DynamicLocalTasks extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $config = \Drupal::config('route_iframes.routeiframesconfiguration');
    $name = $config->get('route_iframe_main_tab_name');
    $path = $config->get('route_iframe_main_tab_path');
    $tabs = $config->get('route_iframe_tabs');
    $task = 'route_iframes.' . $path;

    if (!empty($name) && !empty($path)) {
      $this->derivatives[$task] = $base_plugin_definition;
      $this->derivatives[$task]['title'] = $name;
      $this->derivatives[$task]['route_name'] = $task;
      $this->derivatives[$task]['base_route'] = 'entity.node.canonical';
      $weight = 0;
      if (!empty($tabs)) {
        $this->derivatives[$task]['route_name'] = $task . '.' . $tabs[0]['path'];
        foreach ($tabs as $tab) {
          $subtask = $task . '.' . $tab['path'];
          $this->derivatives[$subtask] = $base_plugin_definition;
          $this->derivatives[$subtask]['title'] = $tab['name'];
          $this->derivatives[$subtask]['route_name'] = $subtask;
          $this->derivatives[$subtask]['parent_id'] = 'route_iframes.dynamic_local_tasks:' . $task;
          $this->derivatives[$subtask]['id'] = $subtask;
          $this->derivatives[$subtask]['weight'] = $weight;
          $this->derivatives[$subtask]['options'] = ['tab' => $tab['path']];
          $weight++;
        }
      }
    }
    return $this->derivatives;
  }

}
