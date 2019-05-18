<?php

namespace Drupal\aws_cloud\Plugin\Derivative;

use Drupal\cloud\Plugin\CloudConfigPluginManagerInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides plugin definitions for custom local menu.
 *
 * @see \Drupal\aws_cloud\Plugin\Derivative\AwsCloudLocalTasks
 */
class AwsCloudMenuLinks extends DeriverBase implements ContainerDeriverInterface {

  /**
   * CloudConfigPlugin.
   *
   * @var \Drupal\cloud\Plugin\CloudConfigPluginManagerInterface
   */
  protected $cloudConfigPluginManager;

  /**
   * Constructs new AwsCloudLocalTasks.
   *
   * @param \Drupal\cloud\Plugin\CloudConfigPluginManagerInterface $cloud_config_plugin_manager
   *   The Cloud config plugin manager.
   */
  public function __construct(CloudConfigPluginManagerInterface $cloud_config_plugin_manager) {
    $this->cloudConfigPluginManager = $cloud_config_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('plugin.manager.cloud_config_plugin')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $entities = $this->cloudConfigPluginManager->loadConfigEntities('aws_ec2');

    foreach ($entities as $entity) {
      /* @var \Drupal\cloud\Entity\CloudConfig $entity */
      $id = $entity->id() . '.local_tasks.' . $entity->getCloudContext();
      $this->derivatives[$id] = $base_plugin_definition;
      $this->derivatives[$id]['title'] = $entity->label();
      $this->derivatives[$id]['route_name'] = 'view.aws_instances.page_1';
      $this->derivatives[$id]['base_route'] = 'cloud.service_providers.menu';
      $this->derivatives[$id]['parent'] = 'cloud.service_providers.menu';
      $this->derivatives[$id]['route_parameters'] = ['cloud_context' => $entity->getCloudContext()];
    }

    return $this->derivatives;
  }

}
