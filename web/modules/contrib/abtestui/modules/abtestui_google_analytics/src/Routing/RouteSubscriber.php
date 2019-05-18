<?php

namespace Drupal\abtestui_google_analytics\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\abtestui_google_analytics\Routing
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $testListRoute = $collection->get('abtestui.test_list');
    if (NULL === $testListRoute) {
      return;
    }

    $testListRoute->setDefault(
      '_controller',
      '\Drupal\abtestui_google_analytics\Controller\ListController::renderTestList'
    );
  }

}
