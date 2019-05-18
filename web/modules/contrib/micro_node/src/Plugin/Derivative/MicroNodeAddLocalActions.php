<?php

namespace Drupal\micro_node\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\micro_site\SiteNegotiatorInterface;

/**
 * Derivative class that provides the menu local Actions on the content site tab.
 */
class MicroNodeAddLocalActions extends DeriverBase implements ContainerDeriverInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager.
   *
   * The entity type manager service.
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The site negotiator.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * Creates a MicroPageAddLocalTasks instance.
   *
   * @param $base_plugin_id
   *   The base plugin id.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\micro_site\SiteNegotiatorInterface $site_negotiator
   *   The site negotiator.
   */
  public function __construct($base_plugin_id, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, SiteNegotiatorInterface $site_negotiator) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->negotiator = $site_negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('micro_site.negotiator')
    );
  }

  /**
   * {@inheritdoc}
   */
//  public function getDerivativeDefinitions($base_plugin_definition) {
//    $links = [];
//
//    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
//    foreach ($node_types as $id => $node_type) {
//      $links[$id] = [
//          'title' => $node_type->label(),
//          'route_name' => 'micro_node.node.add.' . $node_type,
//          'base_route' => 'entity.site.canonical',
//        ] + $base_plugin_definition;
//    }
//
//    return $links;
//  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    $node_types_enabled = $this->configFactory->get('micro_node.settings')->get('node_types');

    if (empty($node_types_enabled)) {
      return $this->derivatives;
    }

    foreach ($node_types as $id => $node_type) {
     if (in_array($id, $node_types_enabled)) {
       $this->derivatives[$id] = $base_plugin_definition;
       $this->derivatives[$id]['title'] = 'Add ' . $node_type->label();
       $this->derivatives[$id]['route_name'] = 'micro_node.node.add.' . $id;
     }
    }

    return $this->derivatives;
  }
}