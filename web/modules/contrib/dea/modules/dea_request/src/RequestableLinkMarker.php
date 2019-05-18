<?php
namespace Drupal\dea_request;

use Drupal\Core\Routing\LazyRouteEnhancer;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Url;
use Drupal\dea_request\Routing\RequestableRouteEnhancer;
use Symfony\Cmf\Component\Routing\DynamicRouter;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Entity\EntityInterface;

/**
 * Service that marks links as requestable, with information about their entity
 * operations.
 */
class RequestableLinkMarker {
  use ContainerAwareTrait;

  /**
   * @var \Symfony\Cmf\Component\Routing\DynamicRouter
   */
  protected $router;

  /**
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Array of enhancer service id's that implement the RequestableRouteEnhancer
   * interface.
   */
  protected $requestableEnhancers = [];

  /**
   * RequestableLinkMarker constructor.
   * @param \Drupal\Core\Routing\LazyRouteEnhancer $route_enhancer
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   */
  public function __construct(DynamicRouter $router, RouteProviderInterface $route_provider, RequestStack $request_stack) {
    $this->router = $router;
    $this->routeProvider = $route_provider;
    $this->requestStack = $request_stack;
  }

  public function attributes(Url $url) {
    if (!$url->isRouted()) {
      return [];
    }

    $route = $this->routeProvider->getRouteByName($url->getRouteName());
    if (!array_reduce($route->getOption('_route_enhancers'), [$this, 'requestable'], FALSE)) {
      return [];
    }

    $request = $this->requestStack->getCurrentRequest();
    $sub_request = Request::create(
      $url->toString(TRUE)->getGeneratedUrl(),
      'GET',
      $request->query->all(),
      $request->cookies->all(),
      array(),
      $request->server->all()
    );

    list($entity, $operation) = array_values($this->router->matchRequest($sub_request)[RequestableRouteEnhancer::ENTITY_OPERATION]);

    /** @var EntityInterface $entity */

    return [
      'data-entity-type' => $entity->getEntityTypeId(),
      'data-entity-id' => $entity->id(),
      'data-entity-operation' => $operation,
    ];
  }

  protected function requestable($requestable, $enhancer) {
    return $requestable || $this->container->get($enhancer) instanceof RequestableRouteEnhancer;
  }

}