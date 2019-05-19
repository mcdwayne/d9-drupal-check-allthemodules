<?php

namespace Drupal\views_restricted\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\views_restricted\ViewsRestrictedHelper;
use Symfony\Component\Routing\RouteCollection;

/**
 * Views restricted event subscriber.
 */
class ViewsRestrictedRouteSubscriber extends RouteSubscriberBase {

  /**
   * Alters existing routes for a specific collection.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection for adding routes.
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach (ViewsRestrictedHelper::getRouteAlter() as $name => $info) {
      $route = $collection->get($name);

      // Add parameter views_restricted.
      $route->setPath($route->getPath() . '/{views_restricted}');
      $route->setDefault('views_restricted', 'views_restricted_legacy');
      $route->setOption('parameters', (array)$route->getOption('parameters') + ['views_restricted' => ['type' => 'views_restricted']]);

      // Swap in our controller.
      if (isset($info['controller'])) {
        $route->setDefault('_controller', $info['controller']);
      }

      // Remove entity/permission access, and keep hints for legacy controller.
      if ($legacyEntityAccessFull = $route->getRequirement('_entity_access')) {
        list($legacyEntityAccessObject, $legacyEntityAccess) = explode('.', $legacyEntityAccessFull, 2);
        if ($legacyEntityAccessObject !== 'view') {
          throw new \LogicException(sprintf('Unexpected prior entity access: %s in %s', $route->getRequirement('_entity_access'), $name));
        }
        $route->setDefault('_views_restricted_decorated_entity_access', $legacyEntityAccess);
        // Remove.
        $requirements = $route->getRequirements();
        unset($requirements['_entity_access']);
        $route->setRequirements($requirements);
      }
      elseif ($legacyPermission = $route->getRequirement('_permission')) {
        $route->setDefault('_views_restricted_decorated_permission', $legacyPermission);
        // Remove.
        $requirements = $route->getRequirements();
        unset($requirements['_permission']);
        $route->setRequirements($requirements);
      }
      else {
        throw new \LogicException(sprintf('Unexpected route without access: %s', $name));
      }

      // Add our defaults.
      if (isset($info['defaults'])) {
        $route->addDefaults($info['defaults']);
      }

      // Re-add access: \Drupal\views_restricted\Plugin\ViewsRestricted\ViewsRestrictedControllerLegacy
      $route->setRequirement('_custom_access', '\Drupal\views_restricted\Access\AccessController::access');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Come after config translation.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -199];
    return $events;
  }

}
