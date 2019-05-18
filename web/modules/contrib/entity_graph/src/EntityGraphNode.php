<?php

namespace Drupal\entity_graph;

use Drupal\Core\Entity\EntityInterface;

class EntityGraphNode implements EntityGraphNodeInterface {

  /**
   * The entity that this object represents.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * A list of EntityGraphNodes that point to this EntityGraphNode.
   *
   * @var \Drupal\entity_graph\EntityGraphNode[]
   */
  protected $incomingEdges = NULL;

  /**
   * Creates an EntityGraphNode.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity behind this graph node.
   * @param EntityGraphEdgeInterface[] $incomingEdges
   *   An array of GraphNodes that reference the current node.
   */
  public function __construct(EntityInterface $entity, $incomingEdges = NULL) {
    $this->setEntity($entity);
    $this->setIncomingEdges($incomingEdges);
  }

  /**
   * Returns the underlying entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Returns a list of EntityGraphNodes that point to this EntityGraphNode.
   *
   * @return \Drupal\entity_graph\EntityGraphNode[]
   */
  public function getIncomingEdges() {
    return $this->incomingEdges;
  }

  /**
   * {@inheritdoc}
   */
  public function setIncomingEdges($incomingEdges) {
    if ($incomingEdges !== NULL) {
      foreach ($incomingEdges as $referencingGraphNode) {
        if (!$referencingGraphNode instanceof EntityGraphEdgeInterface) {
          throw new \InvalidArgumentException('Each incoming edge has to implement EntityGraphEdgeInterface.');
        }
      }
    }
    $this->incomingEdges = $incomingEdges;
  }

  /**
   * Sets the underlying entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  protected function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
  }

}
