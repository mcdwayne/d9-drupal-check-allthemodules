<?php

namespace Drupal\slack_rtm;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Slack RTM Message entity.
 *
 * @see \Drupal\slack_rtm\Entity\SlackRtmMessage.
 */
class SlackRtmMessageAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\slack_rtm\Entity\SlackRtmMessageInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view slack rtm messages');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete slack rtm messages');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add slack rtm messages');
  }

}
