<?php

namespace Drupal\og_sm_admin_menu\Routing;

use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber for OG related routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   */
  public function __construct(RouteProviderInterface $route_provider) {
    $this->routeProvider = $route_provider;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $route = new Route('/group/node/{node}/admin');
    $route->addDefaults([
      '_controller' => '\Drupal\system\Controller\SystemController::overview',
      '_title_callback' => '\Drupal\og_sm_admin_menu\Controller\SiteAdminController::siteAdminMenuTitle',
      '_title_arguments' => [
        'title' => 'Administer @site_title',
      ],
      'link_id' => 'og_sm:og_sm.site.admin',
    ]);
    $route->addRequirements([
      '_site_permission' => 'administer site',
    ]);
    $route->addOptions([
      '_admin_route' => TRUE,
      'parameters' => [
        'node' => [
          'type' => 'og_sm:site',
        ],
      ],
    ]);

    $collection->add('entity.node.og_admin_routes', $route);

    $collection
      ->get('toolbar.subtrees')
      ->setRequirement('_custom_access', '\Drupal\og_sm_admin_menu\Controller\ToolbarController::checkSubTreeAccess');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER] = ['onAlterRoutes'];
    return $events;
  }

}
