<?php

namespace Drupal\visualn\Core;

use Drupal\visualn\Core\AdapterWithJsInterface;
use Drupal\visualn\Core\MapperBase;
use Drupal\visualn\ResourceInterface;
use Drupal\visualn\ChainPluginJsTrait;

/**
 * Base class for VisualN Mapper plugins using js.
 *
 * @see \Drupal\visualn\Core\MapperWithJsInterface
 *
 * @ingroup mapper_plugins
 */
abstract class MapperWithJsBase extends MapperBase implements AdapterWithJsInterface {

  use ChainPluginJsTrait;

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    $mapper_js_id = $this->jsId();  // defaults to plugin id if not overriden in drawer plugin class.
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['mapper']['mapperId'] = $mapper_js_id;
    // @todo: this setting is just for reference
    $build['#attached']['drupalSettings']['visualn']['handlerItems']['mappers'][$mapper_js_id][$vuid] = $vuid;

    // @todo: this should return the resource of required type (as in annotation output_type)

    return $resource;
  }

}
