<?php

namespace Drupal\dream_fields;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A class to define permissions for dream fields.
 */
class DreamFieldsPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The dream fields plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * Create a permission for each dream field plugin.
   */
  public function pluginPermissions() {
    $permissions = [];
    foreach ($this->pluginManager->getDefinitions() as $definition) {
      $permissions[static::permissionName($definition['id'])] = [
        'title' => $this->t('Use "%plugin_label" dream field', ['%plugin_label' => $definition['label']]),
      ];
    }
    return $permissions;
  }

  /**
   * Get the permission name assocaited with a plugin ID.
   *
   * @param string $id
   *   The plugin ID.
   *
   * @return string
   *   The permission name.
   */
  public static function permissionName($id) {
    return 'use ' . $id . ' dream field';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.dream_fields'));
  }

  /**
   * Creates an instance of the dream fields permissions provider.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   The plugin manager.
   */
  public function __construct(PluginManagerInterface $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

}
