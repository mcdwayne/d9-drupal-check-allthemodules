<?php

namespace Drupal\prevnext;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\node\Entity\Node;

/**
 * Class PrevnextService.
 *
 * @package Drupal\prevnext
 */
class PrevnextService implements PrevnextServiceInterface {

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Previous / Next nids.
   *
   * @var array
   */
  public $prevnext;

  /**
   * PrevnextService constructor.
   *
   * @param QueryFactory $query
   *   The entity query instance.
   */
  public function __construct(QueryFactory $query) {
    $this->queryFactory = $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviousNext(Node $node) {
    $nodes = $this->getNodesOfType($node);
    $current_nid = $node->id();

    $current_key = array_search($current_nid, $nodes);
    $this->prevnext['prev'] = ($current_key == 0) ? '' : $nodes[$current_key - 1];
    $this->prevnext['next'] = ($current_key == count($nodes) - 1) ? '' : $nodes[$current_key + 1];

    return $this->prevnext;
  }

  /**
   * Retrieves all nodes of the same type and language of given.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node entity.
   *
   * @return array $nodes
   *   An array of nodes filtered by type, status and language.
   */
  protected function getNodesOfType(Node $node) {
    $query = $this->queryFactory->get('node');
    $bundle = $node->bundle();
    $langcode = $node->language()->getId();
    $nodes = $query->condition('status', NODE_PUBLISHED)
      ->condition('type', $bundle)
      ->condition('langcode', $langcode)
      ->addMetaData('type', $bundle)
      ->addMetaData('langcode', $langcode)
      ->addTag('prev_next_nodes_type')
      ->execute();

    return array_values($nodes);
  }

}
