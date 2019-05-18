<?php

namespace Drupal\ofed_switcher\Routing;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Class OfedSwitcherRoutes.
 */
class OfedSwitcherRoutes implements ContainerInjectionInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * NegotiationLanguageSelectionPageForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
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
  public function main() {
    $routes = [];
    $config = $this->configFactory->get('ofed_switcher.configuration');

    $routes['ofed_switcher.switch_to_frontend'] = new Route(
      $config->get('path.frontend'),
      [
        '_controller' => '\Drupal\ofed_switcher\Controller\OfedSwitcherSwitchController::go_to_frontend',
        '_title' => $config->get('title'),
      ],
      [
        '_permission' => 'access content',
      ]
    );

    $routes['ofed_switcher.switch_to_backend'] = new Route(
      $config->get('path.backend'),
      [
        '_controller' => '\Drupal\ofed_switcher\Controller\OfedSwitcherSwitchController::go_to_backend',
        '_title' => $config->get('title'),
      ],
      [
        '_permission' => 'access content',
      ]
    );

    return $routes;
  }

}
