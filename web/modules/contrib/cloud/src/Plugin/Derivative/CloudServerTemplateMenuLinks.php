<?php

namespace Drupal\cloud\Plugin\Derivative;

use Drupal\cloud\Plugin\CloudConfigPluginManagerInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides plugin definitions for custom local menu.
 */
class CloudServerTemplateMenuLinks extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The cloud config plugin manager.
   *
   * @var \Drupal\cloud\Plugin\CloudConfigPluginManagerInterface
   */
  protected $cloudConfigPluginManager;

  /**
   * Constructs new AwsCloudLocalTasks.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\cloud\Plugin\CloudConfigPluginManagerInterface $cloud_config_plugin_manager
   *   The cloud config plugin manager.
   */
  public function __construct(EntityTypeBundleInfoInterface $entity_type_bundle_info, CloudConfigPluginManagerInterface $cloud_config_plugin_manager) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->cloudConfigPluginManager = $cloud_config_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.bundle.info'),
      $container->get('plugin.manager.cloud_config_plugin')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Loop through all cloud_config bundles and build server template links.
    // @TODO: not all implementing clouds will have server_templates
    // @TODO: Add support for identifying which clouds have server_templates
    $bundles = $this->entityTypeBundleInfo->getBundleInfo('cloud_config');
    foreach ($bundles as $key => $bundle) {
      $entities = $this->cloudConfigPluginManager->loadConfigEntities($key);
      foreach ($entities as $entity) {
        /* @var \Drupal\cloud\Entity\CloudConfig $entity */
        $id = 'entity.cloud_server_template.local_tasks.' . $entity->getCloudContext();
        $this->derivatives[$id] = $base_plugin_definition;
        $this->derivatives[$id]['title'] = $entity->label();
        $this->derivatives[$id]['route_name'] = 'entity.cloud_server_template.collection';
        $this->derivatives[$id]['parent'] = 'cloud.design.menu';
        $this->derivatives[$id]['route_parameters'] = ['cloud_context' => $entity->getCloudContext()];
      }
    }
    return $this->derivatives;
  }

}
