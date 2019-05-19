<?php

namespace Drupal\simple_amp;

use Drupal\Component\Plugin\PluginBase;

class AmpMetadataBase extends PluginBase implements AmpMetadataInterface {

  /**
   * Get entity types.
   */
  public function getEntityTypes($entity) {
    return $this->pluginDefinition['entity_types'];
  }

  /**
   * Return entity AMP metadata.
   */
  public function getMetadata($entity) {
    // Metadata
  }

}
