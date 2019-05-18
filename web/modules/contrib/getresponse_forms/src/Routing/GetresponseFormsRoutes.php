<?php

namespace Drupal\getresponse_forms\Routing;

use Symfony\Component\Routing\Route;

/**
 * Defines dynamic routes for GetResponse forms rendered as pages.
 */
class GetresponseFormsRoutes {

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $routes = array();

    $signups = getresponse_forms_load_multiple();

    /* @var $signup \Drupal\getresponse_forms\Entity\GetresponseForms */
    foreach ($signups as $signup) {
      if ((intval($signup->mode) == GETRESPONSE_FORMS_PAGE) || (intval($signup->mode) == GETRESPONSE_FORMS_BOTH)) {
        $routes['getresponse_forms.' . $signup->id] = new Route(
          // Route Path.
          '/' . $signup->path,
          // Route defaults.
          array(
            '_controller' => '\Drupal\getresponse_forms\Controller\GetresponseFormsController::page',
            '_title' => $signup->title,
            'signup_id' => $signup->id,
          ),
          // Route requirements.
          array(
            '_permission'  => 'access getresponse forms pages',
          )
        );
      }
    }

    return $routes;
  }

}
