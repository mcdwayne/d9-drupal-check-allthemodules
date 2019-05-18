<?php

namespace Drupal\entity_ui\Routing;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Drupal\entity_ui\EntityTabsLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides routes that the EntityTab entities define for content entities.
 */
class TabRouteProvider implements EntityRouteProviderInterface, EntityHandlerInterface {

  /**
   * The entity tab loader.
   *
   * @var \Drupal\entity_ui\EntityTabsLoader
   */
  protected $entityTabLoader;

  /**
   * Constructs a new TabRouteProvider.
   *
   * @param \Drupal\entity_ui\EntityTabsLoader $entity_tab_loader
   *   The entity tab loader.
   */
  public function __construct(EntityTabsLoader $entity_tab_loader) {
    $this->entityTabLoader = $entity_tab_loader;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_ui.entity_tabs')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = new RouteCollection();

    foreach ($this->entityTabLoader->getEntityTabs($entity_type) as $tab_id => $entity_tab) {
      // Note that we can't use link templates, because to define them on the
      // target entity type we'd first need to load (and therefore discover)
      // entity tab entities, and this would be circular.
      $path_component = $entity_tab->getPathComponent();
      $path = $entity_type->getLinkTemplate('canonical') . '/' . $path_component;
      $entity_type_id = $entity_type->id();

      $route = new Route($path);
      $route
        ->setDefaults([
          '_controller' => '\Drupal\entity_ui\Controller\EntityTabController::content',
          '_title_callback' => '\Drupal\entity_ui\Controller\EntityTabController::title',
          '_entity_tab_id' => $tab_id,
        ])
        ->setRequirements([
           '_custom_access' => '\Drupal\entity_ui\Controller\EntityTabController::access',
        ])
        ->setOption('parameters', [
          $entity_type_id => ['type' => 'entity:' . $entity_type_id],
        ]);

      $collection->add($entity_tab->getRouteName(), $route);
    }

    return $collection;
  }

}
