<?php

namespace Drupal\chatbot;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Chatbot entity.
 *
 * @see \Drupal\chatbot\Entity\Chatbot.
 */
class ChatbotAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\chatbot\Entity\ChatbotInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished chatbot entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published chatbot entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit chatbot entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete chatbot entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add chatbot entities');
  }

}
