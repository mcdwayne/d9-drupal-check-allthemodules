<?php

namespace Drupal\yaml_content;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * A plugin manager service for ContentProcessor plugin implementations.
 */
interface ContentProcessorPluginManagerInterface extends PluginManagerInterface {

  /**
   * Retrieve a list of content processor plugins supporting import operations.
   *
   * @return array
   *   An array of plugin definitions keyed by plugin id where the annotation
   *   indicates `import` as TRUE.
   */
  public function getImportPlugins();

  /**
   * Retrieve a list of content processor plugins supporting export operations.
   *
   * @return array
   *   An array of plugin definitions keyed by plugin id where the annotation
   *   indicates `export` as TRUE.
   */
  public function getExportPlugins();

}
