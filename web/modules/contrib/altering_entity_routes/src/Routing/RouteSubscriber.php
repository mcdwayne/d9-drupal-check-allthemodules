<?php

namespace Drupal\altering_entity_routes\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RouteProvider;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

use Drupal\eck\Entity\EckEntityType;


/**
 * Class RouteSubscriber.
 *
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {


  /**
   * {@inheritdoc}
   */

  protected function alterRoutes(RouteCollection $collection) {
      foreach (EckEntityType::loadMultiple() as $eck_type) {
          if ($route = $collection->get('entity.' . $eck_type->id . '.canonical')) {
              $route->setPath('/' . $eck_type->id . '/{' . $eck_type->id . '}');
             // dsm($route);
          }
//          var_dump($eck_type->id);
      }

}




}


