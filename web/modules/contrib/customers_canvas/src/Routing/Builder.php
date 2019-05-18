<?php

namespace Drupal\customers_canvas\Routing;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines dynamic route for the builder.
 */
class Builder implements ContainerInjectionInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new ImageStyleRoutes object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The stream wrapper manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $container->get('config.factory');
    return new static(
      $config_factory
    );
  }

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $routes = [];
    // Define the route with path, defaults, requirements and options.
    $builder_url = $this->configFactory->get('customers_canvas.settings')->get('builder_url');
    $path = "/$builder_url/{user}/{cc_entity}/{state_id}";
    $title = $this->configFactory->get('customers_canvas.settings')->get('builder_title');
    $defaults = [
      '_controller' => '\Drupal\customers_canvas\Controller\Builder::content',
      '_title' => $title,
      'state_id' => '',
    ];
    $requirements = [
      // Permission to load the builder.
      '_permission' => 'access own customers canvas builder+access all customers canvas builder',
    ];
    $options = [
      'parameters' => [
        'user' => ['type' => 'entity:user'],
        'cc_entity' => ['type' => 'entity:node'],
      ],
    ];
    $routes['customers_canvas.builder'] = new Route($path, $defaults, $requirements, $options);
    return $routes;
  }

}
