<?php

namespace Drupal\bibcite\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Defines an interface for bibcite_format managers.
 */
interface BibciteFormatManagerInterface extends PluginManagerInterface {

  /**
   * Get definitions of export formats.
   *
   * @return array|null
   *   List of plugin definitions.
   */
  public function getExportDefinitions();

  /**
   * Get definitions of import formats.
   *
   * @return array|null
   *   List of plugin definitions.
   */
  public function getImportDefinitions();

}
