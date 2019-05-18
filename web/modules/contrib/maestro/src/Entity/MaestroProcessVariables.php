<?php

namespace Drupal\maestro\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\maestro\MaestroProcessInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedTrait;

//TODO: need the access controller for the variables entity
//TODO: need the list builder for the variables entity

/**
 * Defines the MaestroProcessVariables entity.
 * 
 * We have no forms for this entity as this entity is managed by the Maestro engine.
 * Deletions, additions, alterations are managed by Maestro, not natively in Drupal.
 *  *
 * @ingroup maestro
 *
 * @ContentEntityType(
 *   id = "maestro_process_variables",
 *   label = @Translation("Maestro Process Variables"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\maestro\Entity\Controller\MaestroProcessListBuilder",
 *     "form" = {
 *     },
 *     "access" = "Drupal\maestro\MaestroProcessAccessControlHandler",
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "maestro_process_variables",
 *   admin_permission = "administer maestro_process entity",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 * )
 *
 * 
 */
class MaestroProcessVariables extends ContentEntityBase implements MaestroProcessInterface {

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

    //Auto increment process ID
    $fields['id'] = BaseFieldDefinition::create('integer')
    ->setLabel(t('id'))
    ->setDescription(t('The ID of the Maestro Process Variable.'))
    ->setReadOnly(TRUE);
   
    $fields['process_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Process ID'))
      ->setDescription(t('The process ID this variable belongs to.'))
      ->setSetting('target_type', 'maestro_process')
      ->setSetting('handler', 'default');

    //the name of the variable.  This comes from the template originally
    $fields['variable_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the variable.'))
      ->setSettings(array(
          'default_value' => '',
          'max_length' => 255,
          'text_processing' => 0,
      ));
    
    //the value of the variable
    $fields['variable_value'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Value'))
      ->setDescription(t('The value of the variable.'))
      ->setSettings(array(
          'default_value' => '',
          'max_length' => 255,
          'text_processing' => 0,
      ));
      
      
    return $fields;
  }

}
