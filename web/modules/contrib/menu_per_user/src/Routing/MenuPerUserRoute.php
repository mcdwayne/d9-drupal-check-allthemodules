<?php

namespace Drupal\menu_per_user\Routing;


use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;


class MenuPerUserRoute extends RouteSubscriberBase  {


  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {

    foreach ($collection->all() as $routename => $route) {
      $path = $route->getPath();
      $check_path = menu_per_user_check_path($path);
      if(!$check_path){
        $check_path = menu_per_user_check_path(substr($path, 1)); 
      }
      if($check_path){
        $route->setRequirement(
          '_custom_access',
          '\Drupal\menu_per_user\AccessChecks\MenuAccessChecks::access'
        );
      }
    }
  }
}