<?php

/**
 * @file
 * Contains VisualN Resource data type class. The structure is mainly based
 * on \Drupal\Core\Field\Plugin\DataType\FieldItem class.
 */

namespace Drupal\visualn\Plugin\DataType;

/**
 * Defines the base plugin for deriving data types for resource types.
 *
 * Note that the class only registers the plugin, and is actually never used.
 * \Drupal\visualn\Resource is available for use as base class.
 *
 * @DataType(
 *   id = "visualn_resource",
 *   label = @Translation("Resource"),
 *   deriver = "Drupal\visualn\Plugin\DataType\Deriver\ResourceDeriver"
 * )
 */
abstract class Resource {
  // @todo: check if list_class is needed in annotaion here

  /**
   *   list_class = "\Drupal\Core\Field\FieldItemList",
   *   deriver = "..."
   */
}
