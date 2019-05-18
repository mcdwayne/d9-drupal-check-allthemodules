<?php

namespace Drupal\uc_product\Plugin\Condition;

use Drupal\node\NodeInterface;
use Drupal\rules\Core\RulesConditionBase;

/**
 * Provides a 'Node is a product' condition.
 *
 * @Condition(
 *   id = "node_is_product",
 *   label = @Translation("Node is a product"),
 *   description = @Translation("Determines if the content type (node) is a product content type."),
 *   category = @Translation("Node"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node",
 *       label = @Translation("Content"),
 *       description = @Translation("The data to be checked to be empty, specified by using a data selector, e.g. 'node:uid:entity:name:value'.")
 *     )
 *   }
 * )
 * @todo 'access callback' => 'rules_node_integration_access',
 */
class NodeIsProduct extends RulesConditionBase {

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Content is product');
  }

  /**
   * Evaluates if the node is a product content type.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return bool
   *   TRUE if the node is a product.
   */
  public function doEvaluate(NodeInterface $node) {
    return in_array($node->getType(), uc_product_types());
  }

  /**
   * Provides the content types of products as asserted metadata.
   */
  public function uc_product_rules_condition_node_is_product_assertions($element) {
    return ['node' => ['bundle' => uc_product_types()]];
  }

}
