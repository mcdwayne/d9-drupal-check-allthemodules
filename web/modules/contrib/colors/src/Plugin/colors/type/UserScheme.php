<?php

namespace Drupal\colors\Plugin\colors\type;

use Drupal\colors\Plugin\ColorsSchemeInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides colors for individual users.
 *
 * @ColorsScheme(
 *   id = "user",
 *   module = "colors",
 *   title = "Users",
 *   parent = "user",
 *   label = @Translation("Enable colors for each user"),
 *   description = @Translation("Colors for users. If enabled, you may set colors for each user below."),
 *   callback = "\Drupal\colors\Plugin\colors\type\UserScheme::getUsers",
 *   weight = 2,
 * )
 */
class UserScheme extends PluginBase implements ColorsSchemeInterface, ContainerFactoryPluginInterface {

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

  public function getUsers() {
    $users = \Drupal::entityManager()->getStorage('user')->loadMultiple();
    $result = array();
    foreach ($users as $user) {
      if ($user->id()) {
        $result[$user->id()] =  $user->getUsername();
      }
    }

    return $result;
  }

}
