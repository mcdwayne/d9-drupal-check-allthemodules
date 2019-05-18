<?php

/**
 * @file
 * Contains \Drupal\content_tab\Plugin\Derivative\ContentTabLocalTasks.
 */

namespace Drupal\content_tab\Plugin\Derivative;

use Drupal\content_tab\ContentTabMapperManagerInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic local tasks for config translation.
 */
class ContentTabLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The mapper plugin discovery service.
   *
   * @var \Drupal\content_tab\ContentTabMapperManagerInterface
   */
  protected $mapperManager;

  /**
   * The base plugin ID.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * Constructs a new ContentTabLocalTasks.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\content_tab\ContentTabMapperManagerInterface $mapper_manager
   *   The mapper plugin discovery service.
   */
  public function __construct($base_plugin_id, ContentTabMapperManagerInterface $mapper_manager) {
    $this->basePluginId = $base_plugin_id;
    $this->mapperManager = $mapper_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('plugin.manager.content_tab.mapper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $mappers = $this->mapperManager->getMappers();
    foreach ($mappers as $plugin_id => $mapper) {
      /** @var \Drupal\content_tab\ContentTabMapperInterface $mapper */
      $route_name = $mapper->getOverviewRouteName();
      $base_route = $mapper->getBaseRouteName();
      if (!empty($base_route)) {
        $this->derivatives[$route_name] = $base_plugin_definition;
        $this->derivatives[$route_name]['content_tab_plugin_id'] = $plugin_id;
        $this->derivatives[$route_name]['class'] = '\Drupal\content_tab\Plugin\Menu\LocalTask\ContentTabLocalTask';
        $this->derivatives[$route_name]['route_name'] = 'content_tab.content_type';
        $this->derivatives[$route_name]['parent_id'] = 'content_tab.content_tab';
      }
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }
}
