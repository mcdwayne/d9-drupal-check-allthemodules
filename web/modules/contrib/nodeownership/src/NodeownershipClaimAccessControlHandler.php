<?php

namespace Drupal\nodeownership;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;

/**
 * Access controller for the nodeownership_claim entity.
 *
 * @see \Drupal\comment\Entity\Comment.
 */
class NodeownershipClaimAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $uid = $account->id();
    if ($uid == 1) {
      return AccessResult::allowed();
    }
    $node = \Drupal::routeMatch()->getParameter('node');
    if (!is_object($node)) {
      $node = Node::load($node);
    }
    $node_type = $node->getType();
    $claim_allowed_types = \Drupal::config('nodeownership.settings')->get('nodeownership_node_types');
    if ($claim_allowed_types[$node_type] == $node_type && $node->getOwnerId() != $uid) {
      return AccessResult::allowedIfHasPermission($account, 'add nodeownership_claim entity');
    }
    return AccessResult::forbidden();
  }

}
