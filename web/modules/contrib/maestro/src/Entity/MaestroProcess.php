<?php

namespace Drupal\maestro\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\maestro\MaestroProcessInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedTrait;

//TODO: need the access controller for the process entity
//TODO: need the list builder for the process entity

/**
 * Defines the MaestroProcess entity.
 * 
 * We have no forms for this entity as this entity is managed by the Maestro engine.
 * Deletions, additions, alterations are managed by Maestro, not natively in Drupal.
 *  *
 * @ingroup maestro
 *
 * @ContentEntityType(
 *   id = "maestro_process",
 *   label = @Translation("Maestro Process"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\maestro\Entity\Controller\MaestroProcessListBuilder",
 *     "views_data" = "Drupal\maestro\Entity\MaestroProcessViewsData",
 *     "form" = {
 *     },
 *     "access" = "Drupal\maestro\MaestroProcessAccessControlHandler",
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "maestro_process",
 *   entity_keys = {
 *     "id" = "process_id",
 *     "label" = "process_name",
 *     "uuid" = "uuid"
 *   },
 *   field_ui_base_route = "maestro.maestro_process_settings",
 * )
 *
 * 
 */
class MaestroProcess extends ContentEntityBase implements MaestroProcessInterface {

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
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('initiator_uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('initiator_uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('initiator_uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('initiator_uid', $account->id());
    return $this;
  }

  /**
   * Get the completed time for the process
   */
  public function getCompletedTime() {
    return $this->get('completed')->value;
  }
  
  /**
   * {@inheritdoc}
   *
   * Field properties defined here.
   *
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    //Auto increment process ID
    $fields['process_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ProcessID'))
      ->setDescription(t('The ID of the Maestro Process.'))
      ->setReadOnly(TRUE);

    // UUID for the process (really required?  perhaps for cross site comparison purposes)
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Maestro Process entity.'))
      ->setReadOnly(TRUE);

    //the name for the process.  Carried over by the template
    $fields['process_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Process Name'))
      ->setDescription(t('The Process Name. Carried from the Template.'))
      ->setSettings(array(
          'default_value' => '',
          'max_length' => 255,
          'text_processing' => 0,
      ));
    
    //the machine name (id) of the template being 
    $fields['template_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Template Machine Name/ID'))
      ->setDescription(t('Machine name of the template.'))
      ->setSettings(array(
          'default_value' => '',
          'max_length' => 255,
          'text_processing' => 0,
      ));
    
    //completion flag
    //0 is incomplete.  1 is complete.
    $fields['complete'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Complete Flag'))
      ->setDescription(t('Completion flag'))
      ->setSettings(array(
          'default_value' => '0',
      ));
    
    //initiator UID.  The UID of the person who started it.
    //0 for Maestro.  This is also mimicked in the initiator variable
    $fields['initiator_uid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('User ID of the initiator.'))
      ->setDescription(t('Initiator User ID'))
      ->setSettings(array(
          'default_value' => '0',
      ));
      
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the process was created.'));
    
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the process entity was last edited.'));
      
    $fields['completed'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Completed'))
      ->setDescription(t('The time that the process was completed.'))
      ->setSettings(array(
          'default_value' => '0',
      ));
      
    return $fields;
  }

}
