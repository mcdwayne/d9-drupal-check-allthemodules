<?php

namespace Drupal\iots_measure;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Iots Measure entity.
 *
 * @see \Drupal\iots_measure\Entity\IotsMeasure.
 */
class IotsMeasureAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\iots_measure\Entity\IotsMeasureInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished iots measure entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published iots measure entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit iots measure entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete iots measure entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add iots measure entities');
  }

}
