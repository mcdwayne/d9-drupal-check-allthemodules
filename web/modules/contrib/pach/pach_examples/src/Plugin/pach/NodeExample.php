<?php

namespace Drupal\pach_examples\Plugin\pach;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\pach\Plugin\AccessControlHandlerBase;

/**
 * Example access control handler plugin for nodes.
 *
 * @AccessControlHandler(
 *   id = "node_example",
 *   type = "node",
 *   weight = -10
 * )
 */
class NodeExample extends AccessControlHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function applies(EntityInterface $entity, $operation, AccountInterface $account = NULL) {
    /* @var $entity \Drupal\node\NodeInterface */
    // Applies to all nodes of type "article".
    return 'article' === $entity->getType();
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccessResultInterface &$access, EntityInterface $entity, $operation, AccountInterface $account = NULL) {
    /* @var $entity \Drupal\node\NodeInterface */
    if ('update' === $operation && strpos($entity->getTitle(), 'test') !== FALSE) {
      // Generally deny edit access to all nodes having the word "test" in its
      // title.
      $access = $access->andIf(AccessResult::forbidden());
    }
  }

}
