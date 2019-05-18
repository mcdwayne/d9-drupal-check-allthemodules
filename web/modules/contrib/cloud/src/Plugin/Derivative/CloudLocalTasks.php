<?php

namespace Drupal\cloud\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Defines dynamic local tasks.
 */
class CloudLocalTasks extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Implement dynamic logic to provide values for the same keys
    // as in example.links.task.yml.
    $this->derivatives['cloud.task_id'] = $base_plugin_definition;
    $this->derivatives['cloud.task_id']['title'] = "AWS Cloud Instance";
    $this->derivatives['cloud.task_id']['route_name'] = 'entity.aws_cloud_instance.collection';
    $this->derivatives['cloud.task_id']['base_name'] = 'cloud.root_menu';
    return $this->derivatives;
  }

}
