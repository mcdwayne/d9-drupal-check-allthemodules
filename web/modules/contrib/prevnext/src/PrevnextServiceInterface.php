<?php

namespace Drupal\prevnext;
use Drupal\node\Entity\Node;

/**
 * Interface PrevnextServiceInterface.
 *
 * @package Drupal\prevnext
 */
interface PrevnextServiceInterface {

  /**
   * Retrieves previous and next nids of a given node, if they exist.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node entity.
   *
   * @return array
   *   An array of prev/next nids of given node.
   */
  public function getPreviousNext(Node $node);

}
