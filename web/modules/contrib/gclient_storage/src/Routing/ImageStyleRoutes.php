<?php

namespace Drupal\gclient_storage\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines a route subscriber to register a url for serving image styles.
 */
class ImageStyleRoutes implements ContainerInjectionInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new ImageStyleRoutes object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler')
    );
  }

  /**
   * Returns an array of route objects.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of route objects.
   */
  public function routes() {
    $routes = [];
    // Only add route for image styles if image module is enabled.
    if ($this->moduleHandler->moduleExists('image')) {
      $routes['gclient_storage.image_styles'] = new Route(
        '/gs/files/styles/{image_style}/{scheme}',
        [
          '_controller' => 'Drupal\gclient_storage\Controller\GclientStorageImageStyleDownloadController::deliver',
        ],
        [
          '_access' => 'TRUE',
        ]
      );

      // @see \Drupal\redirect\Routing\RouteSubscriber::alterRoutes()
      if ($this->moduleHandler->moduleExists('redirect')) {
        $routes['gclient_storage.image_styles']->setDefault('_disable_route_normalizer', TRUE);
      }
    }

    return $routes;
  }

}
