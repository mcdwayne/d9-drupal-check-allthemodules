<?php

namespace Drupal\mail_entity_queue;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\mail_entity_queue\Entity\MailEntityQueueItemInterface;

/**
 * Access controller for the mail entity queue item entity.
 */
class MailEntityQueueItemAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\mail_entity_queue\Entity\MailEntityQueueItemInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view mail entity queue items');

      case 'update':
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer mail entity queue items');

      case 'process':
        return AccessResult::allowedIf(
          (integer) $entity->getStatus() !== MailEntityQueueItemInterface::SENT &&
          $account->hasPermission('administer mail entity queue items')
        );

      default:
        // Unknown operation, no opinion.
        return AccessResult::neutral();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'administer mail entity queue items');
  }

}
