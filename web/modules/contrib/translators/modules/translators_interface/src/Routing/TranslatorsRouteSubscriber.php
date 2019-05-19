<?php

namespace Drupal\translators_interface\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\translators_interface\Controller\TranslatorsInterfaceController;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class TranslatorsRouteSubscriber.
 *
 * @package Drupal\translators_interface\Routing
 */
class TranslatorsRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $route = $collection->get('locale.translate_page');
    if ($route instanceof Route) {
      $route->setDefault(
        '_controller',
        TranslatorsInterfaceController::class . '::translatePage'
      );
      $route->setRequirement('_permission', self::getPermissions());
    }
  }

  /**
   * Get permission value for the route requirements.
   *
   * @return string
   *   String-formatted list of permissions,
   *   separated with "+" to implement OR logic.
   */
  private static function getPermissions() {
    static $permissions = [
      'translate interface text into translation skills',
      'translate interface',
    ];
    return implode('+', $permissions);
  }

}
