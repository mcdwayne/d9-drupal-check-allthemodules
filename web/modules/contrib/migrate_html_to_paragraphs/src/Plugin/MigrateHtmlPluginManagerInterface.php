<?php

namespace Drupal\migrate_html_to_paragraphs\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Migration paragraphs plugin manager interface.
 */
interface MigrateHtmlPluginManagerInterface extends PluginManagerInterface {

  /**
   * Creates a pre-configured instance of a migration paragraphs plugin.
   *
   * A specific createInstance method is necessary to pass the paragraphs
   * migration on.
   *
   * @param string $plugin_id
   *   The ID of the plugin being instantiated.
   * @param array $configuration
   *   An array of configuration relevant to the plugin instance.
   *
   * @return object
   *   A fully configured plugin instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the instance cannot be created, such as if the ID is invalid.
   */
  public function createInstance($plugin_id, array $configuration = []);

}
