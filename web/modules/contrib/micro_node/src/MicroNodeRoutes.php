<?php

namespace Drupal\micro_node;

use Drupal\node\Entity\NodeType;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class MicroNodeRoutes {

  const ROUTE_NAME_PREFIX = "micro_node.node.add.";

  public function routes() {
    $node_types = \Drupal::config('micro_node.settings')->get('node_types');
    if (empty($node_types)) {
      return;
    }

    $defaults = [
      '_controller' => '\Drupal\node\Controller\NodeController::add',
      '_title_callback' => '\Drupal\node\Controller\NodeController::addPageTitle',
    ];
    $requirements = [
      'site' => '\d+',
      '_custom_access' => '\Drupal\micro_node\Access\NodeAddAccess:access',
    ];
    $options = [
      '_node_operation_route' => TRUE,
      '_admin_route' => TRUE,
      'parameters' => [
        'site' => [
          'type' => 'entity:site',
          'with_config_overrides' => TRUE,
        ],
        'node_type' => [
          'with_config_overrides' => TRUE,
        ],
      ],
    ];

    $routeCollection = new RouteCollection();
    foreach (array_keys(NodeType::loadMultiple()) as $node_type) {
      if (in_array($node_type, $node_types)) {
        $defaults['node_type'] = $node_type;
        $routeCollection->add(static::ROUTE_NAME_PREFIX . $node_type, new Route("/site/{site}/add/$node_type", $defaults, $requirements, $options));
      }
    }
    return $routeCollection;
  }

}
