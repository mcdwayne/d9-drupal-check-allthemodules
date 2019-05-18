<?php

namespace Drupal\log_entity\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Log Entity content entity.
 *
 * @ContentEntityType(
 *   id = "log_entity",
 *   label = @Translation("Log Entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *   },
 *   base_table = "log_entity",
 *   admin_permission = "administer log entry entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 * )
 */
class LogEntity extends ContentEntityBase {

  /**
   * Gets the event type.
   *
   * @return string
   *   The event type.
   */
  public function getEventType() {
    return $this->event_type->value;
  }

  /**
   * Gets the description.
   *
   * @return string
   *   The description.
   */
  public function getDescription() {
    return $this->description->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['event_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Event Type'))
      ->setDescription(t('The event type, any arbitrary string to group events by.'));

    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Event Description'))
      ->setDescription(t('Describe the logged event.'));

    $fields['timestamp'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Timestamp'))
      ->setDescription(t('The time that the event occurred.'))
      ->setRevisionable(TRUE);

    $fields['ip_address'] = BaseFieldDefinition::create('string')
      ->setLabel(t('IP Address'))
      ->setDescription(t('The IP address of the user that triggered the event.'))
      ->setRevisionable(TRUE);

    return $fields;
  }

}
