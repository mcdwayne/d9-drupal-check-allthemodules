<?php

namespace Drupal\entity_graph;

interface EntityGraphEdgeInterface {

  /**
   * Creates an EntityGraphNode.
   *
   * @param \Drupal\entity_graph\EntityGraphNodeInterface $sourceNode
   *    The node in which the edge starts (referencing entity).
   * @param \Drupal\entity_graph\EntityGraphNodeInterface $targetNode
   *   The node in which the edge ends (referenced entity).
   */
  public function __construct(EntityGraphNodeInterface $sourceNode, EntityGraphNodeInterface $targetNode);

  /**
   * Returns the underlying entity.
   *
   * @return \Drupal\entity_graph\EntityGraphNodeInterface
   */
  public function getSourceNode();

  /**
   * Returns the underlying entity.
   *
   * @return \Drupal\entity_graph\EntityGraphNodeInterface
   */
  public function getTargetNode();

}
