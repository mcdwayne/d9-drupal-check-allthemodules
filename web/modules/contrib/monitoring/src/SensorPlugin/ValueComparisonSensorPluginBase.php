<?php

/**
 * @file
 * Contains \Drupal\monitoring\SensorPlugin\ValueComparisonSensorPluginBase
 */

namespace Drupal\monitoring\SensorPlugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\monitoring\Result\SensorResultInterface;

/**
 * Provides abstract functionality for a value comparison sensor.
 *
 * Uses "value" offset to store the expected value against which the actual
 * value will be compared to. You can prepopulate this offset with initial
 * value that will be used as the expected one on the sensor enable.
 */
abstract class ValueComparisonSensorPluginBase extends SensorPluginBase {

  /**
   * Gets the value description that will be shown in the settings form.
   *
   * @return string
   *   Value description.
   */
  abstract protected function getValueDescription();

  /**
   * Gets the current value.
   *
   * @return mixed
   *   The current value.
   */
  abstract protected function getValue();

  /**
   * Gets the current value as text.
   *
   * @return string
   *   The expected value.
   */
  protected function getValueText() {
    if ($this->sensorConfig->isBool()) {
      $actual_value = $this->getValue() ? 'TRUE' : 'FALSE';
    }
    else {
      $actual_value = $this->getValue();
    }

    return $actual_value;
  }

  /**
   * Gets the expected value.
   *
   * @return mixed
   *   The expected value.
   */
  protected function getExpectedValue() {
    return $this->sensorConfig->getSetting('value');
  }

  /**
   * Adds expected value setting field into the sensor settings form.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['value'] = array(
      '#title' => t('Expected value'),
      '#description' => $this->getValueDescription(),
      '#default_value' => $this->getExpectedValue(),
    );

    if ($this->sensorConfig->isBool()) {
      $form['value']['#type'] = 'checkbox';
    }
    elseif ($this->sensorConfig->isNumeric()) {
      $form['value']['#type'] = 'number';
    }
    else {
      $form['value']['#type'] = 'textfield';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    $result->setValue($this->getValue());
    $result->setExpectedValue($this->getExpectedValue());
  }
}
