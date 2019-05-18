<?php

/**
 * @file
 * Contains \Drupal\monitoring\Plugin\views\field\SensorMessage.
 */

namespace Drupal\monitoring\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Provides a field handler that renders a log message properly.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("monitoring_sensor_message")
 */
class SensorMessage extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    return $this->sanitizeValue($value, 'xss_admin');
  }

}
