<?php
/**
 * @file
 * Contains \Drupal\social_counters\Entity\SocialCountersData.
 */

namespace Drupal\social_counters\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\social_counters\SocialCountersDataInterface;
use Drupal\user\UserInterface;

/**
 * Defines the SocialCountersData.
 *
 * @ContentEntityType(
 *   id = "social_counters_data",
 *   label = @Translation("Social Counters Data"),
 *   base_table = "social_counters_data",
 *   handlers = {
 *     "views_data" = "Drupal\social_counters\SocialCountersDataViewsData",
 *   },
 *   entity_keys = {
 *     "id" = "id"
 *   },
 * )
 */
class SocialCountersData extends ContentEntityBase implements SocialCountersDataInterface {
  /**
   * {@inheritdoc}
   *
   * Define the field properties here.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Social Counters entity.'))
      ->setReadOnly(TRUE);

    $fields['config'] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Configuration'))
    ->setDescription(t('Reference to social counters configuration.'))
    ->setSetting('target_type', 'social_counters_config');

    $fields['counter'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Counter'))
      ->setDescription(t('Number of counters for specific social network.'));

    return $fields;
  }
}
