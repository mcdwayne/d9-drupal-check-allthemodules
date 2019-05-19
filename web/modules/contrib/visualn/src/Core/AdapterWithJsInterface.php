<?php

namespace Drupal\visualn\Core;
use Drupal\visualn\Core\AdapterInterface;
use Drupal\visualn\Core\VisualNPluginJsInterface;

/**
 * Interface for VisualN Adapter plugins using js.
 *
 * @see \Drupal\visualn\Core\AdapterWithJsBase
 *
 * @ingroup adapter_plugins
 */
interface AdapterWithJsInterface extends AdapterInterface, VisualNPluginJsInterface {

}
