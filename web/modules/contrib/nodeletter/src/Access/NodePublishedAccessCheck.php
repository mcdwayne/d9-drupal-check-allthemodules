<?php

namespace Drupal\nodeletter\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\Routing\Route;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\Entity\Node;

class NodePublishedAccessCheck implements AccessInterface {

  /**
   * Checks if a node is published.
   *
   * The value of the '_node_published' key has to be the name
   * of the route parameter representing a node.
   * Notice: The route parameter needs to be upcaseted properly to
   * be usable for this access check!
   *
   * @code
   * pattern: '/foo/{node}/bar'
   * requirements:
   *   _nodeletter_enabled: 'node'
   * options:
   *   parameters:
   *     node:
   *       type: entity:node
   * @endcode
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match) {
    // Split the type type and the value.
    $requirement = $route->getRequirement('_node_published');
    $route_params = $route_match->getParameters();

    if (!$route_params->has($requirement)) {
      return AccessResult::neutral();
    }

    $upcasted_param = $route_params->get($requirement);

    if ($upcasted_param instanceof Node) {
      return AccessResult::allowedIf($upcasted_param->isPublished());
    } else {
      return AccessResult::forbidden();
    }

  }
}
