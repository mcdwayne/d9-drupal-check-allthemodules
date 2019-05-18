<?php

namespace Drupal\entity_log\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Entity log entity.
 *
 * @ingroup entity_log
 *
 * @ContentEntityType(
 *   id = "entity_log",
 *   label = @Translation("Entity log"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\entity_log\EntityLogListBuilder",
 *     "views_data" = "Drupal\entity_log\Entity\EntityLogViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\entity_log\Form\EntityLogForm",
 *       "add" = "Drupal\entity_log\Form\EntityLogForm",
 *       "edit" = "Drupal\entity_log\Form\EntityLogForm",
 *       "delete" = "Drupal\entity_log\Form\EntityLogDeleteForm",
 *     },
 *     "access" = "Drupal\entity_log\EntityLogAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\entity_log\EntityLogHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "entity_log",
 *   admin_permission = "administer entity log entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "log_type" = "log_type",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *     "hostname" = "hostname",
 *     "entity_logged_id" = "entity_logged_id",
 *     "old_value" = "old_value",
 *     "new_value" = "new_value"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/entity_log/{entity_log}",
 *     "add-form" = "/admin/structure/entity_log/add",
 *     "edit-form" = "/admin/structure/entity_log/{entity_log}/edit",
 *     "delete-form" = "/admin/structure/entity_log/{entity_log}/delete",
 *     "collection" = "/admin/structure/entity_log",
 *   },
 *   field_ui_base_route = "entity_log.settings"
 * )
 */
class EntityLog extends ContentEntityBase implements EntityLogInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
      'hostname' => \Drupal::request()->getClientIp(),
    ];
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
   * Getter for entity logged.
   *
   * @return mixed
   *   Entity logged.
   */
  public function getEntityLogged() {
    return $this->get('entity_logged_id')->entity;
  }

  /**
   * Getter for entity logged id.
   *
   * @return mixed
   *   Entity logged id.
   */
  public function getEntityLoggedId() {
    return $this->get('entity_logged_id')->target_id;
  }

  /**
   * Setter for entity logged id.
   *
   * @param int $entity_id
   *   Entity id.
   *
   * @return \Drupal\entity_log\Entity\EntityLog
   *   Entity log.
   */
  public function setEntityLoggedId($entity_id) {
    $this->set('entity_logged_id', $entity_id);
    return $this;
  }

  /**
   * Getter for log type.
   *
   * @return mixed
   *   Log type.
   */
  public function getLogType() {
    return $this->get('log_type')->value;
  }

  /**
   * Setter for log type.
   *
   * @param string $log_type
   *   Log Type.
   *
   * @return \Drupal\entity_log\Entity\EntityLog
   *   EntityLog.
   */
  public function setLogType($log_type) {
    $this->set('log_type', $log_type);
    return $this;
  }

  /**
   * Getter for old field value.
   *
   * @return mixed
   *   Old field value.
   */
  public function getOldValue() {
    return $this->get('old_value')->value;
  }

  /**
   * Setter for old field value.
   *
   * @param mixed $old_value
   *   Old value.
   *
   * @return \Drupal\entity_log\Entity\EntityLog
   *   EntityLog.
   */
  public function setOldValue($old_value) {
    $this->set('old_value', $old_value);
    return $this;
  }

  /**
   * Getter for new field value.
   *
   * @return mixed
   *   New field value.
   */
  public function getNewValue() {
    return $this->get('new_value')->value;
  }

  /**
   * Setter for new field value.
   *
   * @param mixed $new_value
   *   New field value.
   *
   * @return \Drupal\entity_log\Entity\EntityLog
   *   EntityLog.
   */
  public function setNewValue($new_value) {
    $this->set('new_value', $new_value);
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
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * Getter for hostname.
   *
   * @return mixed
   *   Hostname.
   */
  public function getHostname() {
    return $this->get('hostname')->value;
  }

  /**
   * Setter for hostname.
   *
   * @return \Drupal\entity_log\Entity\EntityLog
   *   EntityLog.
   */
  public function setHostname() {
    $this->set('hostname', \Drupal::request()->getClientIp());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Entity log entity.'))
      ->setRevisionable(FALSE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
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

    $fields['entity_logged_id'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('Entity logged'))
      ->setDescription(t('Entity which is being logged.'))
      ->setRevisionable(FALSE)
      ->setSetting('handler', 'default')
      ->setTranslatable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
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

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Field label'))
      ->setDescription(t('The field label that is being logged.'))
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
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['old_value'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Old field value'))
      ->setDescription(t('Old field value'))
      ->setDefaultValue('')
      ->setSettings([
        'text_processing' => 0,
        'type' => 'text',
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['new_value'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('New field value'))
      ->setDescription(t('New field value'))
      ->setDefaultValue('')
      ->setSettings([
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of field that is being logged in the Entity log entity.'))
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
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['log_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Log Type'))
      ->setDescription(t('The Type of the Entity log entity.'))
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
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['hostname'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hostname'))
      ->setDescription(t('IP address from user that logged this.'))
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
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Entity log is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));
    return $fields;
  }

}
