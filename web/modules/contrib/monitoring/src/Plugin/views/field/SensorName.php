<?php

/**
 * @file
 * Contains \Drupal\monitoring\Plugin\views\field\SensorName.
 */

namespace Drupal\monitoring\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\monitoring\Sensor\NonExistingSensorException;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Defines a field handler that turns sensor machine name into a clickable link
 * with the sensor label as the show text.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("monitoring_sensor_name")
 */
class SensorName extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);

    try {
      $sensor_config = monitoring_sensor_manager()->getSensorConfigByName($value);
      $label = $sensor_config->getLabel();
    }
    catch (NonExistingSensorException $e) {
      $label = t('Disappeared sensor @name', array('@name' => $value));
    }

    return \Drupal::l($label, Url::fromRoute('entity.monitoring_sensor_config.details_form', array('monitoring_sensor_config' => $value)));
  }
}
