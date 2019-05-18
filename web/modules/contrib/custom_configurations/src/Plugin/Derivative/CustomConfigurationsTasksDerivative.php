<?php

namespace Drupal\custom_configurations\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\custom_configurations\CustomConfigurationsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derivative class that provides the menu links for the Products.
 */
class CustomConfigurationsTasksDerivative extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

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
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\custom_configurations\CustomConfigurationsManager $custom_configurations_manager
   *   Custom configurations service.
   */
  public function __construct($base_plugin_id, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, CustomConfigurationsManager $custom_configurations_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->customConfigurationsManager = $custom_configurations_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('custom_configurations.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $plugins = $this->customConfigurationsManager->getConfigPlugins();

    foreach ($plugins as $plugin) {

      $task_id = $plugin['id'];
      $this->derivatives[$task_id] = $base_plugin_definition;
      $this->derivatives[$task_id]['title'] = 'Global';
      $this->derivatives[$task_id]['base_route'] = 'custom_configurations.' . $plugin['id'] . '.form';
      $this->derivatives[$task_id]['route_name'] = 'custom_configurations.' . $plugin['id'] . '.form';
      $this->derivatives[$task_id]['route_parameters']['plugin_id'] = $plugin['id'];
      $this->derivatives[$task_id]['route_parameters']['language'] = FALSE;

      if ($this->customConfigurationsManager->languagesAvailable()) {
        $languages = $this->languageManager->getLanguages();
        foreach ($languages as $language_code => $lang) {
          $task_id = $plugin['id'] . '.' . $language_code;
          $this->derivatives[$task_id] = $base_plugin_definition;
          $this->derivatives[$task_id]['title'] = $lang->getName();
          $this->derivatives[$task_id]['base_route'] = 'custom_configurations.' . $plugin['id'] . '.form';
          $this->derivatives[$task_id]['route_name'] = 'custom_configurations.' . $task_id . '.form';
          $this->derivatives[$task_id]['route_parameters']['plugin_id'] = $plugin['id'];
          $this->derivatives[$task_id]['route_parameters']['language'] = $language_code;
        }
      }
    }

    return $this->derivatives;
  }

}
