<?php
namespace Drupal\menu_custom_access\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Drupal\menu_custom_access\AccessChecks\RouteAccessChecks;


class AdminRouteSubscriber extends RouteSubscriberBase  {


  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {


  /**
   * {@inheritdoc}
   */
  // Change path '/node/edit' to custom controller action.
    foreach ($collection->all() as $routename => $route) {
      // if($routename == 'entity.menu.add_form') {
          // dpm($route);
        $route->setRequirement(
          '_custom_access',
          '\Drupal\menu_custom_access\AccessChecks\RouteAccessChecks::access'
        );
      }
    // }
  }
}