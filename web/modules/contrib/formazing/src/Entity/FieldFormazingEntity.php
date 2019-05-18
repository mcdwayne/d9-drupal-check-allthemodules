<?php

namespace Drupal\formazing\Entity;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Annotation\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the Field formazing entity entity.
 *
 * @ingroup formazing
 *
 * @ContentEntityType(
 *   id = "field_formazing_entity",
 *   label = @Translation("Field formazing entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\formazing\FieldFormazingEntityListBuilder",
 *
 *     "form" = {
 *       "default" = "Drupal\formazing\Form\FieldFormazingEntityForm",
 *       "add" = "Drupal\formazing\Form\FieldFormazingEntityForm",
 *       "edit" = "Drupal\formazing\Form\FieldFormazingEntityForm",
 *       "delete" = "Drupal\formazing\Form\FieldFormazingEntityDeleteForm",
 *     },
 *     "access" = "Drupal\formazing\FieldFormazingEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\formazing\FieldFormazingEntityHtmlRouteProvider",
 *     },
 *   },
 *   translatable = TRUE,
 *   base_table = "field_formazing_entity",
 *   data_table = "field_formazing_entity_field_data",
 *   admin_permission = "administer field formazing entity entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "weight" = "weight",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *     "formazing_id" = "formazing_id",
 *   },
 *   links = {
 *     "canonical" =
 *   "/admin/structure/field_formazing_entity/{field_formazing_entity}",
 *     "add-form" = "/admin/structure/field_formazing_entity/add",
 *     "edit-form" =
 *   "/admin/structure/field_formazing_entity/{field_formazing_entity}/edit",
 *     "delete-form" =
 *   "/admin/structure/field_formazing_entity/{field_formazing_entity}/delete",
 *     "collection" = "/admin/structure/field_formazing_entity",
 *   },
 *   field_ui_base_route = "field_formazing_entity.settings"
 * )
 */
class FieldFormazingEntity extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(
    EntityStorageInterface $storage_controller, array &$values
  ) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Field formazing entity entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'hidden',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Field formazing entity.'))
      ->setSettings([
        'max_length' => 250,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('The description of the Field formazing entity.'))
      ->setSettings([
        'max_length' => 2058,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['machine_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Machine name'))
      ->setDescription(t('The machine name of the Field formazing entity entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['placeholder'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Placeholder'))
      ->setDescription(t('The placeholder of the Field formazing entity entity.'))
      ->setSettings([
        'max_length' => 150,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Field formazing entity entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['weight'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Weight'))
      ->setDescription(t('The weight of the Field formazing entity entity.'))
      ->setSettings([
        'max_length' => 5,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['value'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Value of field'))
      ->setDescription(t('The value of the Field formazing entity entity.'))
      ->setSettings([
        'max_length' => 150,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['prefix'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Prefix'))
      ->setDescription(t('The prefix of the Field formazing entity entity.'))
      ->setSettings([
        'max_length' => 250,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['suffix'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Suffix'))
      ->setDescription(t('The suffix of the Field formazing entity entity.'))
      ->setSettings([
        'max_length' => 250,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['field_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Field type'))
      ->setDescription(t('The type of the Field formazing entity entity.'))
      ->setSettings([
        'max_length' => 350,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'hidden',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['formazing_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Parent form id'))
      ->setDescription(t('Id of parent form'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'hidden',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Field formazing entity is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['is_required'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Is required'))
      ->setDescription(t('Is this field required'))
      ->setDefaultValue(FALSE);

    $fields['is_showing_label'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Show label ?'))
      ->setDescription(t('If true, label will be showed'))
      ->setDefaultValue(FALSE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * @return string
   */
  public function getFieldType() {
    return $this->get('field_type')->value;
  }

  /**
   * @param $value
   */
  public function setFieldType($value) {
    $this->set('field_type', $value);
  }

  /**
   * @return string
   */
  public function getMachineName() {
    return $this->get('machine_name')->value;
  }

  /**
   * @param $value
   */
  public function setMachineName($value) {
    $this->set('machine_name', $value);
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * @param bool $value
   */
  public function setIsShowingLabel($value) {
    $this->set('is_showing_label', $value);
  }

  /**
   * @return bool
   */
  public function isShowingLabel() {
    return $this->get('is_showing_label')->value;
  }

  /**
   * @param bool $value
   */
  public function setRequired($value) {
    $this->set('is_required', $value);
  }

  /**
   * @return bool
   */
  public function isRequired() {
    return $this->get('is_required')->value;
  }

  /**
   * @return integer
   */
  public function getFormId() {
    return $this->get('formazing_id')->value;
  }

  /**
   * @param $value
   */
  public function setFormId($value) {
    $this->set('formazing_id', $value);
  }

  public function setPlaceholder($value) {
    $this->set('placeholder', $value);
  }

  public function getPlaceholder() {
    return $this->get('placeholder')->value;
  }

  public function getFieldValue() {
    return $this->get('value')->value;
  }

  public function setFieldValue($value) {
    $this->set('value', $value);
  }

  public function getPrefix() {
    return $this->get('prefix')->value;
  }

  public function setPrefix($value) {
    $this->set('prefix', $value);
  }

  public function getSuffix() {
    return $this->get('suffix')->value;
  }

  public function setSuffix($value) {
    $this->set('suffix', $value);
  }

  public function getWeight() {
    return $this->get('weight')->value;
  }

  public function setWeight($value) {
    $this->set('weight', $value);
  }

  public function getDescription() {
    return $this->get('description')->value;
  }

  public function setDescription($value) {
    $this->set('description', $value);
  }

}
