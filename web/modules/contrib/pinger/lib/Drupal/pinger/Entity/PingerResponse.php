<?php

/**
 * @file
 * Definition of Drupal\pinger\Entity\PingerResponse.
 */

namespace Drupal\Pinger\Entity;

use Drupal\Core\Entity\EntityNG;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Annotation\Translation;
use Drupal\pinger\PingerResponseInterface;

/**
 * Defines the Pinger Response entity class.
 *
 * @EntityType(
 *   id = "pinger_response",
 *   label = @Translation("Pinger Response"),
 *   module = "pinger",
 *   controllers = {
 *     "storage" = "Drupal\pinger\PingerResponseStorageController",
 *   },
 *   base_table = "pinger_responses",
 *   entity_keys = {
 *     "id" = "id",
 *   }
 * )
 */
class PingerResponse extends EntityNG implements PingerResponseInterface {

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->get('id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSite() {
    return $this->get('site_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponseCode() {
    return $this->get('response_code')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponseTime() {
    return $this->get('response_time')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimestamp() {
    return $this->get('timestamp')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type) {
    $properties['id'] = array(
      'label' => t('Response ID'),
      'description' => t('The Response ID.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );

    $properties['site_id'] = array(
      'label' => t('Url'),
      'description' => t('The site ID for the response'),
      'type' => 'integer_field',
    );

    $properties['response_code'] = array(
      'label' => t('Response Code'),
      'description' => t('The Response Code for the request.'),
      'type' => 'string_field',
    );

    $properties['response_time'] = array(
      'label' => t('Duration'),
      'description' => t('The Duration of the request.'),
      'type' => 'string_field',
    );

    $properties['timestamp'] = array(
      'label' => t('Timestamp'),
      'description' => t('The timestamp of the response.'),
      'type' => 'integer_field',
    );

    return $properties;
  }

}
