<?php

namespace Drupal\colors\Plugin\colors\type;

use Drupal\colors\Plugin\ColorsSchemeInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides colors for user roles.
 *
 * @ColorsScheme(
 *   id = "user_role",
 *   module = "colors",
 *   title = "User role",
 *   parent = "user",
 *   default = TRUE,
 *   label = @Translation("Enable colors for user role"),
 *   description = @Translation("Colors for user roles. If enabled, you may set colors for each user role below."),
 *   callback = "\Drupal\colors\Plugin\colors\type\UserRoleScheme::getRoles",
 *   weight = 1,
 * )
 */
class UserRoleScheme extends PluginBase implements ColorsSchemeInterface, ContainerFactoryPluginInterface {

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

  public function getRoles() {
    $roles = [];
    foreach (user_roles() as $role) {
      $roles[$role->id()] = $role->label();
    }
    return $roles;
  }

}
