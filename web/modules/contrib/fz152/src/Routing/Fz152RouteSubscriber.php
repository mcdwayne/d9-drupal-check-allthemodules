<?php

namespace Drupal\fz152\Routing;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic trousers route events.
 */
class Fz152RouteSubscriber extends RouteSubscriberBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a Fz152RouteSubscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config) {
    $this->configFactory = $config;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Try to get the route from the current collection.
    $name = 'fz152.privacy_policy_page';
    if ($route = $collection->get($name)) {
      $config = $this->configFactory->get($name);

      if ($config->get('enable')) {
        $route->setPath($config->get('path'));
      }
      else {
        $collection->remove($name);
      }
    }
  }

}
