<?php

namespace Drupal\cloudwords;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Cloudwords translatable entity.
 *
 * @see \Drupal\cloudwords\Entity\CloudwordsTranslatable.
 */
class CloudwordsTranslatableAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\cloudwords\Entity\CloudwordsTranslatableInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'manage cloudwords projects');
        }
        return AccessResult::allowedIfHasPermission($account, 'manage cloudwords projects');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'manage cloudwords projects');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'manage cloudwords projects');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'manage cloudwords projects');
  }

}
