<?php

/**
 * @file
 * Contains \Drupal\log\EventSubscriber\LogAdminRouteSubscriber.
 */

namespace Drupal\log\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Sets the _admin_route for specific log-related routes.
 */
class LogAdminRouteSubscriber extends RouteSubscriberBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new LogAdminRouteSubscriber.
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
  protected function alterRoutes(RouteCollection $collection) {
    if ($this->configFactory->get('log.settings')->get('log_use_admin_theme')) {
      foreach ($collection->all() as $route) {
        if ($route->hasOption('_log_operation_route')) {
          $route->setOption('_admin_route', TRUE);
        }
      }
    }
  }

}
