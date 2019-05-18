<?php
namespace Drupal\dea_request\Routing;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

class RequestableEntityRouteEnhancer extends RequestableRouteEnhancer {
  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    if ($route->hasRequirement('_entity_access')) {
      list($entity_type, $operation) = explode('.', $route->getRequirement('_entity_access'));
      return $entity_type != 'dea_request';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {

    list($entity_type, $operation) = explode('.', $defaults[RouteObjectInterface::ROUTE_OBJECT]->getRequirement('_entity_access'));
    
    if (array_key_exists($entity_type, $defaults)) {
      $defaults[RequestableRouteEnhancer::ENTITY_OPERATION] = [
        'entity' => $defaults[$entity_type],
        'operation' => $operation,
      ];
    }
    return $defaults;
  }

}
