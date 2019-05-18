<?php

namespace Drupal\crm_core_contact\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\crm_core\EntityOwnerTrait;
use Drupal\crm_core_contact\IndividualInterface;
use Drupal\entity\Revision\RevisionableContentEntityBase;

/**
 * CRM Individual Entity Class.
 *
 * @ContentEntityType(
 *   id = "crm_core_individual",
 *   label = @Translation("CRM Core Individual"),
 *   bundle_label = @Translation("Individual type"),
 *   handlers = {
 *     "access" = "Drupal\crm_core_contact\IndividualAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\crm_core_contact\Form\IndividualForm",
 *       "delete" = "Drupal\crm_core_contact\Form\IndividualDeleteForm",
 *     },
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\crm_core_contact\IndividualListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *       "revision" = "\Drupal\entity\Routing\RevisionRouteProvider",
 *     },
 *     "local_task_provider" = {
 *       "default" = "\Drupal\entity\Menu\DefaultEntityLocalTaskProvider",
 *     },
 *   },
 *   show_revision_ui = TRUE,
 *   admin_permission = "administer crm_core_individual entities",
 *   base_table = "crm_core_individual",
 *   revision_table = "crm_core_individual_revision",
 *   entity_keys = {
 *     "id" = "individual_id",
 *     "revision" = "revision_id",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   bundle_entity_type = "crm_core_individual_type",
 *   field_ui_base_route = "entity.crm_core_individual_type.edit_form",
 *   permission_granularity = "bundle",
 *   permission_labels = {
 *     "singular" = @Translation("Individual"),
 *     "plural" = @Translation("Individuals"),
 *   },
 *   links = {
 *     "add-page" = "/crm-core/individual/add",
 *     "add-form" = "/crm-core/individual/add/{crm_core_individual_type}",
 *     "canonical" = "/crm-core/individual/{crm_core_individual}",
 *     "collection" = "/crm-core/individual",
 *     "edit-form" = "/crm-core/individual/{crm_core_individual}/edit",
 *     "delete-form" = "/crm-core/individual/{crm_core_individual}/delete",
 *     "revision" = "/crm-core/individual/{crm_core_individual}/revisions/{crm_core_individual_revision}/view",
 *     "revision-revert-form" = "/crm-core/individual/{crm_core_individual}/revisions/{crm_core_individual_revision}/revert",
 *     "version-history" = "/crm-core/individual/{crm_core_individual}/revisions",
 *   }
 * )
 */
class Individual extends RevisionableContentEntityBase implements IndividualInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the individual was created.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the individual was last edited.'))
      ->setRevisionable(TRUE);

    $fields['uid'] = EntityOwnerTrait::getOwnerFieldDefinition()
      ->setDescription(t('The user that is the individual owner.'));

    $fields['name'] = BaseFieldDefinition::create('name')
      ->setLabel(t('Name'))
      ->setDescription(t('Name of the individual.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'name_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'name_default',
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
    $name = empty($type->primary_fields[$field]) ? '' : $type->primary_fields[$field];
    return $this->get($name);
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $label = '';
    if ($item = $this->get('name')->first()) {
      $label = "$item->given $item->family";
    }
    if (empty(trim($label))) {
      $label = t('Nameless #@id', ['@id' => $this->id()]);
    }
    \Drupal::moduleHandler()->alter('crm_core_individual_label', $label, $this);

    return $label;
  }

}
