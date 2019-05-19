<?php

namespace Drupal\visualn\Core;

use Drupal\Component\Plugin\PluginBase;
use Drupal\visualn\Core\RawResourceFormatInterface;

/**
 * Base class for Raw Resource Format plugins.
 */
abstract class RawResourceFormatBase extends PluginBase implements RawResourceFormatInterface {

  /**
   * {@inheritdoc}
   */
  public function buildResource(array $raw_input) {
    $output_type = $this->getPluginDefinition()['output'];


    // @todo: the code is copied from VisualN::getResourceByOptions() so check comments there

    $resource_plugin_id = $output_type;

    $visualNResourceManager = \Drupal::service('plugin.manager.visualn.resource');
    $plugin_definitions = $visualNResourceManager->getDefinitions();

    if (!isset($plugin_definitions[$resource_plugin_id])) {
      $resource_plugin_id = 'generic';
    }

    $resource_plugin_config = ['raw_input' => $raw_input];
    $resource = $visualNResourceManager->createInstance($resource_plugin_id, $resource_plugin_config);

    $resource->setValue($raw_input);
    $resource->setResourceType($output_type);

    return $resource;
  }

}
