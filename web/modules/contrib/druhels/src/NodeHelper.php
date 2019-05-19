<?php

namespace Drupal\druhels;

use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

class NodeHelper {

  /**
   * Return TRUE if current page is a node page.
   *
   * @return bool
   */
  public static function isNodePage($node_type = NULL) {
    static $current_node_type;

    if ($current_node_type === NULL) {
      $route_match = \Drupal::routeMatch();
      if ($route_match->getRouteName() == 'entity.node.canonical') {
        $current_node_type = $route_match->getParameter('node')->bundle();
      }
      else {
        $current_node_type = FALSE;
      }
    }

    return $node_type ? $current_node_type == $node_type : (bool)$current_node_type;
  }

  /**
   * Return current node in node page.
   *
   * @return NodeInterface|NULL
   */
  public static function getCurrentNode() {
    return \Drupal::routeMatch()->getParameter('node');
  }

  /**
   * Return node build array.
   *
   * @param NodeInterface|integer $node - Node object or nid.
   *
   * @return array Node render array.
   */
  public static function view($node, $view_mode = 'full') {
    if (is_numeric($node)) {
      $node = Node::load($node);
    }

    return \Drupal::entityTypeManager()->getViewBuilder('node')->view($node, $view_mode);
  }

}
