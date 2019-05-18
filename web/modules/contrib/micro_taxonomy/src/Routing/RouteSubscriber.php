<?php

namespace Drupal\micro_taxonomy\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Add custom access on site taxonomy term tab view.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Add as custom access requirement on tab content listing per site.
    $route = $collection->get('view.site_taxonomy_term.tab');
    if ($route) {
      $route->addRequirements([
        '_custom_access' => '\Drupal\micro_taxonomy\Access\TabTermAccess:access',
      ]);

      $options = [
        '_admin_route' => TRUE,
        'parameters' => [
          'site' => [
            'type' => 'entity:site',
            'with_config_overrides' => TRUE,
          ],
          'taxonomy_vocabulary' => [
            'type' => 'entity:taxonomy_vocabulary',
            'with_config_overrides' => TRUE,
          ],
        ],
      ];
      $route->addOptions($options);
    }

    $route = $collection->get('view.site_taxonomy_term.tab_all');
    if ($route) {
      $route->addRequirements([
        '_custom_access' => '\Drupal\micro_taxonomy\Access\TabTermAccess:access',
      ]);

      $options = [
        '_admin_route' => TRUE,
        'parameters' => [
          'site' => [
            'type' => 'entity:site',
            'with_config_overrides' => TRUE,
          ],
          'taxonomy_vocabulary' => [
            'type' => 'entity:taxonomy_vocabulary',
            'with_config_overrides' => TRUE,
          ],
        ],
      ];
      $route->addOptions($options);
    }
  }

}
