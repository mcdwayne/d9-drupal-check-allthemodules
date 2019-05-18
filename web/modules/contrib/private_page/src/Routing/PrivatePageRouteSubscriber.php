<?php

namespace Drupal\private_page\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class PrivatePageRouteSubscriber.
 *
 * Listens to the dynamic route events.
 */
class PrivatePageRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $collection->addRequirements(['_private_page' => 'TRUE']);
  }
}
