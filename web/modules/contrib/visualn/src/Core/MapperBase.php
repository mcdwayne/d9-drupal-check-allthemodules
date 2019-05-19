<?php

namespace Drupal\visualn\Core;

use Drupal\visualn\Core\MapperInterface;
use Drupal\visualn\Core\VisualNPluginBase;
use Drupal\visualn\ResourceInterface;

/**
 * Base class for VisualN Mapper plugins.
 *
 * @see \Drupal\visualn\Core\MapperInterface
 *
 * @ingroup mapper_plugins
 */
abstract class MapperBase extends VisualNPluginBase implements MapperInterface {

  /**
   * @inheritdoc
   */
  public function defaultConfiguration() {
    return [
      'data_keys_structure' => [],
      'drawer_fields' => [],
    ];
  }

}
