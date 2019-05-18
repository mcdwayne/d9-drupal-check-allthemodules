<?php

namespace Drupal\local_translation_interface\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\local_translation_interface\Controller\LocalTranslationInterfaceController;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class LocalTranslationRouteSubscriber.
 *
 * @package Drupal\local_translation_interface\Routing
 */
class LocalTranslationRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $route = $collection->get('locale.translate_page');
    if ($route instanceof Route) {
      $route->setDefault(
        '_controller',
        LocalTranslationInterfaceController::class . '::translatePage'
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
      'translate interface text into registered languages',
      'translate interface',
    ];
    return implode('+', $permissions);
  }

}
