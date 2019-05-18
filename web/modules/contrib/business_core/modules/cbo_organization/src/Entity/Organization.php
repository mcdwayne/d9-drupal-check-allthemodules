<?php

namespace Drupal\cbo_organization\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\cbo_organization\OrganizationInterface;

/**
 * Defines the organization entity class.
 *
 * @ContentEntityType(
 *   id = "organization",
 *   label = @Translation("Organization"),
 *   label_collection = @Translation("Organizations"),
 *   bundle_label = @Translation("Organization type"),
 *   handlers = {
 *     "storage" = "Drupal\cbo_organization\OrganizationStorage",
 *     "access" = "Drupal\cbo_organization\OrganizationAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\cbo_organization\OrganizationForm",
 *       "delete" = "Drupal\cbo_organization\OrganizationDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *   },
 *   base_table = "organization",
 *   entity_keys = {
 *     "id" = "oid",
 *     "bundle" = "type",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *   },
 *   bundle_entity_type = "organization_type",
 *   field_ui_base_route = "entity.organization_type.edit_form",
 *   admin_permission = "administer organizations",
 *   links = {
 *     "add-page" = "/admin/organization/add",
 *     "add-form" = "/admin/organization/add/{organization_type}",
 *     "canonical" = "/admin/organization/{organization}",
 *     "edit-form" = "/admin/organization/{organization}/edit",
 *     "delete-form" = "/admin/organization/{organization}/delete",
 *     "collection" = "/admin/organization",
 *   }
 * )
 */
class Organization extends ContentEntityBase implements OrganizationInterface {

  /**
   * {@inheritdoc}
   */
  public function getParent() {
    return $this->get('parent')->entity;
  }

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
        'weight' => -4,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['parent'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Parent'))
      ->setSetting('target_type', 'organization')
      ->addConstraint('OrganizationParent')
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

    $fields['location'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Location'))
      ->setSetting('target_type', 'location')
      ->setDisplayOptions('view', [
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

}
