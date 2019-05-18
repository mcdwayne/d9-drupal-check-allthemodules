<?php

namespace Drupal\living_style_guide\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\living_style_guide\Controller\StyleGuideController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines dynamic routes.
 */
class StyleGuideRoutes {

  /**
   * The style guide controller.
   *
   * @var \Drupal\living_style_guide\Controller\StyleGuideController
   */
  protected $styleGuideController;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The bundles.
   *
   * @var array
   */
  protected $bundles;

  /**
   * StyleGuideRoutes constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ContainerInterface $container) {
    $this->styleGuideController = StyleGuideController::create($container);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $entityTypes = $this->styleGuideController->getEntityTypes();
    $this->bundles = $this->styleGuideController->getAllBundles();

    $routes = [];

    foreach ($entityTypes as $entityType) {
      if (!isset($this->bundles[$entityType])) {
        continue;
      }

      $this->createEntityTypeRoute($routes, $entityType);

      $availableBundles = array_keys($this->bundles[$entityType]);

      $this->createBundleRoutes($routes, $entityType, $availableBundles);
    }

    return $routes;
  }

  /**
   * Creates a route for a living style guide entity page.
   *
   * @param array $routes
   *   The routes array.
   * @param string $type
   *   The entity type.
   */
  private function createEntityTypeRoute(array &$routes, $type) {
    $this->buildRoute($routes, $type);
  }

  /**
   * Creates routes for living style guide bundle pages.
   *
   * @param array $routes
   *   The routes array.
   * @param string $type
   *   The entity type for the bundles.
   * @param array $bundles
   *   Array with strings of bundle machine names.
   */
  private function createBundleRoutes(array &$routes, $type, array $bundles) {
    foreach ($bundles as $bundle) {
      $this->buildRoute($routes, $type, $bundle);
    }
  }

  /**
   * Builds a living style guide route for the given entity type and bundle.
   *
   * @param array $routes
   *   The routes array.
   * @param string $type
   *   The entity type.
   * @param string $bundle
   *   (Optional) The bundle.
   */
  private function buildRoute(array &$routes, $type, $bundle = '') {
    $routeMachineName = 'living_style_guide.guide.' . $type . '.' . $bundle;
    $routeMachineName = rtrim($routeMachineName, '.');

    $entityTypeLabel = $this->entityTypeManager->getDefinition($type)->getLabel();
    $bundleLabel = $this->bundles[$type][$bundle]['label'];

    $routes[$routeMachineName] = new Route(
      '/living-style-guide/' . $type . '/' . $bundle,
      [
        '_controller' => '\Drupal\living_style_guide\Controller\StyleGuideController::getGuide',
        '_title' => 'Living style guide - ' . $entityTypeLabel . ': ' . $bundleLabel,
        'type' => $type,
        'bundle' => $bundle,
      ],
      [
        '_permission' => 'view living style guide',
      ]
    );
  }

}
