<?php

/**
 * @file
 * Contains Resource class. The class is used when there is no VisualN Resource plugin
 * available for a give resource type. Also is used as base class for VisualN Resource plugins.
 */

namespace Drupal\visualn;

use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;

/**
 *
 * Comment taken from Map.php:
 * By default there is no metadata for contained properties. Extending classes
 * may want to override MapDataDefinition::getPropertyDefinitions() to define
 * it.
 *
 * Currently conains the most essantial methods just to make system work. Should be reviewed and completed.
 * The structure is created and developed following the one used by Fields infrastructure, namely FieldItemBase class.
 *
 */

// @todo: maybe rename to ResourceItemBase (or ResourceBase) and make abstract as FieldItemBase
//    though a generic class maybe needed in case there is no plugin for a resource output_type
abstract class Resource extends Map implements ResourceInterface {

  protected $resource_type;



  /**
   * @todo: remove these methods if not needed
   *    resource_type maybe could be taken form data definition 'type' propoerty
   *    which is actually "visualn_resource:resource_plugin_id", not just resource_plugin id
   */
  public function getResourceType() {
    return $this->resource_type;
  }

  public function setResourceType($resource_type) {
    $this->resource_type = $resource_type;

    return $resource_type;
  }

}
