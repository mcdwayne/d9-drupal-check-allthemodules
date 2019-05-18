<?php

namespace Drupal\entity_collector;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Entity collection entity.
 *
 * @see \Drupal\entity_collector\Entity\EntityCollection.
 */
class EntityCollectionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\entity_collector\Entity\EntityCollectionInterface $entity */
    $is_participant = in_array($account->id(), $entity->getParticipantsIds());
    $is_owner = $account->id() == $entity->getOwnerId();

    if ($operation == 'view') {
      return $this->checkViewAccess($account, $entity, $is_owner, $is_participant);
    }

    if ($operation == 'update') {
      return $this->checkUpdateAccess($account, $entity, $is_owner, $is_participant);
    }

    if ($operation == 'delete') {
      return $this->checkDeleteAccess($account, $entity, $is_owner);
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add entity collection entities');
  }

  /**
   * Check if the user is allowed to view the entity collection.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param $entity
   * @param $is_owner
   * @param $is_participant
   *
   * @return mixed
   */
  public function checkViewAccess(AccountInterface $account, $entity, $is_owner, $is_participant) {
    if (!$entity->isPublished()) {
      return AccessResult::allowedIfHasPermission($account, 'view unpublished entity collection entities');
    }
    return AccessResult::allowedIfHasPermission($account, 'view published entity collection entities')->andIf(AccessResult::allowedIf($is_owner || $is_participant));
  }

  /**
   * Check if the user is allowed to update the entity collection.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param $entity
   * @param $is_owner
   * @param $is_participant
   *
   * @return mixed
   */
  public function checkUpdateAccess(AccountInterface $account, $entity, $is_owner, $is_participant) {
    return AccessResult::allowedIfHasPermission($account, 'edit entity collection entities')->andIf(AccessResult::allowedIf($is_owner || $is_participant));

  }

  /**
   * Check if the user is allowed to delete. the entity collection.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param $entity
   * @param $is_owner
   *
   * @return mixed
   */
  public function checkDeleteAccess(AccountInterface $account, $entity, $is_owner) {
    return AccessResult::allowedIfHasPermission($account, 'delete entity collection entities')
      ->andIf(AccessResult::allowedIf($is_owner));
  }
}
