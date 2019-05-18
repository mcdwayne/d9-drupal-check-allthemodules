<?php

namespace Drupal\maestro\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\maestro\MaestroProcessInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedTrait;

//TODO: need the access controller for the Process Status entity
//TODO: need the list builder for the Process Status entity

/**
 * Defines the MaestroEntityIdentifiers entity.
 * 
 * We have no forms for this entity as this entity is managed by the Maestro engine.
 * Deletions, additions, alterations are managed by Maestro, not natively in Drupal.
 *  *
 * @ingroup maestro
 *
 * @ContentEntityType(
 *   id = "maestro_entity_identifiers",
 *   label = @Translation("Maestro Entity Identifiers"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\maestro\Entity\Controller\MaestroEntityIdentifiersListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *     },
 *     "access" = "Drupal\maestro\MaestroEntityIdentifiersAccessControlHandler",
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "maestro_entity_identifiers",
 *   admin_permission = "administer maestro_entity_identifiers entity",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 * )
 *
 * 
 */
class MaestroEntityIdentifiers extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   *
   * When a new entity instance is added, set the user_id entity reference to
   * the current user as the creator of the instance.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return NULL;
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
   * Get the completed time for the process
   */
  public function getCompletedTime() {
    return NULL;
  }
  
  /**
   * {@inheritdoc}
   *
   * Field properties defined here.
   *
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = [];
    //Auto increment ID
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('id'))
      ->setDescription(t('The unique ID of the Maestro Entity Identifiers entry.'))
      ->setReadOnly(TRUE);
   
    //relation/entity ref to process
    $fields['process_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Process ID'))
      ->setDescription(t('The process ID this entity identifier belongs to.'))
      ->setSetting('target_type', 'maestro_process')
      ->setSetting('handler', 'default');

    //the unique ID of the entity
    $fields['unique_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Unique ID'))
      ->setDescription(t('The unique identifier for the task to bind to the identifier.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ));
      
    //the entity type.  example, node, webform
    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity Type'))
      ->setDescription(t('The type of entity.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ));
      
    //the bundle associated to the entity type.  example, article, basic_page...
    $fields['bundle'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Bundle'))
      ->setDescription(t('The bundle associated to the entity_type.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ));
      
    //the bundle associated to the entity type.  example, article, basic_page...
    $fields['entity_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity ID'))
      ->setDescription(t('The ID of the entity.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ));
      
    return $fields;
  }

}
