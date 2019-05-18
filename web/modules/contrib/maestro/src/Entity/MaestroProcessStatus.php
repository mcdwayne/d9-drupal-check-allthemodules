<?php

namespace Drupal\maestro\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\maestro\MaestroProcessInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedTrait;

//TODO: need the list builder for the Process Status entity

/**
 * Defines the MaestroProcessStatus entity.
 * 
 * We have no forms for this entity as this entity is managed by the Maestro engine.
 * Deletions, additions, alterations are managed by Maestro, not natively in Drupal.
 *  *
 * @ingroup maestro
 *
 * @ContentEntityType(
 *   id = "maestro_process_status",
 *   label = @Translation("Maestro Process Status"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\maestro\Entity\Controller\MaestroProcessStatusListBuilder",
 *     "form" = {
 *     },
 *     "access" = "Drupal\maestro\MaestroProcessStatusAccessControlHandler",
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "maestro_process_status",
 *   admin_permission = "administer maestro_process_status entity",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 * )
 *
 * 
 */
class MaestroProcessStatus extends ContentEntityBase implements MaestroProcessInterface {

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
      ->setDescription(t('The unique ID of the Maestro Process Status entry.'))
      ->setReadOnly(TRUE);
   
    //relation/entity ref to process
    $fields['process_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Process ID'))
      ->setDescription(t('The process ID this status belongs to.'))
      ->setSetting('target_type', 'maestro_process')
      ->setSetting('handler', 'default');

    //the numerical stage for the message.  This comes from the template tasks originally
    $fields['stage_number'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Stage Number'))
      ->setDescription(t('The integer stage number.'));
      
    
    //the message associated to the stage number
    $fields['stage_message'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Stage Message'))
      ->setDescription(t('The status message to show for this stage.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ));
      
    //completion time stamp
    $fields['completed'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Completed'))
      ->setDescription(t('The time that the task associated to this status was completed.'))
      ->setSettings(array(
        'default_value' => '0',
      ));
      
    return $fields;
  }

}
