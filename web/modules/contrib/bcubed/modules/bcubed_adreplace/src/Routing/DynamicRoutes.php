<?php

namespace Drupal\bcubed_adreplace\Routing;

use Drupal\bcubed\StringGenerator;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DynamicRoutes.
 *
 * @package Drupal\bcubed_adreplace\Routing
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
    $this->generatedStrings = $string_generator->getStrings('bcubed_adreplace');
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
   * Return dynamic routes.
   */
  public function routes() {
    $routes = [];
    $routes['bcubed_adreplace.proxy'] = new Route(
      '/' . $this->generatedStrings['main_proxy'] . '/{locator}/{resource}',
      [
        '_controller' => '\Drupal\bcubed_adreplace\Controller\ProxyController::request',
        'locator' => '',
        'resource' => '',
      ],
      [
        '_permission'  => 'access content',
      ]
    );

    $routes['bcubed_adreplace.element'] = new Route(
      '/' . $this->generatedStrings['element_proxy'],
      [
        '_controller' => '\Drupal\bcubed_adreplace\Controller\ProxyController::replacementElement',
      ],
      [
        '_permission'  => 'access content',
      ]
    );

    $routes['bcubed_adreplace.click'] = new Route(
      '/' . $this->generatedStrings['click_tracking_proxy'] . '/{banner}',
      [
        '_controller' => '\Drupal\bcubed_adreplace\Controller\ProxyController::trackClick',
        'banner' => '',
      ],
      [
        '_permission'  => 'access content',
      ]
    );
    return $routes;
  }

}
