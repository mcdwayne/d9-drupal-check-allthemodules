<?php

namespace Drupal\purest\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Listens to the dynamic route events.
 */
class PurestRouteSubscriber extends RouteSubscriberBase {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    $this->config = $this->configFactory
      ->get('purest.settings');
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {

    $prefix = $this->config->get('prefix');

    if ($route = $collection->get('rest.purest_content_resource.GET')) {
      $route->setPath(($prefix ? $prefix : '/purest') . '/content');
    }

    if ($route = $collection->get('rest.purest_menu_resource.GET')) {
      $route->setPath(($prefix ? $prefix : '/purest') . '/menu/{menu}');
    }

    if ($route = $collection->get('rest.purest_menus_resource.GET')) {
      $route->setPath(($prefix ? $prefix : '/purest') . '/menus');
    }

    if ($route = $collection->get('rest.purest_user_activate_resource.PATCH')) {
      $route->setPath(($prefix ? $prefix : '/purest') . '/user/activate');
    }

    if ($route = $collection->get('rest.purest_user_profile_resource.GET')) {
      $route->setPath(($prefix ? $prefix : '/purest') . '/user/profile');
    }

    if ($route = $collection->get('rest.purest_user_reset_resource.PATCH')) {
      $route->setPath(($prefix ? $prefix : '/purest') . '/user/reset');
    }

    if ($route = $collection->get('rest.purest_user_reset_request_resource.POST')) {
      $route->setPath(($prefix ? $prefix : '/purest') . '/user/reset');
    }

    if ($route = $collection->get('rest.user_user_register_resource.POST')) {
      $route->setPath(($prefix ? $prefix : '/purest') . '/user/register');
    }
  }

}
