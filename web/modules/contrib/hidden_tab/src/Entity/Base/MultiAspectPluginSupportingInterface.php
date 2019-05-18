<?php

namespace Drupal\hidden_tab\Entity\Base;

use Drupal\Core\Entity\EntityInterface;

/**
 * Many Hidden Tab entity types have plugins and their corresponding config.
 *
 * This is the common interface for those entity types.
 *
 * As each entity may need multiple type of plugins, to distinguish between
 * them without IDs colliding, they are given aspects (plugin type).
 */
interface MultiAspectPluginSupportingInterface extends EntityInterface {

  /**
   * Del a plugin to the list of plugins managing the entity's plugable aspect.
   *
   * @param string $type
   *   Type of plugin. There might be multiple categories of plugin and it
   *   identifies them.
   * @param string $plugin
   *   The plugin in question.
   */
  public function delPlugin(string $type, string $plugin);

  /**
   * Plugin configuration, json_decoded.
   *
   * TODO pass old data.
   *
   * @param string $type
   *   Type of plugin. There might be multiple categories of plugin and it
   *   identifies them.
   * @param string $plugin_id
   *   Id of the plugin in question.
   *
   * @return mixed|null
   *   Plugin configuration managing the main aspect of entity (json decoded).
   */
  public function pluginConfiguration(string $type, string $plugin_id);

  /**
   * Corresponding setter of pluginConfiguration().
   *
   * @param string $type
   *   Type of plugin. There might be multiple categories of plugin and it
   *   identifies them.
   * @param string $plugin_id
   *   Id of the plugin in question.
   * @param $configuration
   *   The configuration data, will be json_encoded.
   */
  public function setPluginConfiguration(string $type, string $plugin_id, $configuration);

  /**
   * All plugin configurations keyed by their ID.
   *
   * @param string|null $type
   *   Type of plugin. There might be multiple categories of plugin and it
   *   identifies them.
   *
   * @return array
   *   All plugin configurations (each json_decoded).
   */
  public function pluginConfigurations(?string $type): array;

  /**
   * Clear internal storage.
   *
   * @param string|null $type
   *   Type of plugin. There might be multiple categories of plugin and it
   *   identifies them. If null, reset everything.
   *
   */
  public function resetPluginConfigurations(?string $type);

}
