<?php

namespace Drupal\crm_core;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Trait implementing the various methods defined in EntityOwnerInterface.
 *
 * @see \Drupal\user\EntityOwnerInterface
 *
 * @todo Remove once the same trait is in Core.
 */
trait EntityOwnerTrait {

  /**
   * Defines 'uid' base field definition.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   A field definition object.
   */
  public static function getOwnerFieldDefinition() {
    return BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\crm_core\EntityOwnerTrait::getDefaultAuthorId');
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return int
   *   The user ID.
   */
  public static function getDefaultAuthorId() {
    return \Drupal::currentUser()->id();
  }

  /**
   * Returns the entity owner's user entity.
   *
   * @return \Drupal\user\UserInterface
   *   The owner user entity.
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * Sets the entity owner's user entity.
   *
   * @param \Drupal\user\UserInterface $account
   *   The owner user entity.
   *
   * @return $this
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * Returns the entity owner's user ID.
   *
   * @return int|null
   *   The owner user ID, or NULL in case the user ID field has not been set on
   *   the entity.
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * Sets the entity owner's user ID.
   *
   * @param int $uid
   *   The owner user id.
   *
   * @return $this
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

}
