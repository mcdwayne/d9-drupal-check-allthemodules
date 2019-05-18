<?php

namespace Drupal\js\Plugin\Js;

use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @JsCallback(
 *   id = "js.content",
 *   allowed_methods = { "GET" },
 *   csrf_token = FALSE,
 * )
 */
class Content extends JsCallbackBase {

  /**
   * @var \Symfony\Component\HttpKernel\Controller\ControllerResolverInterface
   */
  protected $controllerResolver;

  /**
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * The access aware router.
   *
   * @var \Drupal\Core\Routing\AccessAwareRouterInterface
   */
  protected $router;

  /**
   * @var \Drupal\Core\Controller\TitleResolver
   */
  protected $titleResolver;

  /**
   * @var \Symfony\Cmf\Component\Routing\NestedMatcher\NestedMatcher
   */
  protected $routeMatcher;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->controllerResolver = \Drupal::service('controller_resolver');
    $this->router = \Drupal::service('router');
    $this->titleResolver = \Drupal::service('title_resolver');
    $this->routeMatcher = \Drupal::service('router.matcher');
    $this->currentRouteMatch = \Drupal::service('current_route_match');
  }

  /**
   * {@inheritdoc}
   */
  public function access($path = '') {
    return \Drupal::accessManager()->checkRequest($this->getRequest($path));
  }

  /**
   * Creates a Request object for the given path.
   *
   * @param string $path
   *   The path to create request from. Must be internal.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   A request object.
   */
  protected function getRequest($path = '') {
    $request = Request::create($path);
    $this->router->matchRequest($request);
    return $request;
  }

  /**
   * Retrieves the route object from a path.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Request object to create route from.
   *
   * @return \Symfony\Component\Routing\Route|null
   */
  protected function getRoute(Request $request) {
    return $this->currentRouteMatch->getRouteMatchFromRequest($request)->getRouteObject();
  }

  /**
   * {@inheritdoc}
   */
  public function execute($path = '') {
    // Normalize absolute URLs to an internal relative path.
    $base_url = \Drupal::service('router.request_context')->getCompleteBaseUrl();
    if (!UrlHelper::isExternal($path) || UrlHelper::externalIsLocal($path, $base_url)) {
      $path = preg_replace('`^' . preg_quote($base_url) . '`', '', $path);
    }

    $request = $this->getRequest($path);
    $controller = $this->controllerResolver->getController($request);

    // Immediately return if there is no controller.
    if (!$controller) {
      throw new NotFoundHttpException();
    }

    // Set the title.
    if ($title = $this->titleResolver->getTitle($request, $this->getRoute($request))) {
      $this->setTitle($title);
    }

    return call_user_func_array($controller, $this->controllerResolver->getArguments($request, $controller));
  }

}
