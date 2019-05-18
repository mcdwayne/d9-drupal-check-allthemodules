<?php

namespace Drupal\plus\Traits;

/**
 * Trait SerializationTrait.
 */
trait PluginSerializationTrait {

  use SerializationTrait;

  /**
   * {@inheritdoc}
   */
  public function serializeProperties() {
    return [
      'configuration',
      'pluginDefinition',
      'pluginId',
    ];
  }

}
