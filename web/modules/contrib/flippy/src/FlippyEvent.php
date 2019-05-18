<?php

namespace Drupal\flippy;

use Symfony\Component\EventDispatcher\Event;
use Drupal\node\NodeInterface;

class FlippyEvent extends Event {

  protected $queries;
  protected $node;

  /**
   * FlippyEvent constructor.
   *
   * @param array $queries
   * @param \Drupal\node\NodeInterface $node
   */
  public function __construct(Array $queries, NodeInterface $node) {
    $this->queries = $queries;
    $this->node = $node;
  }

  /**
   * Getter for query array.
   *
   * @return array
   */
  public function getQueries() {
    return $this->queries;
  }

  /**
   * Setter for query array.
   *
   * @param array $queries
   */
  public function setQueries($queries) {
    $this->queries = $queries;
  }

  /**
   * Getter for node.
   *
   * @return \Drupal\node\NodeInterface
   */
  public function getNode() {
    return $this->node;
  }

}
