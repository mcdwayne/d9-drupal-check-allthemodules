<?php

namespace Drupal\visualn\Core;
use Drupal\visualn\Core\MapperInterface;
use Drupal\visualn\Core\VisualNPluginJsInterface;

/**
 * Interface for VisualN Mapper plugins using js.
 *
 * @see \Drupal\visualn\Core\MapperWithJsBase
 *
 * @ingroup mapper_plugins
 */
interface MapperWithJsInterface extends MapperInterface, VisualNPluginJsInterface {

}
