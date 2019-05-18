<?php

namespace Drupal\entity_parser;


class UtilityParser {

  public static function is_field_ready($entity, $field) {
    $bool = FALSE;
    if (is_object($entity) && $entity->hasField($field)) {
      $field_value = $entity->get($field)->getValue();
      if (!empty($field_value)) {
        $bool = TRUE;
      }
    }
    return $bool;
  }

  public static function url_node($node_or_nid) {

    if (method_exists($node_or_nid, '__toString') && $node_or_nid->__toString() && is_numeric($node_or_nid->__toString())) {
      $node_or_nid = $node_or_nid->__toString();
    }
    if (is_numeric($node_or_nid)) {
      $nid = $node_or_nid;
      $options = ['absolute' => TRUE];
      $url_object = \Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $nid], $options)
        ->toString();
    }
    else {
      if (is_object($node_or_nid) && $node_or_nid->id()) {
        $url_path = explode("/", $node_or_nid->toUrl()->getInternalPath());
        $nid = $url_path[sizeof($url_path) - 1];
        $options = ['absolute' => TRUE];
        $url_object = \Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $nid], $options)
          ->toString();
      }
      else {
        $url_object = NULL;
      }
    }
    return $url_object;
  }

}