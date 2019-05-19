<?php

namespace Drupal\stacks\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\stacks\WidgetInstanceEntityInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Widget Instance entity entity.
 *
 * @ingroup stacks
 *
 * @ContentEntityType(
 *   id = "widget_instance_entity",
 *   label = @Translation("Widget Instance entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\stacks\WidgetInstanceEntityListBuilder",
 *     "views_data" = "Drupal\stacks\Entity\WidgetInstanceEntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\stacks\Form\WidgetInstanceEntityForm",
 *       "add" = "Drupal\stacks\Form\WidgetInstanceEntityForm",
 *       "edit" = "Drupal\stacks\Form\WidgetInstanceEntityForm",
 *       "delete" = "Drupal\stacks\Form\WidgetInstanceEntityDeleteForm",
 *     },
 *     "access" = "Drupal\stacks\WidgetInstanceEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\stacks\WidgetInstanceEntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "widget_instance_entity",
 *   admin_permission = "administer widget instance entity entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/stacks/widget_instance_entity/{widget_instance_entity}",
 *     "add-form" = "/admin/structure/stacks/widget_instance_entity/add",
 *     "edit-form" = "/admin/structure/stacks/widget_instance_entity/{widget_instance_entity}/edit",
 *     "delete-form" = "/admin/structure/stacks/widget_instance_entity/{widget_instance_entity}/delete",
 *     "collection" = "/admin/structure/stacks/widget_instance_entity",
 *   },
 *   field_ui_base_route = "widget_instance_entity.settings"
 * )
 */
class WidgetInstanceEntity extends ContentEntityBase implements WidgetInstanceEntityInterface {
  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
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
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->set('status', $status);
    return $this;
  }

  /**
   * Increments the widget_instance_times_used value for this entity.
   */
  public function triggerTimesUsed() {
    $time_used = (int) $this->get('widget_instance_times_used')->value;
    $time_used++;
    $this->set('widget_instance_times_used', $time_used);
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
  public function getTemplate() {
    return $this->get('template')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTemplate($template) {
    $this->set('template', $template);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTheme() {
    return $this->get('theme')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTheme($theme) {
    $this->set('theme', $theme);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWrapperID() {
    return $this->get('wrapper_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setWrapperID($wrapper_id) {
    $this->set('wrapper_id', $wrapper_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWrapperClasses() {
    return str_replace(',', ' ', $this->get('wrapper_classes')->value);
  }

  /**
   * {@inheritdoc}
   */
  public function setWrapperClasses($wrapper_classes) {
    $this->set('wrapper_classes', $wrapper_classes);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isShareable() {
    return (bool) $this->get('enable_sharing')->getValue()[0]['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function setEnableSharing($enable_sharing) {
    $this->set('enable_sharing', $enable_sharing ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetEntityID() {
    if (NULL !== $this->get('widget_entity')->getValue()[0]['target_id']) {
      return $this->get('widget_entity')->getValue()[0]['target_id'];
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setWidgetEntityID($widget_entity_id) {
    $this->set('widget_entity', (int) $widget_entity_id);
    return $this;
  }

  public function getWidgetEntity() {
    if (NULL !== $this->get('widget_entity')->referencedEntities()[0]) {
      return $this->get('widget_entity')->referencedEntities()[0];
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getIsRequired() {
    return ($this->get('required')->value == 1) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setIsRequired($is_required) {
    $this->set('required', $is_required ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredType() {
    return $this->get('required_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRequiredType($required_type) {
    $this->set('required_type', $required_type);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredBundle() {
    return $this->get('required_bundle')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRequiredBundle($required_bundle) {
    $this->set('required_bundle', $required_bundle);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Widget Instance entity entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Widget Instance entity entity.'))
      ->setReadOnly(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Widget Instance entity entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The label for this widget instance.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 255,
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
        'weight' => -51,
      ])
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDescription(t('True if enabled. False if disabled.'))
      ->setRequired(TRUE)
      ->setDefaultValue(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => -51,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['widget_instance_times_used'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Times Used'))
      ->setDescription(t('How many times has this widget instance been used?'))
      ->setDefaultValue(1);

    $fields['template'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Template'))
      ->setDescription(t('Select which template to use for this widget.'))
      ->setSettings([
        'max_length' => 150,
        'text_processing' => 0,
      ])
      ->setRequired(TRUE)
      ->setDefaultValue('default')
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -50,
      ]);

    $fields['theme'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Theme'))
      ->setDescription(t('Set which theme to use for this widget.'))
      ->setSettings([
        'max_length' => 150,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -49,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['wrapper_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Wrapper ID'))
      ->setDescription(t('Specify the id set on the wrapper div for this widget.'))
      ->setSettings([
        'max_length' => 150,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -48,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['wrapper_classes'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Wrapper Classes'))
      ->setDescription(t('Specify css classes to set on the wrapper div for this widget.'))
      ->setRequired(FALSE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -47,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['enable_sharing'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Enable Sharing'))
      ->setDescription(t('Turn this option on to allow this widget instance to be shared.'))
      ->setRequired(FALSE)
      ->setDefaultValue(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => -46,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['widget_entity'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Widget Entity ID'))
      ->setDescription(t('Which stacks entity is this attached to?'))
      ->setSetting('target_type', 'widget_entity')
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => 'Select Widget Entity',
          'weight' => -45,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setRequired(TRUE);

    $fields['required'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Is this a required widget instace?'))
      ->setDescription(t('This specifies whether this widget instance is a required instance. Connected to the form settings of the widget field.'))
      ->setRequired(FALSE)
      ->setDefaultValue(FALSE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => -44,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['required_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('If this is a required instance, what type is it?'))
      ->setDescription(t('Connected to the form settings of the widget field.'))
      ->setRequired(FALSE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -43,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['required_bundle'] = BaseFieldDefinition::create('string')
      ->setLabel(t('If this is a required instance, what is the widget bundle it is connected to?'))
      ->setDescription(t('Connected to the form settings of the widget field.'))
      ->setRequired(FALSE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -42,
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

  /**
   * Wether this instance is already being used elsewhere.
   *
   * @TODO: This probably is better done through widget_instance_times_used but
   * that's not properly implemented yet.
   *
   * @see stacks_cron().
   */
  public function getTimesUsed($ignore_entity_id) {
    // Loop through all widget fields.
    $widget_fields = \Drupal::service('entity_field.manager')
      ->getFieldMapByFieldType('stacks_type');

    $count = 0;
    foreach ($widget_fields as $entity_type => $fields) {
      foreach ($fields as $field_name => $field) {
        $db_field_name = \Drupal::database()
          ->escapeTable($field_name . '_widget_instance_id');
        $db_table_name = \Drupal::database()->escapeTable($entity_type . '__' . $field_name);
        $query = db_query('SELECT COUNT(wi.id) FROM {widget_instance_entity} wi WHERE wi.id IN (SELECT f.' . $db_field_name . ' FROM {' . $db_table_name . '} f WHERE NOT (f.entity_id = :entity_id)) AND wi.id=:id', [
          ':id' => $this->id(),
          ':entity_id' => $ignore_entity_id,
        ]);
        $cnt = $query->fetchField();

        $count += $cnt;
      }
    }

    return $count;
  }

}
