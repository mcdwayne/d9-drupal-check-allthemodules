<?php

namespace Drupal\registration_types\Routing;

use Drupal\registration_types\Entity\RegistrationType;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\registration_types\Routing
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
  }

  /**
   * Returns a set of route objects.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   A route collection.
   */
  public function routes() {
    $collection = new RouteCollection();

    $types = RegistrationType::loadMultiple();
    // @todo: exclude default
    foreach ($types as $machine_name => $type) {
      if (!$type->getEnabled()) {
        continue;
      }
      // @todo: check if url encoding is required here
      $path = !empty($type->getCustomPath()) ? ($type->getCustomPath()) : 'user/register/' . $machine_name;
      $route = new Route(
        // Path to attach this route to:
        $path,
        // Route defaults:
        [
          // @todo: maybe here should be _entity_form instead of controller as in user module
          //   standard registration
          '_controller' => '\Drupal\registration_types\Controller\RegistrationTypesController::page',
          'registration_type_id' => $machine_name,
          '_title' => $type->getPageTitle(),
        ],
        // Route requirements:
        [
          '_access_user_register'  => 'TRUE',
        ]
      );

      // Add the route under the name 'example.content'.
      $collection->add('registration_types.' . $machine_name, $route);
    }

    return $collection;
  }

}
