<?php

namespace Drupal\maestro\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\maestro\MaestroProcessInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedTrait;

//TODO: need the access controller for the queue
//TODO: need the list builder for the queue
//TODO: consider adding list and edit capabilities 
/**
 * Defines the MaestroQueue entity.
 * 
 * We have no forms for this entity as this entity is managed by the Maestro engine.
 * Deletions, additions, alterations are managed by Maestro, not natively in Drupal.
 * However having the ability to delete/edit entities natively could be useful for debugging.
 * 
 *  *
 * @ingroup maestro
 *
 * @ContentEntityType(
 *   id = "maestro_queue",
 *   label = @Translation("Maestro Queue"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\maestro\Entity\Controller\MaestroQueue",
 *     "views_data" = "Drupal\maestro\Entity\MaestroQueueViewsData",
 *     "form" = {
 *     },
 *     "access" = "Drupal\maestro\MaestroQueueAccessControlHandler",
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "maestro_queue",
 *   admin_permission = "administer maestro queue entities",
 *   entity_keys = {
 *     "id" = "id",
 *   }
 * )
 *
 * 
 */
class MaestroQueue extends ContentEntityBase implements MaestroProcessInterface {

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
      ->setDescription(t('The ID of the Maestro Queue entry.'))
      ->setReadOnly(TRUE);
   
    $fields['process_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Process ID'))
      ->setDescription(t('The process ID this queue item belongs to.'))
      ->setSetting('target_type', 'maestro_process')
      ->setSetting('handler', 'default');

    //the class name of the task.  This comes from the template originally
    $fields['task_class_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Class Name'))
      ->setDescription(t('The class name of the task.'))
      ->setSettings(array(
          'default_value' => '',
          'max_length' => 255,
          'text_processing' => 0,
      ));
    
    //the task ID.  This comes from the template originally
    $fields['task_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Task ID'))
      ->setDescription(t('The machine name of the task.'))
      ->setSettings(array(
          'default_value' => '',
          'max_length' => 255,
          'text_processing' => 0,
      ));
      
    //the task label.  This comes from the template originally
    $fields['task_label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setDescription(t('The label of the task.'))
      ->setSettings(array(
          'default_value' => '',
          'max_length' => 255,
          'text_processing' => 0,
      ));
    
    $fields['engine_version'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Engine Version'))
      ->setDescription(t('Engine version. Default is 2.'))
      ->setSettings(array(
          'default_value' => '2',
      ));
      
    $fields['is_interactive'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Is Interactive'))
      ->setDescription(t('Is this an interactive type task.'))
      ->setSettings(array(
          'default_value' => '0',
      ));
      
    $fields['show_in_detail'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Show in Detail'))
      ->setDescription(t('Show this field in detail display outputs. Overridable by custom views'))
      ->setSettings(array(
          'default_value' => '0',
      ));
      
    $fields['handler'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Handler'))
      ->setDescription(t('Any special code to handle this task?'))
      ->setSettings(array(
          'default_value' => '',
          'max_length' => 255,
          'text_processing' => 0,
      ));
     
    $fields['task_data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Task Data'))
      ->setDescription(t('Serialized task data used by the task.'));
     
    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Status'))
      ->setDescription(t('0 is default meaning unexecuted.'))
      ->setSettings(array(
          'default_value' => '0',
      ));
   
    $fields['archived'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Archived'))
      ->setDescription(t('0 is default meaning not archived.'))
      ->setSettings(array(
          'default_value' => '0',
      ));
      
    $fields['run_once'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('RunOnce flag'))
      ->setDescription(t('Tells the engine to only run this once rather than each and every time.'))
      ->setSettings(array(
          'default_value' => '0',
      ));
      
    $fields['uid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('User ID'))
      ->setDescription(t('User ID associated with this queue item creation.'))
      ->setSettings(array(
          'default_value' => '0',
      ));
      
    //the created, started, changed and completed fields
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the queue item was created.'));
     
    $fields['started_date'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Started Date'))
      ->setDescription(t('The time that the queue item was started.'))
      ->setSettings(array(
          'default_value' => '0',
      ));
      
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the queue item entity was last edited.'));
      
    $fields['completed'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Completed'))
      ->setDescription(t('The time that the queue item was completed.'))
      ->setSettings(array(
          'default_value' => '0',
      ));
      
      
    //escalations and reminders
    $fields['next_reminder_time'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Next Reminder Time'))
      ->setDescription(t('The time that the queue item should send its next reminder.'))
      ->setSettings(array(
          'default_value' => '0',
      ));
      
    $fields['num_reminders_sent'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('# Reminders sent'))
      ->setDescription(t('The number of reminders that have been sent.'))
      ->setSettings(array(
          'default_value' => '0',
      ));
    
    $fields['last_escalation_time'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Last escalation Time'))
      ->setDescription(t('The last time that the queue item sent out an escalation.'))
      ->setSettings(array(
          'default_value' => '0',
      ));
      
    $fields['num_escalations_sent'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('# escalations sent'))
      ->setDescription(t('The number of escalations that have been sent.'))
      ->setSettings(array(
          'default_value' => '0',
      ));
      
    $fields['reminder_interval'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Reminder Interval'))
      ->setDescription(t('The interval in days for a reminder.'))
      ->setSettings(array(
          'default_value' => '0',
      ));
      
    $fields['escalation_interval'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Escalation Interval'))
      ->setDescription(t('The interval in days for an escalation.'))
      ->setSettings(array(
          'default_value' => '0',
      ));
      
    return $fields;
  }

}
