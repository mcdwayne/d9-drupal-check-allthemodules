<?php
/**
 * @file
 * Contains \Drupal\entity_conditional_fields\Routing\Routes.
 */

namespace Drupal\entity_conditional_fields\Routing;

use Drupal\Core\Routing\RouteProvider;
use Symfony\Component\Routing\Route;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Url;


/**
 * Defines dynamic routes.
 */
class Routes {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * @var \Drupal\Core\Routing\RouteProvider
   */
  protected $routeProvider;

  /**
   * Routes constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityTypeBundleInfoInterface $entityTypeBundleInfo, RouteProvider $routeProvider) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->routeProvider = $routeProvider;
  }

  /**
   * @return array
   */
  public function routes() {
    $routes = [];
    foreach ($this->entityTypeManager->getDefinitions() as $key => $entityType) {
      $bundle_entity_type = $entityType->get('bundle_entity_type');
      $entity_type = $key;
      $route_name = ($entityType->get('field_ui_base_route') !== NULL) ? $entityType->get('field_ui_base_route') : NULL;
      if ($route_name && $bundle_entity_type !== 'node_type') {
        foreach ($this->entityTypeBundleInfo->getBundleInfo($key) as $k => $bundle) {
          $path = $this->routeProvider->getRouteByName($route_name)->getPath() . "/conditionals";

          $routes["entity_conditional_fields.$entity_type"] = new Route(
            $path,
            [
              '_controller' => 'Drupal\entity_conditional_fields\Controller\EntityConditionalFieldController::provideArgumentsByType',
              '_title' => 'Manage Dependencies',
              'entity_type' => $entityType->id(),
              'bundle' => $k
            ],
            [
              '_permission'  => 'view conditional fields',
            ]
          );
        }
      }
    }

    return $routes;
  }
}
