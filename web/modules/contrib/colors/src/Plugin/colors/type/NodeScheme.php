<?php

namespace Drupal\colors\Plugin\colors\type;

use Drupal\colors\Plugin\ColorsSchemeInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Provides colors for node types.
 *
 * @ColorsScheme(
 *   id = "node",
 *   module = "colors",
 *   title = "Node type",
 *   label = @Translation("Enable colors for node types"),
 *   description = @Translation("Colors for node types. If enabled, you may set colors for each node type below."),
 *   callback = "node_type_get_names",
 * )
 */
class NodeScheme extends PluginBase implements ColorsSchemeInterface, ContainerFactoryPluginInterface {

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

}
