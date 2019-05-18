<?php

namespace Drupal\entity_extra\Entity;

use Drupal\user\UserInterface;

/**
 * A trait for entities that have an owner referenced by an entity reference 
 * field named 'owner'.
 */
trait EntityOwnerTrait {

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    $account = NULL;
    $entity_type = $this->getEntityType();
    if ($entity_type->hasKey('owner')) {
      $field_name = $entity_type->getKey('owner');
      $account = $this->get($field_name)->entity;
    }
    return $account;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    $uid = NULL;
    $entity_type = $this->getEntityType();
    if ($entity_type->hasKey('owner')) {
      $field_name = $entity_type->getKey('owner');
      $uid = $this->get($field_name)->target_id;
    }
    return $uid;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $entity_type = $this->getEntityType();
    if ($entity_type->hasKey('owner')) {
      $field_name = $entity_type->getKey('owner');
      $this->get($field_name)->entity = $account;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $entity_type = $this->getEntityType();
    if ($entity_type->hasKey('owner')) {
      $field_name = $entity_type->getKey('owner');
      $this->get($field_name)->target_id = $uid;
    }
  }

}
