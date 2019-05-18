<?php

namespace Drupal\ext_redirect\Service;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\AdminContext;

/**
 * Class CurrentPath.
 */
class CurrentUrl implements CurrentUrlInterface {

  /**
   * Symfony\Component\HttpFoundation\Request definition.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;
  /**
   * Drupal\Core\Path\CurrentPathStack definition.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $pathCurrent;
  /**
   * Drupal\Core\Path\AliasManager definition.
   *
   * @var \Drupal\Core\Path\AliasManager
   */
  protected $pathAliasManager;
  /**
   * \Symfony\Component\Routing\Route definition.
   *
   * @var \Symfony\Component\Routing\Route
   */
  protected $route;
  /**
   * Drupal\Core\Routing\AdminContext definition.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $routerAdminContext;
  /**
   * Constructs a new CurrentPath object.
   */

  /**
   * Indicates whether current path is front page.
   *
   * @var boolean
   */
  private $isFrontPage;

  public function __construct(RequestStack $request_stack, CurrentPathStack $path_current, AliasManagerInterface $path_alias_manager, CurrentRouteMatch $current_route_match, AdminContext $router_admin_context, PathMatcherInterface $path_matcher) {
    $this->request = $request_stack->getCurrentRequest();
    $this->pathCurrent = $path_current;
    $this->pathAliasManager = $path_alias_manager;
    $this->route = $current_route_match->getRouteObject();
    $this->routerAdminContext = $router_admin_context;
    $this->isFrontPage = $path_matcher->isFrontPage();
  }

  /**
   * {@inheritdoc}
   */
  public function isAdminPath() {
    $this->routerAdminContext->isAdminRoute($this->route);
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    $path = NULL;

    if (!is_null($path)) {
      return $path;
    }

    $query_string = '';

    // The current path includes the query string, if any.
    if ($query = $this->request->getQueryString()) {
      $query_string = '?' . $query;
    }

    $path = $this->isFrontPage ? '' : $this->pathCurrent->getPath($this->request);

    if (!empty($path)) {
      $path = $this->pathAliasManager->getAliasByPath($path);
    }

    // @TODO consider adding alter hook here.

    return $path . $query_string;
  }

  /**
   * {@inheritdoc}
   */
  public function getScheme() {
    return $this->request->getScheme();
  }

  /**
   * {@inheritdoc}
   */
  public function getHost() {
    return $this->request->getHost();
  }
}
