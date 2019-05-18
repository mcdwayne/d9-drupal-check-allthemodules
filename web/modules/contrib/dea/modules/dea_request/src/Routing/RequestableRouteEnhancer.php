<?php
namespace Drupal\dea_request\Routing;
use Drupal\Core\Routing\Enhancer\RouteEnhancerInterface;

abstract class RequestableRouteEnhancer implements RouteEnhancerInterface {
  const ENTITY_OPERATION = '_entity_operation';
}