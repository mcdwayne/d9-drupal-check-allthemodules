<?php
/**
 * @file
 * Contains \Drupal\example\Routing\CctagsRoutes.
 */

namespace Drupal\cctags\Routing;

use Symfony\Component\Routing\Route;

/**
 * Defines dynamic routes.
 */
class CctagsRoutes {

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $routes = array();

    $items = _cctags_get_settings();
    foreach($items as $key => $item) {
      if($item['page']) {
        $routes['cctags.route'. $key] = new Route(
          $item['page_path'],
          array(
            '_controller' => '\Drupal\cctags\Controller\CctagsController::content',
            '_title' => $item['page_title'],
            'cctid' => $key,
            'page_amount' => $item['page_amount'],
            'page_mode' => $item['page_mode'],
            'page_extra_class' => $item['page_extra_class'],
            'page_vocname' => $item['page_vocname'],
          ),
          array(
            '_permission'  => 'access content',
          )
        );
      }

    }
    return $routes;
  }

}