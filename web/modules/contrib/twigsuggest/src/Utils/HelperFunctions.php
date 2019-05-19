<?php

namespace Drupal\twigsuggest\Utils;

/**
 * Class HelperFunctions.
 *
 * @package Drupal\twigsuggest\Utils
 */
class HelperFunctions {

  /**
   * Get current node.
   *
   * Helper function to return current node no matter if viewing full node,
   * node preview or revision.
   *
   * @return bool|\Drupal\node\Entity\Node
   *   Returns the current node object or FALSE otherwise.
   */
  public static function getCurrentNode() {

    $node = FALSE;
    $route_match = \Drupal::routeMatch();

    if ($route_match->getRouteName() == 'entity.node.canonical') {
      $node = $route_match->getParameter('node');
    }
    elseif ($route_match->getRouteName() == 'entity.node.revision') {
      $revision_id = $route_match->getParameter('node_revision');
      $node = node_revision_load($revision_id);
    }
    elseif ($route_match->getRouteName() == 'entity.node.preview') {
      $node = $route_match->getParameter('node_preview');
    }

    return $node;
  }

}
