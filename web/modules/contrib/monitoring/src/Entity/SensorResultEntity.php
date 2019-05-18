<?php
/**
 * @file
 * Contains \Drupal\monitoring\Entity\SensorResultEntity.
 */

namespace Drupal\monitoring\Entity;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\monitoring\Result\SensorResult;

/**
 * The monitoring_sensor_result entity class.
 *
 * @ContentEntityType(
 *   id = "monitoring_sensor_result",
 *   label = @Translation("Monitoring sensor result"),
 *   base_table = "monitoring_sensor_result",
 *   translatable = FALSE,
 *   handlers = {
 *     "views_data" = "Drupal\monitoring\Entity\ViewsData\SensorResultViewsData",
 *   },
 *   entity_keys = {
 *     "id" = "record_id",
 *     "label" = "sensor_message",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class SensorResultEntity extends ContentEntityBase implements SensorResultDataInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['record_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Record ID'))
      ->setDescription(t('The record ID.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The record UUID.'))
      ->setReadOnly(TRUE);

    $fields['sensor_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Sensor name'))
      ->setDescription(t('The machine name of the sensor.'));

    $fields['sensor_status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Sensor status'))
      ->setDescription(t('The sensor status at the moment of the sensor run.'));

    $fields['sensor_value'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Sensor value'))
      ->setDescription(t('The sensor value at the moment of the sensor run.'));

    $fields['sensor_message'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Sensor message'))
      ->setDescription(t('The sensor message reported by the sensor.'));

    $fields['timestamp'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Timestamp'))
      ->setDescription(t('The time that the sensor was executed.'));

    $fields['execution_time'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Execution time'))
      ->setDescription(t('The time needed for the sensor to execute in ms.'));

    return $fields;
  }

  /**
   * Gets sensor name.
   *
   * @return string
   *   Sensor name.
   */
  protected function getSensorName() {
    return $this->get('sensor_name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->get('sensor_status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusLabel() {
    $labels = SensorResult::getStatusLabels();
    return $labels[$this->get('sensor_status')->value];
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $this->get('sensor_value')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    $this->get('sensor_message')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getExecutionTime() {
    $this->get('execution_time')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimestamp() {
    $this->get('timestamp')->value;
  }

  /**
   * Sets sensor name.
   *
   * @param string $name
   *   The name of the sensor.
   */
  protected function setSensorName($name) {
    $this->set('sensor_name', $name);
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->set('sensor_status', $status);
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($sensor_value) {
    $this->set('sensor_value', $sensor_value);
  }

  /**
   * {@inheritdoc}
   */
  public function setMessage($message, array $variables = array()) {
    $this->set('sensor_message', new FormattableMarkup($message, $variables));
  }

  /**
   * {@inheritdoc}
   */
  public function setExecutionTime($time) {
    $this->set('execution_time', $time);
  }
}
