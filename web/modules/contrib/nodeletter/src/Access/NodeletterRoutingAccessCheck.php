<?php

namespace Drupal\nodeletter\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\nodeletter\NodeletterService;
use Symfony\Component\Routing\Route;

class NodeletterRoutingAccessCheck implements AccessInterface {

  /**
   * Checks whether nodeletter is enabled for a node or node-type
   *
   * The value of the '_nodeletter_enabled' key has to be the name
   * of the route parameter representing a node or node-type entity.
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
   * @code
   * pattern: '/foo/{node_type}/bar'
   * requirements:
   *   _nodeletter_enabled: 'node_type'
   * options:
   *   parameters:
   *     node_type:
   *       type: entity:node_type
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
    $requirement = $route->getRequirement('_nodeletter_enabled');
    $route_params = $route_match->getParameters();

    if (!$route_params->has($requirement)) {
      return AccessResult::neutral();
    }

    $upcasted_param = $route_params->get($requirement);

    if ($upcasted_param instanceof Node) {
      $node_type_id = $upcasted_param->getType();
    } else if ($upcasted_param instanceof NodeType) {
      $node_type_id = $upcasted_param->id();
    } else {
      return AccessResult::forbidden();
    }

    /** @var NodeletterService $nodeletter */
    $nodeletter = nodeletter_service();
    $enabled = $nodeletter->nodeTypeEnabled($node_type_id);
    return AccessResult::allowedIf($enabled);
  }
}
