<?php

namespace Drupal\timed_node_page\Repository;

use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Interface TimedNodeRepositoryInterface.
 *
 * @package Drupal\timed_node_page\Repository
 */
interface TimedNodeRepositoryInterface {

  /**
   * Loads the current node.
   *
   * @param string $bundle
   *   The bundle of the current node we are trying to load.
   * @param string $startField
   *   The name of the start field on the node.
   * @param string $endField
   *   The name of the end field on the node.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity or null.
   */
  public function getCurrentNode($bundle, $startField, $endField = NULL);

  /**
   * Loads the next node that has to be set as current timed node.
   *
   * @param string $bundle
   *   The bundle of the node.
   * @param string $startField
   *   The name of the start field on the node.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity or null.
   */
  public function getNextNode($bundle, $startField);

  /**
   * Executes the query and loads the results.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   An entity or null.
   */
  public function execute(QueryInterface $query);

}
