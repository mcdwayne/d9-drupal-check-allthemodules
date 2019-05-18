<?php

namespace Drupal\icon\Routing;

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
    /**
     * @FIXME
     * Parts of your hook_menu_alter() logic should be moved in here. You should NOT
     * use this method to define new routes -- read the documentation at
     * https://www.drupal.org/node/2122201 to learn how to define dynamic routes --
     * but to alter existing ones.
     */
  }

}
