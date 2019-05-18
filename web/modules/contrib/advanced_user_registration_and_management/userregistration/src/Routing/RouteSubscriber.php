<?php

/**
 * @file
 */
namespace Drupal\userregistration\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Change path '/user/login' to '/login'.
    if ($route = $collection->get('sociallogin.settings_form')) {
      $route->setPath('admin/config/people/userregistration');
     
    $defaults =   $route->getDefaults();
      $defaults['_title'] = "User Registration settings";

      $route->setDefaults($defaults);
    }
     if ($route = $collection->get('advanced.settings_form')) {
      $route->setPath('admin/config/people/userregistration/advanced');
    }
  }

}