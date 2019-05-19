<?php

namespace Drupal\timed_node_page\Repository;

use Drupal\timed_node_page\Service\TimedNodeDateFormatter;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Class TimedNodeRepository.
 *
 * @package Drupal\timed_node_page\Repository
 */
class TimedNodeRepository implements TimedNodeRepositoryInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The timed node date formatter service.
   *
   * @var \Drupal\timed_node_page\Service\TimedNodeDateFormatter
   */
  protected $timedNodeDateFormatter;

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $nodeStorage;

  /**
   * TimedNodeRepository constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\timed_node_page\Service\TimedNodeDateFormatter $timedNodeDateFormatter
   *   The timed node date formatter service.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    TimedNodeDateFormatter $timedNodeDateFormatter
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->timedNodeDateFormatter = $timedNodeDateFormatter;
    $this->nodeStorage = $this->entityTypeManager->getStorage('node');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(QueryInterface $query) {
    $result = NULL;
    $nids = $query->execute();

    if ($nids) {
      $nid = reset($nids);
      $result = $this->nodeStorage->load($nid);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentNode($bundle, $startField, $endField = NULL) {
    $baseQuery = $this->getBaseQuery();
    $baseQuery->condition('type', $bundle);
    $baseQuery->sort($startField, 'DESC');
    $baseQuery->condition($startField, $this->timedNodeDateFormatter->formatDateForField($startField, $bundle), '<=');

    // If we have an end date field as-well then make sure we don't load already
    // ended nodes.
    if ($endField) {
      $baseQuery->condition($baseQuery->orConditionGroup()
        ->notExists($endField)
        ->condition($endField, $this->timedNodeDateFormatter->formatDateForField($endField, $bundle), '>='));
    }

    return $this->execute($baseQuery);
  }

  /**
   * {@inheritdoc}
   */
  public function getNextNode($bundle, $startField) {
    $baseQuery = $this->getBaseQuery();
    $baseQuery->condition('type', $bundle);
    $baseQuery->sort($startField, 'ASC');
    $baseQuery->condition($startField, $this->timedNodeDateFormatter->formatDateForField($startField, $bundle), '>');

    return $this->execute($baseQuery);
  }

  /**
   * Returns the base query.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface|mixed
   *   The query object.
   */
  public function getBaseQuery() {
    return $this->nodeStorage->getQuery()
      ->condition('status', 1)
      ->range(0, 1);
  }

}
