<?php

namespace Drupal\config_src\Routing;

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
    // Change callback /admin/config/development/configuration.
    if ($route = $collection->get('config.sync')) {
      $defaults = ['_form' => '\Drupal\config_src\Form\ConfigSrc'];
      $route->addDefaults($defaults);
    }

  }

}
