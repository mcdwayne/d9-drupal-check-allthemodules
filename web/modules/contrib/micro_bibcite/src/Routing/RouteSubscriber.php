<?php

namespace Drupal\micro_bibcite\Routing;

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

    $route = $collection->get('view.bibcite_reference_site_admin.page_1');
    if ($route) {
      $route->addRequirements([
        '_custom_access' => '\Drupal\micro_bibcite\Access\MicroBibciteAccess:access',
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
