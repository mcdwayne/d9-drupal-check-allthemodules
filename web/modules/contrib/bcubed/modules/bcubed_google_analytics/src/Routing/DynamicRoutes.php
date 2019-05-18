<?php

namespace Drupal\bcubed_google_analytics\Routing;

use Drupal\bcubed\StringGenerator;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DynamicRoutes.
 *
 * @package Drupal\bcubed_google_analytics\Routing
 */
class DynamicRoutes implements ContainerInjectionInterface {

  /**
   * The generated strings.
   *
   * @var generatedStrings
   */
  protected $generatedStrings;

  /**
   * Constructs a new DynamicRoutes object.
   *
   * @param \Drupal\bcubed\StringGenerator $string_generator
   *   String Generator object.
   */
  public function __construct(StringGenerator $string_generator) {
    $this->generatedStrings = $string_generator->getStrings('bcubed_google_analytics');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('bcubed.string_generator')
    );
  }

  /**
   * Returns dynamic routes.
   */
  public function routes() {
    $routes = [];
    $routes['bcubed_google_analytics.proxy'] = new Route(
      '/' . $this->generatedStrings['proxy'],
      [
        '_controller' => '\Drupal\bcubed_google_analytics\Controller\ProxyController::sendEvent',
      ],
      [
        '_permission'  => 'access content',
      ]
    );
    return $routes;
  }

}
