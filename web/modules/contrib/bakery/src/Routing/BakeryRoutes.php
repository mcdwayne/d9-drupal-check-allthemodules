<?php

namespace Drupal\bakery\Routing;

use Symfony\Component\Routing\Route;

/**
 * Defines dynamic routes.
 */
class BakeryRoutes {

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $routes = array();
    if (\Drupal::config('bakery.settings')->get('bakery_is_master')) {
      $routes['bakery.register'] = new Route(
        // Path to attach this route to:
        '/bakery',
        array(
          '_controller' => '\Drupal\bakery\Controller\BakeryController::bakeryRegister',
          '_title' => 'Register',
        ),
        array(
          '_custom_access'  => '\Drupal\bakery\Controller\BakeryController::userIsAnonymous',
        )
      );
      $routes['bakery.login'] = new Route(
        // Path to attach this route to:
        '/bakery/login',
        array(
          '_controller' => '\Drupal\bakery\Controller\BakeryController::bakeryLogin',
          '_title' => 'Login',
        ),
        array(
          '_custom_access'  => '\Drupal\bakery\Controller\BakeryController::userIsAnonymous',
        )
      );
      $routes['bakery.validate'] = new Route(
        // Path to attach this route to:
        '/bakery/validate',
        array(
          '_controller' => '\Drupal\bakery\Controller\BakeryController::bakeryEatThinmintCookie',
          '_title' => 'Validate',
        ),
        array(
          '_custom_access'  => '\Drupal\bakery\Controller\BakeryController::bakeryTasteThinmintCookie',
        )
      );
      $routes['bakery.create'] = new Route(
        // Path to attach this route to:
        '/bakery/create',
        array(
          '_controller' => '\Drupal\bakery\Controller\BakeryController::bakeryEatGingerbreadCookie',
          '_title' => 'Bakery create',
        ),
        array(
          '_custom_access'  => '\Drupal\bakery\Controller\BakeryController::bakeryTasteGingerbreadCookie',
        )
      );
    }
    else {
      $routes['bakery.register'] = new Route(
        // Path to attach this route to:
        '/bakery',
        array(
          '_controller' => '\Drupal\bakery\Controller\BakeryController::bakeryRegisterReturn',
          '_title' => 'Register',
        ),
        array(
          '_permission'  => 'access content',
        )
      );
      $routes['bakery.login'] = new Route(
        // Path to attach this route to:
        '/bakery/login',
        array(
          '_controller' => '\Drupal\bakery\Controller\BakeryController::bakeryLoginReturn',
          '_title' => 'Login',
        ),
        array(
          '_permission'  => 'access content',
        )
      );
      $routes['bakery.update'] = new Route(
        // Path to attach this route to:
        '/bakery/update',
        array(
          '_controller' => '\Drupal\bakery\Controller\BakeryController::bakeryEatStroopwafelCookie',
          '_title' => 'Update',
        ),
        array(
          '_custom_access'  => '\Drupal\bakery\Controller\BakeryController::bakeryTasteStroopwafelCookie',
        )
      );

      $routes['bakery.repair'] = new Route(
        // Path to attach this route to:
        '/bakery/repair',
        array(
          '_form' => '\Drupal\bakery\Forms\BakeryUncrumbleForm',
          '_title' => 'Repair account',
        ),
        array(
          '_custom_access'  => '\Drupal\bakery\Controller\BakeryController::bakeryUncrumbleAccess',
        )
      );

      $routes['bakery.pull'] = new Route(
        // Path to attach this route to:
        '/admin/config/people/bakery',
        array(
          '_form' => '\Drupal\bakery\Forms\BakeryPullForm',
          '_title' => 'Pull Bakery user',
        ),
        array(
          '_permission'  => 'access content',
        )
      );
    }
    return $routes;
  }

}
