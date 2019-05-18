<?php

namespace Drupal\graph_reference\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Graph edge entities.
 *
 * @ingroup graph_reference
 */
interface GraphEdgeInterface extends  ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Graph edge creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Graph edge.
   */
  public function getCreatedTime();

  /**
   * Sets the Graph edge creation timestamp.
   *
   * @param int $timestamp
   *   The Graph edge creation timestamp.
   *
   * @return \Drupal\graph_reference\Entity\GraphEdgeInterface
   *   The called Graph edge entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * The graph that this entity is part of
   *
   * @return \Drupal\graph_reference\Entity\GraphInterface
   */
  public function getGraph();

  /**
   * One of the two vertices that this edge connects.
   * Because this implementation is ambiguous to the direction of the conection
   * the name given to this vertex is A.
   *
   * Note: This method can cause recursion if it is called when one of its
   * vertices is being loaded.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface
   */
  public function getVertexA();

  /**
   * One of the two vertices that this edge connects.
   * Because this implementation is ambiguous to the direction of the conection
   * the name given to this vertex is B.
   *
   * Note: This method can cause recursion if it is called when one of its
   * vertices is being loaded.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface
   */
  public function getVertexB();

  /**
   * Returns the other vertex of the edge.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $vertex
   *   One of the vertices in the edge. This vertex will be cached to avoid
   *   recursions.
   * @return \Drupal\Core\Entity\FieldableEntityInterface
   */
  public function getOtherVertex(FieldableEntityInterface $vertex);

}
