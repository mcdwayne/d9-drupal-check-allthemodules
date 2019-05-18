<?php
/**
 * @file
 * Contains \Drupal\monitoring\Entity\ViewsData\SensorResultViewsData.
 */

namespace Drupal\monitoring\Entity\ViewsData;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the message entity type.
 */
class SensorResultViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['monitoring_sensor_result']['sensor_message']['field']['id'] = 'monitoring_sensor_message';
    return $data;
  }

}
