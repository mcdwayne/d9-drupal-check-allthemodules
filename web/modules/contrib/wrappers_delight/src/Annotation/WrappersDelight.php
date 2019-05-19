<?php

namespace Drupal\wrappers_delight\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Wrappers Delight annotation object.
 *
 * Plugin Namespace: Wrapper
 *
 * @see plugin_api
 *
 * @Annotation
 */
class WrappersDelight extends Plugin {

  const TYPE_ENTITY = 'entity';
  const TYPE_BUNDLE = 'bundle';
  const TYPE_QUERY_ENTITY = 'query_entity';
  const TYPE_QUERY_BUNDLE = 'query_bundle';
  const TYPE_FIELD_TYPE = 'field_type';
  const TYPE_FIELD_LIST = 'field_list';

  /**
   * Plugin ID
   * 
   * @var string
   */
  public $id;
  
  /**
   * Type of wrapper class
   * 
   * @var string
   */
  public $type;

  /**
   * The entity type.
   *
   * @var string
   */
  public $entity_type;

  /**
   * Bundle
   * 
   * @var string
   */
  public $bundle;

  /**
   * Field type
   * 
   * @var string
   */
  public $field_type;

  /**
   * @return array
   */
  public function getDefinition() {
    return $this->definition;
  }

}
