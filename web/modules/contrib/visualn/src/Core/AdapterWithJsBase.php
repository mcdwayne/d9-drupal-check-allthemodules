<?php

namespace Drupal\visualn\Core;

use Drupal\visualn\Core\AdapterBase;
use Drupal\visualn\Core\AdapterWithJsInterface;
use Drupal\visualn\ResourceInterface;
use Drupal\visualn\ChainPluginJsTrait;

/**
 * Base class for VisualN Adapter plugins using js.
 *
 * @see \Drupal\visualn\Core\AdapterWithJsInterface
 *
 * @ingroup adapter_plugins
 */
abstract class AdapterWithJsBase extends AdapterBase implements AdapterWithJsInterface {

  use ChainPluginJsTrait;

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    $adapter_js_id = $this->jsId();  // defaults to plugin id if not overriden in drawer plugin class.
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['adapter']['adapterId'] = $adapter_js_id;
    // @todo: this setting is just for reference
    $build['#attached']['drupalSettings']['visualn']['handlerItems']['adapters'][$adapter_js_id][$vuid] = $vuid;

    // @todo: this should return the resource of required type (as in annotation output_type)

    return $resource;
  }

}
