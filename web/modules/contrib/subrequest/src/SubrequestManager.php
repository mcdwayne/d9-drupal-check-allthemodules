<?php

/**
 * @file
 * Contains \Drupal\subrequest\SubrequestManager.
 */

namespace Drupal\subrequest;

use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Routing\AccessAwareRouterInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;


/**
 * Subrequest manager.
 *
 * @todo: Create an interface.
 */
class SubrequestManager {

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The router.
   *
   * @var \Drupal\Core\Routing\AccessAwareRouterInterface
   */
  protected $router;

  /**
   * Route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $controllerResolver;

  /**
   * SubrequestManager constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Current request.
   * @param \Drupal\Core\Routing\AccessAwareRouterInterface $router
   *   The router.
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   Controller resolver.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   Controller resolver.
   */
  public function __construct(RequestStack $request_stack, AccessAwareRouterInterface $router, ControllerResolverInterface $controller_resolver, RouteMatchInterface $route_match) {
    $this->request = $request_stack->getCurrentRequest();
    $this->router = $router;
    $this->routeMatch = $route_match;
    $this->controllerResolver = $controller_resolver;
  }

  /**
   * Returns a Response for a given uri.
   */
  public function geResponse($uri) {

    $url = Url::fromUri($uri)->toString();

    $sub_request = Request::create(
      $url,
      'GET',
      [],
      [],
      [],
      $this->request->server->all()
    );

    try {
      $this->router->matchRequest($sub_request);
    }
    catch (\Exception $e) {
      watchdog_exception('subrequest', $e);
      return FALSE;
    }

    $controller = $this->controllerResolver->getController($sub_request);
    $arguments = $this->controllerResolver->getArguments($sub_request, $controller);

    $response = call_user_func_array($controller, $arguments);

    return $response;
  }

}
