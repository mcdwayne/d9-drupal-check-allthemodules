<?php

namespace Drupal\entity_graph;

interface EntityGraphInterface {

  /**
   * Returns list of entities that reference the given entity and all their
   * neighbours and their neighbours up to a point when the function returns
   * true.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The root entity.
   * @param callable $matcher
   *   A function returning true then an entity matches criteria. Optional.
   *   Defaults to return true;
   * @param int $maxDepth
   *   How deep to go with the search. -1 means no limit. Defaults to 5.
   * @return \Drupal\entity_graph\EntityGraphNodeInterface
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getGraphNodeWithNeighbourhood($entity, $matcher = NULL, $maxDepth = 5);

}
