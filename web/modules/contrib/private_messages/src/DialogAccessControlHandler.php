<?php

namespace Drupal\private_messages;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\private_messages\Entity\DialogInterface;

/**
 * Access controller for the Dialog entity.
 *
 * @see \Drupal\private_messages\Entity\Dialog.
 */
class DialogAccessControlHandler extends EntityAccessControlHandler
{
  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {

      case 'view':
        if ($entity->getOwnerId() == $account->id()) {
          return AccessResult::allowed();
        }

        if ($entity->getRecipientId() == $account->id()) {
          return AccessResult::allowed();
        }
        return AccessResult::forbidden();
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(
    AccountInterface $account,
    array $context,
    $entity_bundle = null
  ) {
    return AccessResult::allowedIfHasPermission(
      $account,
      'use private messages'
    );
  }

}
