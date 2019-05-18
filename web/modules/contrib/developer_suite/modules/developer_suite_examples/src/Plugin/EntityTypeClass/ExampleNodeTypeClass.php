<?php

namespace Drupal\developer_suite_examples\Plugin\EntityTypeClass;

use Drupal\developer_suite\Storage\NodeStorage;
use Drupal\developer_suite_examples\Entity\ExampleArticleNode;
use Drupal\developer_suite_examples\Entity\ExamplePageNode;
use Drupal\node\Entity\Node;

/**
 * Class NodeTypeStorage.
 *
 * @package Drupal\developer_suite_examples\EntityStorage
 *
 * @EntityTypeClass(
 *   id = "node_type_class",
 *   entity = "node",
 *   label = @Translation("Node type class"),
 * )
 */
class ExampleNodeTypeClass extends NodeStorage {

  /**
   * Returns the entity class per node type.
   *
   * @param string $type
   *   The node type.
   *
   * @return mixed
   *   The entity class.
   */
  public function getEntityTypeClass($type) {
    switch ($type) {
      case 'page':
        return ExamplePageNode::class;

      case 'article':
        return ExampleArticleNode::class;
    }

    return Node::class;
  }

}
