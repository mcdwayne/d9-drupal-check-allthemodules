<?php

namespace Drupal\field_collection_access\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Add view requriement to canocical url.
    if ($route = $collection->get('entity.field_collection_item.canonical')) {
      $requirements = $route->getRequirements();
      unset($requirements['_access_field_collection_item_host']);
      $requirements['_field_collection_access_grants'] = 'view';
      $route->setRequirements($requirements);
    }

    // Add update requriement to canocical url.
    if ($route = $collection->get('entity.field_collection_item.edit_form')) {
      $requirements = $route->getRequirements();
      unset($requirements['_access_field_collection_item_host']);
      $requirements['_field_collection_access_grants'] = 'update';
      $route->setRequirements($requirements);
    }

    // Add delete requriement to canocical url.
    if ($route = $collection->get('entity.field_collection_item.delete_form')) {
      $requirements = $route->getRequirements();
      unset($requirements['_access_field_collection_item_host']);
      $requirements['_field_collection_access_grants'] = 'delete';
      $route->setRequirements($requirements);
    }
  }

}
