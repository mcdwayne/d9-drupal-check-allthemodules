<?php

/**
 * @file
 * Definition of Drupal\pinger\Entity\PingerSite.
 */

namespace Drupal\Pinger\Entity;

use Drupal\Core\Entity\EntityNG;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Annotation\Translation;
use Drupal\pinger\PingerSiteInterface;
/**
 * Defines the Pinger Site entity class.
 *
 * @EntityType(
 *   id = "pinger_site",
 *   label = @Translation("Pinger Site"),
 *   module = "pinger",
 *   controllers = {
 *     "storage" = "Drupal\pinger\PingerSiteStorageController",
 *   },
 *   base_table = "pinger_sites",
 *   entity_keys = {
 *     "id" = "id",
 *   }
 * )
 */
class PingerSite extends EntityNG implements PingerSiteInterface {

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->get('id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return $this->get('url')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type) {
    $properties['id'] = array(
      'label' => t('Site ID'),
      'description' => t('The Site ID.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );

    $properties['url'] = array(
      'label' => t('Url'),
      'description' => t('The url of the site.'),
      'type' => 'string_field',
    );

    return $properties;
  }

}
