<?php

namespace Drupal\colors\Plugin\colors\type;

use Drupal\colors\Plugin\ColorsSchemeInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides colors for the current user.
 *
 * @ColorsScheme(
 *   id = "user_current",
 *   module = "colors",
 *   title = "Current user",
 *   parent = "user",
 *   label = @Translation("Enable a color for the current user"),
 *   description = @Translation("A color for the current user. If enabled, you may set one color for items the current user has authored."),
 *   callback = "\Drupal\colors\Plugin\colors\type\UserCurrentScheme::getUser",
 *   weight = 0,
 * )
 */
class UserCurrentScheme extends PluginBase implements ColorsSchemeInterface, ContainerFactoryPluginInterface {

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


  public function getUser() {
    return ['Current user'];
  }
}
