<?php
/**
 * @file
 * Contains \Drupal\example\Routing\ExampleRoutes.
 */

namespace Drupal\form_mode_routing\Routing;

use Symfony\Component\Routing\Route;

/**
 * Defines dynamic routes.
 */
class NewRoutes {

  /**
   * {@inheritdoc}
   */
  public function routes() {
    //@todo depency inject these.
    $modes = \Drupal::entityTypeManager()->getStorage('form_routing_entity')->loadMultiple();
    $route_provider = \Drupal::service('router.route_provider');

    // Create new if dont exist.
    $routes = [];
    if (!empty($modes)) {
      foreach($modes as $mode) {
        $exists = false;
        $form_mode = $mode->label();
        $title = 'Edit';
        if (!empty($mode->title)) {
          $title = $mode->title;
        }
        $route_name = 'form_mode_routing.' . $form_mode;
        //$exists = count($route_provider->getRoutesByNames([$route_name])) === 1;
        if ($exists == false) {
          // Create
          $routes[$route_name] = new Route(
          // Path to attach this route to:
            $mode->path,
            // Route defaults:
            [
              '_entity_form' => $form_mode,
              '_title_callback' => '\Drupal\Core\Entity\Controller\EntityController::title',
            ],
            // Route requirements:
            [
              '_custom_access'  => '\Drupal\form_mode_routing\Access\CustomAccessCheck::access',
            ],
            // Route Options
            [],
            //rout host
            '',
            [],
            [
              'GET',
              'POST',
            ]
          );
        }
      }
    }
    return $routes;

  }

}