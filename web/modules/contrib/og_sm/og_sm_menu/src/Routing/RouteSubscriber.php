<?php

namespace Drupal\og_sm_menu\Routing;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new RouteSubscriber object.
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
  protected function alterRoutes(RouteCollection $collection) {
    if (!$this->moduleHandler->moduleExists('menu_admin_per_menu')) {
      return;
    }

    $routes = $collection->all();
    foreach ($routes as $route_name => $route) {
      if ($route_name === 'og_sm.site_menu.edit_link') {
        $route->setRequirements(['_custom_access' => '\Drupal\menu_admin_per_menu\Access\MenuAdminPerMenuAccess::menuLinkAccess']);
      }
    }
  }

}
