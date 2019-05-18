<?php

namespace Drupal\blank_node_title\Hook;

/**
 * @file
 * Contains \Drupal\blank_node_title\Hook\EntityPresave.
 */

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller NodePresave.
 */
class NodePresave extends ControllerBase {

  /**
   * Hook.
   */
  public static function hook($node) {
    // Set Node-title.
    if (!$node->title->value || $node->title->value == '-') {
      $title = $node->getType() . " - " . format_date(REQUEST_TIME, 'long');
      $node->title->setValue($title);
    }
    $types = [
      'project',
    ];
    if ($type = self::checkType($node, $types)) {

    }
  }

  /**
   * Check node type.
   */
  public static function checkType($node, $types) {
    $result = FALSE;
    if (method_exists($node, 'getType')) {
      $type = $node->getType();
      if (in_array($type, $types)) {
        $result = $type;
      }
    }
    return $type;
  }

}
