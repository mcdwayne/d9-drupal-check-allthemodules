<?php

namespace Drupal\user_bundle\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for User bundle routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Instead of having the 'user.admin_create' route go directly to an entity
    // creation form, we instead direct it to a page that first asks which type
    // of user we should create.
    if ($route = $collection->get('user.admin_create')) {
      $defaults = $route->getDefaults();
      if (isset($defaults['_entity_form'])) {
        unset($defaults['_entity_form']);
      }
      $defaults['_controller'] = '\Drupal\user_bundle\Controller\TypedUserController::adminCreatePage';
      $route->setDefaults($defaults);
      $route->setOption('_admin_route', TRUE);
    }
  }

}
