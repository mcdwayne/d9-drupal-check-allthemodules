<?php

namespace Drupal\sign_for_acknowledgement\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\node\NodeInterface;
use Symfony\Component\Routing\Route;

/**
 * Check the access to a node task based on the node type.
 */
class NodeTypeAccessCheck implements AccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return NULL;
  }

  /**
   * A custom access check.
   * @param Route $route
   * @param int $node node id (nid)
   *   Run access checks for this node.
   */
  public function access(Route $route,  $node) {
    $node =  \Drupal\node\Entity\Node::load($node);
    $fieldman = \Drupal::service('sign_for_acknowledgement.field_manager');
    return $fieldman->appliesToBundle($node->bundle())? AccessResult::allowed() : AccessResult::forbidden();
  }
}
