<?php

namespace Drupal\entity_ui\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\entity_ui\EntityTabsLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines Entity UI tabs on target entities.
 */
class EntityLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity type manager
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity tabs loader.
   *
   * @var \Drupal\entity_ui\EntityTabsLoader
   */
  protected $tabLoader;

  /**
   * Creates an SelectionBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\entity_ui\EntityTabsLoader $tab_query
   *   The entity query object for entity tab entities.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTabsLoader $tab_loader) {
    $this->entityTypeManager = $entity_type_manager;
    $this->tabLoader = $tab_loader;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_ui.entity_tabs')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $target_entity_types = \Drupal::service('entity_ui.target_entity_types')->getTargetEntityTypes();
    foreach ($target_entity_types as $entity_type_id => $entity_type) {

      foreach ($this->tabLoader->getEntityTabs($entity_type) as $tab_id => $entity_tab) {
        $task = $base_plugin_definition;

        $path_component = $entity_tab->getPathComponent();

        $task['title'] = $entity_tab->getTabTitle();
        $task['weight'] = $entity_tab->get('weight');
        $task['route_name'] = $entity_tab->getRouteName();
        $task['base_route'] = "entity.{$entity_type_id}.canonical";

        // No need to namespace these with a prefix, as the tab plugin ID gets
        // prefixed with the deriver ID.
        $this->derivatives["entity.{$entity_type_id}.{$path_component}"] = $task;
      }
    }

    return $this->derivatives;
  }

}
