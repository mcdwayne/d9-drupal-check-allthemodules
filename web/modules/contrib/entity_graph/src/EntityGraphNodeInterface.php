<?php

namespace Drupal\entity_graph;

use Drupal\Core\Entity\EntityInterface;

interface EntityGraphNodeInterface {

  /**
   * EntityGraphNode constructor.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity behind this graph node.
   * @param EntityGraphNodeInterface[] $incomingEdges
   *   An array of GraphNodes that reference the current node.
   */
  public function __construct(EntityInterface $entity, $incomingEdges);

  /**
   * Returns the underlying entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getEntity();

  /**
   * Returns a list of EntityGraphNodes that point to this EntityGraphNode.
   *
   * @return \Drupal\entity_graph\EntityGraphEdgeInterface[]
   */
  public function getIncomingEdges();

  /**
   * Sets the incomingEdges.
   *
   * @param \Drupal\entity_graph\EntityGraphEdgeInterface[] $incomingEdges
   *   An array of EntityGraphNodes that point to this EntityGraphNode.
   */
  public function setIncomingEdges($incomingEdges);

}
