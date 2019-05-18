<?php /**
       * @file
       * Contains \Drupal\password_reset_tabs\Routing\RouteSubscriber.
       */

namespace Drupal\password_reset_tabs\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    $route = $collection->get('user.reset');
    // Make a change to the controller.
    $route->setDefault('_controller', '\Drupal\password_reset_tabs\Controller\IndexController::resetPass');
  }

}
