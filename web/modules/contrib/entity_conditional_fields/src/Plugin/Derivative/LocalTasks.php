<?php

namespace Drupal\entity_conditional_fields\Plugin\Derivative;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic local tasks for config translation.
 */
class LocalTasks extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The mapper plugin discovery service.
   *
   * @var \Drupal\config_translation\ConfigMapperManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The base plugin ID.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * Constructs a new ConfigTranslationLocalTasks.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\config_translation\ConfigMapperManagerInterface $mapper_manager
   *   The mapper plugin discovery service.
   */
  public function __construct($base_plugin_id, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->entityTypeManager->getDefinitions() as $key => $entityType) {
      $entity_type = $key;
      // Already supported by conditional filed module
      if ($entity_type == 'node') {
        continue;
      }
      $route_base = ($entityType->get('field_ui_base_route') !== NULL) ? $entityType->get('field_ui_base_route') : NULL;
      $route_name = "entity_conditional_fields.$entity_type";
      $this->derivatives[$route_name] = $base_plugin_definition;
      $this->derivatives[$route_name]['route_name'] = $route_name;
      $this->derivatives[$route_name]['base_route'] = $route_base;
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
