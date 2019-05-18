<?php

namespace Drupal\graph_reference\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Provides an interface for defining Graph entities.
 */
interface GraphInterface extends ConfigEntityInterface {

  /**
   * Loads all the entity vertices in the graph that connect to the given entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $vertex
   * @return \Drupal\Core\Entity\FieldableEntityInterface[]
   */
  public function getEdgesOf(FieldableEntityInterface $vertex);

  /**
   * Loads all the entity vertices in the graph that connect to the given entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $vertex
   * @param \Drupal\Core\Entity\FieldableEntityInterface[] $edges
   * @return void
   */
  public function setEdgesOf(FieldableEntityInterface $vertex, array $edges);

  /**
   * The machine name of the reference field that this graph will inhabit.
   *
   * @return string
   */
  public function getReferenceFieldName();
}
