<?php

namespace Drupal\maestro\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\maestro\MaestroProcessInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedTrait;

//TODO: need the access controller for the assignments
//TODO: need the list builder for the assignments
//TODO: consider adding list and edit capabilities 
/**
 * Defines the MaestroProductionAssignments entity.
 * 
 * We have no forms for this entity as this entity is managed by the Maestro engine.
 * Deletions, additions, alterations are managed by Maestro, not natively in Drupal.
 * However having the ability to delete/edit entities natively could be useful for debugging.
 * 
 *  *
 * @ingroup maestro
 *
 * @ContentEntityType(
 *   id = "maestro_production_assignments",
 *   label = @Translation("Maestro Production Assignments"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\maestro\Entity\Controller\MaestroProductionAssignments",
 *     "views_data" = "Drupal\maestro\Entity\MaestroProductionAssignmentsViewsData",
 *     "form" = {
 *     },
 *     "access" = "Drupal\maestro\MaestroProductionAssignmentsAccessControlHandler",
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "maestro_production_assignments",
 *   admin_permission = "administer production assignment entities",
 *   entity_keys = {
 *     "id" = "id",
 *   }
 * )
 *
 * 
 */
class MaestroProductionAssignments extends ContentEntityBase implements MaestroProcessInterface {

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

    //Auto increment queue ID
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('id'))
      ->setDescription(t('The ID of the Maestro production assignment entry.'))
      ->setReadOnly(TRUE);
   
    $fields['queue_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Queue ID'))
      ->setDescription(t('The task ID this assignment item belongs to.'))
      ->setSetting('target_type', 'maestro_queue')
      ->setSetting('handler', 'default');

    $fields['assign_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Assign Type'))
      ->setDescription(t('The machine name of the assignment. eg. role, user, group, future value.'))
      ->setSettings(array(
          'default_value' => '',
          'max_length' => 255,
          'text_processing' => 0,
      ));
      
    $fields['by_variable'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Assign By Variable'))
      ->setDescription(t('Set to 0 for fixed. 1 for by variable.'))
      ->setSettings(array(
          'default_value' => '0',
      ));
      
    //TODO: this is assigned by the machine name such as the user name or the role name.
    $fields['assign_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Assign ID'))
      ->setDescription(t('The ID of the entity this is assigned to.'))
      ->setSettings(array(
          'default_value' => '0',
      ));
      
    $fields['process_variable'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Process Variable ID'))
      ->setDescription(t('The process variable used in this assignment.'))
      ->setSetting('target_type', 'maestro_process_variables')
      ->setSetting('handler', 'default');
      
    $fields['assign_back_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Assign Back'))
      ->setDescription(t('Who to re-assign this to if it was reassigned'))
      ->setSettings(array(
          'default_value' => '0',
      ));
    
    $fields['task_completed'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Has this task been completed'))
      ->setDescription(t('Set to 0 for not. 1 for completed.'))
      ->setSettings(array(
          'default_value' => '0',
      ));
      
      
    //the created, started, changed and completed fields
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the queue item was created.'));
     
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the queue item entity was last edited.'));
      
    return $fields;
  }

}
