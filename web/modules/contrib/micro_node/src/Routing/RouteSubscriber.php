<?php

namespace Drupal\micro_node\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Add custom access on site content tab view.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Add as custom access requirement on tab content listing per site.
    $route = $collection->get('view.site_content.tab');
    if ($route) {
      $route->addRequirements([
        '_custom_access' => '\Drupal\micro_node\Access\TabContentAccess:access',
      ]);

      $options = [
        '_admin_route' => TRUE,
        'parameters' => [
          'site' => [
            'type' => 'entity:site',
            'with_config_overrides' => TRUE,
          ],
        ],
      ];
      $route->addOptions($options);
    }
  }

}
