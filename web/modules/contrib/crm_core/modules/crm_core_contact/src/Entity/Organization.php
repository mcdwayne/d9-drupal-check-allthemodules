<?php

namespace Drupal\crm_core_contact\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\crm_core\EntityOwnerTrait;
use Drupal\crm_core_contact\OrganizationInterface;
use Drupal\entity\Revision\RevisionableContentEntityBase;

/**
 * CRM Organization Entity Class.
 *
 * @ContentEntityType(
 *   id = "crm_core_organization",
 *   label = @Translation("CRM Core Organization"),
 *   bundle_label = @Translation("Organization type"),
 *   handlers = {
 *     "access" = "Drupal\crm_core_contact\OrganizationAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\crm_core_contact\Form\OrganizationForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\crm_core_contact\OrganizationListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *       "revision" = "\Drupal\entity\Routing\RevisionRouteProvider",
 *     },
 *     "local_task_provider" = {
 *       "default" = "\Drupal\entity\Menu\DefaultEntityLocalTaskProvider",
 *     },
 *   },
 *   base_table = "crm_core_organization",
 *   revision_table = "crm_core_organization_revision",
 *   admin_permission = "administer crm_core_organization entities",
 *   show_revision_ui = TRUE,
 *   entity_keys = {
 *     "id" = "organization_id",
 *     "revision" = "revision_id",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   bundle_entity_type = "crm_core_organization_type",
 *   field_ui_base_route = "entity.crm_core_organization_type.edit_form",
 *   permission_granularity = "bundle",
 *   permission_labels = {
 *     "singular" = @Translation("Organization"),
 *     "plural" = @Translation("Organizations"),
 *   },
 *   links = {
 *     "add-page" = "/crm-core/organization/add",
 *     "add-form" = "/crm-core/organization/add/{crm_core_organization_type}",
 *     "canonical" = "/crm-core/organization/{crm_core_organization}",
 *     "collection" = "/crm-core/organization",
 *     "edit-form" = "/crm-core/organization/{crm_core_organization}/edit",
 *     "delete-form" = "/crm-core/organization/{crm_core_organization}/delete",
 *     "revision" = "/crm-core/organization/{crm_core_organization}/revisions/{crm_core_organization_revision}/view",
 *     "revision-revert-form" = "/crm-core/organization/{crm_core_organization}/revisions/{crm_core_organization_revision}/revert",
 *     "version-history" = "/crm-core/organization/{crm_core_organization}/revisions",
 *   }
 * )
 */
class Organization extends RevisionableContentEntityBase implements OrganizationInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the organization was created.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the organization was last edited.'))
      ->setRevisionable(TRUE);

    $fields['uid'] = EntityOwnerTrait::getOwnerFieldDefinition()
      ->setDescription(t('The user that is the organization owner.'));

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textfield',
        'weight' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Gets the primary address.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface|\Drupal\Core\TypedData\TypedDataInterface
   *   The address property object.
   */
  public function getPrimaryAddress() {
    return $this->getPrimaryField('address');
  }

  /**
   * Gets the primary email.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface|\Drupal\Core\TypedData\TypedDataInterface
   *   The email property object.
   */
  public function getPrimaryEmail() {
    return $this->getPrimaryField('email');
  }

  /**
   * Gets the primary phone.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface|\Drupal\Core\TypedData\TypedDataInterface
   *   The phone property object.
   */
  public function getPrimaryPhone() {
    return $this->getPrimaryField('phone');
  }

  /**
   * Gets the primary field.
   *
   * @param string $field
   *   The primary field name.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface|\Drupal\Core\TypedData\TypedDataInterface
   *   The primary field property object.
   *
   * @throws \InvalidArgumentException
   *   If no primary field is configured.
   *   If the configured primary field does not exist.
   */
  public function getPrimaryField($field) {
    $type = $this->get('type')->entity;
    $name = empty($type->getPrimaryFields()[$field]) ? '' : $type->getPrimaryFields()[$field];
    return $this->get($name);
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $label = $this->get('name')->value;
    if (empty($label)) {
      $label = t('Nameless #@id', ['@id' => $this->id()]);
    }
    \Drupal::moduleHandler()->alter('crm_core_organization_label', $label, $this);

    return $label;
  }

}
