<?php

namespace Drupal\onlyone\Routing;

use Symfony\Component\Routing\Route;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Defines dynamic routes.
 */
class OnlyOneRoutes implements ContainerInjectionInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a NodeController object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $routes = [];
    // Checking if we need to show the route.
    if ($this->configFactory->get('onlyone.settings')->get('onlyone_new_menu_entry')) {
      // Defining the route.
      $routes['onlyone.add_page'] = new Route(
        // Path to attach this route to.
        '/onlyone/add',
        // Route defaults.
        [
          '_controller' => '\Drupal\onlyone\Controller\OnlyOneController::addPage',
          '_title' => 'Add content (Only One)',
        ],
        // Route requirements.
        [
          '_node_add_access' => 'node',
        ],
        // Route options.
        [
          '_node_operation_route' => TRUE,
        ]
      );

    }

    return $routes;
  }

}
