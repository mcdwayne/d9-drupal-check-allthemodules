<?php

namespace Drupal\empty_page\Routing;

use Symfony\Component\Routing\Route;
use Drupal\empty_page\Controller\EmptyPageController;

/**
 * Defines a route subscriber to register a url from empty pages.
 */
class EmptyPages {

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $routes = [];
    $callbacks = EmptyPageController::emptyPageGetCallbacks();
    if (!empty($callbacks)) {
      foreach ($callbacks as $cid => $callback) {
        $routes['empty_page.page_' . $cid] = new Route(
          $callback['path'],
          [
            '_controller' => '\Drupal\empty_page\Controller\EmptyPage::emptyCallback',
            '_title' => $callback['page_title'],
          ],
          [
            '_permission'  => 'view empty pages',
          ]
        );
      }
    }
    return $routes;
  }

}
