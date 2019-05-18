<?php

namespace Drupal\entity_graph;

class EntityGraphEdge implements EntityGraphEdgeInterface {

  // TODO: Add edge_type. It should be an object holding the field name, type, label, etc. It could be implemented as plugins that also add to the initial query and create themselves from the entity.

  /**
   * The node in which the edge starts (referencing entity).
   *
   * @var \Drupal\entity_graph\EntityGraphNodeInterface
   */
  protected $sourceNode;

  /**
   * The node in which the edge ends (referenced entity).
   *
   * @var \Drupal\entity_graph\EntityGraphNodeInterface
   */
  protected $targetNode;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityGraphNodeInterface $sourceNode, EntityGraphNodeInterface $targetNode) {
    $this->sourceNode = $sourceNode;
    $this->targetNode = $targetNode;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceNode() {
    return $this->sourceNode;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetNode() {
    return $this->targetNode;
  }

}
