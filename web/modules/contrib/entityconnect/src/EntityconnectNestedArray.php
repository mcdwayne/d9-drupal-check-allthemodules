<?php

namespace Drupal\entityconnect;

use Drupal\Component\Utility\NestedArray;

/**
 * Reimplements NestedArray methods to answer specific needs of the module.
 *
 * Entityconnect button adds a new level in parents array,
 * named "add_entityconnect" and "edit_entityconnect" and we need to remove
 * them in our array building. So we need to rewrite
 * NestedArray::getValue()/setValue() to include these removals.
 *
 * @see Drupal\Component\Utility\NestedArray::getValue()
 * @see Drupal\Component\Utility\NestedArray::setValue()
 */
class EntityconnectNestedArray extends NestedArray {

  /**
   * {@inheritdoc}
   */
  public static function &getValue(array &$array, array $parents, &$key_exists = NULL) {
    $ref = &$array;
    foreach ($parents as $parent) {
      if (stripos($parent, 'add_entityconnect') === FALSE
        && stripos($parent, 'edit_entityconnect') === FALSE) {
        if (is_array($ref) && array_key_exists($parent, $ref)) {
          $ref = &$ref[$parent];
        }
        else {
          $key_exists = FALSE;
          $null = NULL;
          return $null;
        }
      }
    }
    $key_exists = TRUE;
    return $ref;
  }

  /**
   * {@inheritdoc}
   */
  public static function setValue(array &$array, array $parents, $value, $force = FALSE) {
    $ref = &$array;
    foreach ($parents as $parent) {
      if (stripos($parent, 'add_entityconnect') === FALSE
        && stripos($parent, 'edit_entityconnect') === FALSE) {
        // PHP auto-creates container arrays and NULL entries without error if
        // $ref is NULL, but throws an error if $ref is set, but not an array.
        if ($force && isset($ref) && !is_array($ref)) {
          $ref = array();
        }
        $ref = &$ref[$parent];
      }
    }
    $ref = $value;
  }

}
