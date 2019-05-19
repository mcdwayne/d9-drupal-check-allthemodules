<?php

namespace Drupal\social_share;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;

/**
 * Interface for the social share link manager.
 */
interface SocialShareLinkManagerInterface extends CategorizingPluginManagerInterface {

  /**
   * Creates a pre-configured instance of a social share link plugin.
   *
   * @param string $plugin_id
   *   The ID of the plugin being instantiated; i.e., the filter machine name.
   * @param array $configuration
   *   An array of configuration relevant to the plugin instance. As this plugin
   *   is not configurable, this is unused and should stay empty.
   *
   * @return \Drupal\social_share\SocialShareLinkInterface
   *   A fully configured plugin instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the instance cannot be created, such as if the ID is invalid.
   */
  public function createInstance($plugin_id, array $configuration = []);

  /**
   * Merges the context definitions of all given plugins.
   *
   * This allows configuring multiple plugins at once. Each plugin will receive
   * the same context value for context that is named the same way.
   *
   * @return array[]
   *   A numerically indexed array containing two arrays:
   *   - The array of merged context definitions, keyed by context name.
   *   - An array mapping the context definitions names to an array of plugin
   *     ids that are using this context.
   */
  public function getMergedContextDefinitions(array $plugin_ids);

}
