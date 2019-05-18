<?php

namespace Drupal\config_ignore_collection\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('config.sync')) {
      $route->setDefault('_form', '\Drupal\config_ignore_collection\Form\ConfigIgnoreCollectionSync');
    }
    elseif ($route = $collection->get('config.import_single')) {
      $route->setDefault('_form', '\Drupal\config_ignore_collection\Form\ConfigIgnoreCollectionSingleImportForm');
    }
  }

}
