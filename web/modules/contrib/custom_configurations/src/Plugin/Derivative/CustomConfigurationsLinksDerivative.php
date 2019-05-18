<?php

namespace Drupal\custom_configurations\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\custom_configurations\CustomConfigurationsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derivative class that provides the menu links for the Products.
 */
class CustomConfigurationsLinksDerivative extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\custom_configurations\CustomConfigurationsManager definition.
   *
   * @var \Drupal\custom_configurations\CustomConfigurationsManager
   */
  protected $customConfigurationsManager;

  /**
   * Creates a ProductMenuLink instance.
   *
   * @param string $base_plugin_id
   *   The plugin id.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\custom_configurations\CustomConfigurationsManager $custom_configurations_manager
   *   Custom configurations service.
   */
  public function __construct($base_plugin_id, EntityTypeManagerInterface $entity_type_manager, CustomConfigurationsManager $custom_configurations_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->customConfigurationsManager = $custom_configurations_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager'),
      $container->get('custom_configurations.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $links = [];

    if ($base_plugin_definition['id'] == 'custom_configurations.category') {
      $categories = $this->customConfigurationsManager->getConfigPluginCategories();
      $i = -100;
      foreach ($categories as $category_id => $category_name) {
        $links[$category_id] = [
          'title' => $category_name,
          'route_name' => 'custom_configurations.' . $category_id . '.category',
          'parent' => 'custom_configurations.main',
          'weight' => $i++,
        ] + $base_plugin_definition;
      }
    }

    if ($base_plugin_definition['id'] == 'custom_configurations.page') {
      $plugins = $this->customConfigurationsManager->getConfigPlugins();

      foreach ($plugins as $plugin) {

        if (!empty($plugin['category_id'])) {
          $parent = 'custom_configurations.category:' . $plugin['category_id'];
        }
        else {
          $parent = 'custom_configurations.main';
        }

        $links[$plugin['id']] = [
          'title' => $plugin['title'],
          'route_name' => 'custom_configurations.' . $plugin['id'] . '.form',
          'weight' => $plugin['weight'],
          'parent' => $parent,
          'route_parameters' => [
            'plugin_id' => $plugin['id'],
          ],
        ] + $base_plugin_definition;
      }
    }
    return $links;
  }

}
