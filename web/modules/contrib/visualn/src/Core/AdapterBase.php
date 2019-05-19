<?php

namespace Drupal\visualn\Core;

use Drupal\visualn\Core\AdapterInterface;
use Drupal\visualn\Core\VisualNPluginBase;
use Drupal\visualn\ResourceInterface;

/**
 * Base class for VisualN Adapter plugins.
 *
 * @see \Drupal\visualn\Core\AdapterInterface
 *
 * @ingroup adapter_plugins
 */
abstract class AdapterBase extends VisualNPluginBase implements AdapterInterface {

  /**
   * @inheritdoc
   */
  public function defaultConfiguration() {
    return [
      'drawer_fields' => [],
    ];
  }

}
