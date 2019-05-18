<?php

namespace Drupal\cbo_location\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\cbo_location\LocationInterface;

/**
 * Defines the location entity class.
 *
 * @ContentEntityType(
 *   id = "location",
 *   label = @Translation("Location"),
 *   handlers = {
 *     "access" = "Drupal\cbo_location\LocationAccessControlHandler",
 *     "views_data" = "Drupal\cbo_location\LocationViewsData",
 *     "form" = {
 *       "default" = "Drupal\cbo_location\LocationForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *   },
 *   base_table = "location",
 *   entity_keys = {
 *     "id" = "lid",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *   },
 *   admin_permission = "administer locations",
 *   links = {
 *     "add-form" = "/admin/location/add",
 *     "canonical" = "/admin/location/{location}",
 *     "edit-form" = "/admin/location/{location}/edit",
 *     "delete-form" = "/admin/location/{location}/delete",
 *     "collection" = "/admin/location",
 *   }
 * )
 */
class Location extends ContentEntityBase implements LocationInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['address'] = BaseFieldDefinition::create('address')
      ->setLabel(t('Address'))
      ->setDisplayOptions('view', [
        'type' => 'address_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'address_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The timestamp that the lot was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The timestamp that the lot was last changed.'));

    return $fields;
  }

}
