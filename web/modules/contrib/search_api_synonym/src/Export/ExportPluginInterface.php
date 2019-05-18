<?php

namespace Drupal\search_api_synonym\Export;

use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;

/**
 * Provides an interface for search api synonym export plugins.
 *
 * @ingroup plugin_api
 */
interface ExportPluginInterface extends PluginFormInterface, ConfigurablePluginInterface {

  /**
   * Get synonyms in the export format.
   **
   * @param array $synonyms
   *   An array containing synonym objects.
   *
   * @return string
   *   The formatted synonyms as a string ready to be saved to an export file.
   */
  public function getFormattedSynonyms(array $synonyms);

}
