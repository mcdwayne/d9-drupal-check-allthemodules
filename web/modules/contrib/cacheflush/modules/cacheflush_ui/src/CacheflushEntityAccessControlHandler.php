<?php

namespace Drupal\cacheflush_ui;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Cacheflush entity.
 *
 * @see \Drupal\cacheflush_ui\Entity\CacheflushEntity.
 */
class CacheflushEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'clear':
        return $this->checkSingleToMany('cacheflush clear any', 'cacheflush clear own', $account, $entity);

      case 'view':
        return $this->checkSingleToMany('cacheflush view any', 'cacheflush view own', $account, $entity);

      case 'update':
        return $this->checkSingleToMany('cacheflush edit any', 'cacheflush edit own', $account, $entity);

      case 'delete':
        return $this->checkSingleToMany('cacheflush delete any', 'cacheflush delete own', $account, $entity);
    }

    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'cacheflush create new');
  }

  /**
   * Check access for 'any' and 'own' permissions.
   *
   * @param string $any
   *   Permission string for any content.
   * @param string $single
   *   Permission string for own content.
   * @param object $account
   *   User account to check.
   * @param object $entity
   *   The entity object.
   *
   * @return bool
   *   Return TRUE if access is granted.
   */
  protected function checkSingleToMany($any, $single, $account, $entity) {
    return AccessResult::allowedIfHasPermission($account, $any)
      ->orIf(AccessResult::allowedIfHasPermission($account, $single)
        ->andIf($this->checkOwner($account, $entity)));
  }

  /**
   * Check entity owner.
   *
   * @param object $account
   *   User account to check.
   * @param object $entity
   *   The entity object.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Access result.
   */
  protected function checkOwner($account, $entity) {
    if ($account->id() == $entity->getOwnerId()) {
      return AccessResult::allowed();
    }
    return AccessResult::neutral();
  }

}
