<?php

namespace Drupal\people\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\people\PeopleInterface;

/**
 * Defines the people entity class.
 *
 * @ContentEntityType(
 *   id = "people",
 *   label = @Translation("People"),
 *   bundle_label = @Translation("People type"),
 *   handlers = {
 *     "storage" = "Drupal\people\PeopleStorage",
 *     "view_builder" = "Drupal\people\PeopleViewBuilder",
 *     "access" = "Drupal\people\PeopleAccessControlHandler",
 *     "views_data" = "Drupal\people\PeopleViewsData",
 *     "form" = {
 *       "default" = "Drupal\people\PeopleForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *   },
 *   base_table = "people",
 *   entity_keys = {
 *     "id" = "pid",
 *     "bundle" = "type",
 *     "label" = "last_name",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *   },
 *   bundle_entity_type = "people_type",
 *   field_ui_base_route = "entity.people_type.edit_form",
 *   admin_permission = "administer peoples",
 *   links = {
 *     "add-page" = "/admin/resource/people/add",
 *     "add-form" = "/admin/resource/people/add/{people_type}",
 *     "canonical" = "/admin/resource/people/{people}",
 *     "edit-form" = "/admin/resource/people/{people}/edit",
 *     "delete-form" = "/admin/resource/people/{people}/delete",
 *     "collection" = "/admin/resource/people",
 *   }
 * )
 */
class People extends ContentEntityBase implements PeopleInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['first_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('First name'))
      ->setSetting('max_length', 32)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['last_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Last name'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 32)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('Job title'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['organization'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Organization'))
      ->setDescription(t('The organization which this people belongs to.'))
      // Not every people need organization
      // ->setRequired(TRUE)
      ->setSetting('target_type', 'organization')
      ->setDisplayOptions('view', [
        'type' => 'entity_reference_label',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -3,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->addConstraint('UniqueField')
      ->setDisplayOptions('view', [
        'type' => "basic_string",
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'email_default',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['telephone'] = BaseFieldDefinition::create('telephone')
      ->setLabel(t('Telephone number'))
      ->addConstraint('UniqueField')
      ->setDisplayOptions('view', [
        'type' => "basic_string",
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'email_default',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Description'))
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 10,
        'settings' => [
          'rows' => 4,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The timestamp that the job was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The timestamp that the job was last changed.'));

    return $fields;
  }

}
