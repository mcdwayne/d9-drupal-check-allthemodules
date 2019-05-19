<?php

/**
 * @file
 * Contains \Drupal\temporal\Entity\Temporal.
 */

namespace Drupal\temporal\Entity;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\temporal\TemporalInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Temporal entity.
 *
 * @ingroup temporal
 *
 * @ContentEntityType(
 *   id = "temporal",
 *   label = @Translation("Temporal"),
 *   bundle_label = @Translation("Temporal type"),
 *   handlers = {
 *     "storage_schema" = "Drupal\temporal\TemporalStorageSchema",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\temporal\TemporalListBuilder",
 *     "views_data" = "Drupal\temporal\Entity\TemporalViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\temporal\Form\TemporalForm",
 *       "add" = "Drupal\temporal\Form\TemporalForm",
 *       "edit" = "Drupal\temporal\Form\TemporalForm",
 *       "delete" = "Drupal\temporal\Form\TemporalDeleteForm",
 *     },
 *     "access" = "Drupal\temporal\TemporalAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\temporal\TemporalHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "temporal",
 *   admin_permission = "administer temporal entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "value",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/temporal/{temporal}",
 *     "add-form" = "/temporal/add/{temporal_type}",
 *     "edit-form" = "/temporal/{temporal}/edit",
 *     "delete-form" = "/temporal/{temporal}/delete",
 *     "collection" = "/temporal",
 *   },
 *   bundle_entity_type = "temporal_type",
 *   field_ui_base_route = "entity.temporal_type.edit_form"
 * )
 */
class Temporal extends ContentEntityBase implements TemporalInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
  }

  /**
   * Method to return raw Temporal values correctly typed
   *
   * @return mixed
   */
  public function getValue() {
    // Dynamically cast the value to the appropriate type
    $value = $this->get('value')->value;
    settype($value, temporal_field_type_mapping($this->get('entity_field_type')->value));
    return $value;
  }

  /**
   * Method to render Temporal values in a nicer way
   * 
   * @return mixed
   */
  public function renderValue() {
    // Fetch the typed value
    $value = $this->getValue();
    $field_type = $this->getTemporalEntityField();

    $values = [
      'type' => $this->getTemporalEntityBundle(),
    ];

    // Create an new empty instance of the entity that contains the field we want to display
    /** @var Entity $entity */
    $entity = \Drupal::entityTypeManager()->getStorage($this->getTemporalEntityType())->create($values);
    /** @var \Drupal\Core\Field\FieldItemListInterface $field */
    $field = $entity->{$field_type};
    $field->setValue($value);
    $renderable_field = $field->view();

    // Handle zero entity reference values
    if($value == 0 AND $this->getTemporalEntityFieldType() == 'entity_reference') {
      $field_info = temporal_field_info_helper($entity->entityTypeId, $entity->bundle(), $field->getName());
      $renderable_field['#markup'] = '<div class="field__label">'.$field_info['label']. '</div> REMOVED';
    }

    return render($renderable_field);
  }

  public function getStatus() {
    return $this->get('status')->value;
  }

  public function getFuture() {
    return $this->get('future')->value;
  }

  public function getDelta() {
    return $this->get('delta')->value;
  }

  public function getTemporalEntityType() {
    return $this->get('entity_type')->value;
  }

  public function getTemporalEntityBundle() {
    return $this->get('entity_bundle')->value;
  }

  public function getTemporalEntityFieldType() {
    return $this->get('entity_field_type')->value;
  }

  public function getTemporalEntityField() {
    return $this->get('entity_field')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityId() {
    return $this->get('entity_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityId($name) {
    $this->set('entity_id', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('value')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('value', $name);
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
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? NODE_PUBLISHED : NODE_NOT_PUBLISHED);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Temporal entity.'))
      ->setReadOnly(TRUE);
    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The Temporal type/bundle.'))
      ->setSetting('target_type', 'temporal_type')
      ->setRequired(TRUE);
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Temporal entity.'))
      ->setReadOnly(TRUE);

    $fields['delta'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Delta'))
      ->setDescription(t('The delta value for multiple value fields'))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['value'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Value'))
      ->setDescription(t('The value of the Temporal entity.'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -90,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -90,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Due to cardinality of all entities starting at one it is not possible to have
    // a true entity reference without a column for every entity type. So we just store
    // the value and deal with the data at the query/code level. Views data code should
    // be able to handle defining multiple variants
    $fields['entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Entity Reference'))
      ->setDescription(t('The entity the temporal entry belongs to'))
      ->setSetting('unsigned', TRUE);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity Type'))
      ->setDescription(t('The entity type of the tracked field'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['entity_bundle'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity Bundle'))
      ->setDescription(t('The entity bundle of the tracked field'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['entity_field_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Field Type'))
      ->setDescription(t('The field type of the tracked field'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['entity_field'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity Field'))
      ->setDescription(t('The name of the tracked field'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Temporal entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => -70,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Temporal entry is published.'))
      ->setDefaultValue(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code for the Temporal entity.'))
      ->setDisplayOptions('form', array(
        'type' => 'language_select',
        'weight' => 10,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'))
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => -80,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'datetime_timestamp',
        'weight' => -80,
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    /*
     * These fields have been added to allow for future entries to be acted upon
     * No code is written for this functionality, but the schema is in place to handle
     * these with a callback and data based on a future date to execute.
     */
    $fields['future'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Future'))
      ->setDescription(t('A future date/time when this entry should be processed'))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDefaultValue(0);

    $fields['callback'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Callback'))
      ->setDescription(t('The optional callback to use when processing future entries'))
      ->setSetting('max_length', 255)
      ->setDefaultValue(NULL);

    $fields['callback_data'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Callback Data'))
      ->setDescription(t('The optional serialized callback data'))
      ->setSetting('max_length', 255)
      ->setDefaultValue(NULL);

    return $fields;
  }

}
