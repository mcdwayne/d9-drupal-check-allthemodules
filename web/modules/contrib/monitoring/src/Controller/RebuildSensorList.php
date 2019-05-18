<?php

namespace Drupal\monitoring\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller to rebuild the sensors.
 */
class RebuildSensorList extends ControllerBase {

  /**
   * Rebuilds updated requirements sensors.
   */
  public function rebuild() {
    \Drupal::service('monitoring.sensor_manager')->rebuildSensors();
    return $this->redirect('monitoring.sensors_overview_settings');
  }
}
