<?php


namespace Drupal\oauth2_jwt_sso\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class RouteSubscriber extends RouteSubscriberBase{

  protected function alterRoutes(RouteCollection $collection) {
    if ($route =  $collection->get('user.logout')) {
      $route->setDefault('_controller', '\Drupal\oauth2_jwt_sso\Controller\OAuth2JwtSSOController::logout');
      $route->setOptions(['no_cache'=>TRUE]);
    }
  }

}
