<?php

namespace Drupal\ga_reports\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for google analytics reports routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('ga_reports_api.settings')) {
      $route->setDefault('_form', 'Drupal\ga_reports\Form\GaReportsAdminSettingsForm');
    }
  }

}
