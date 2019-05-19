<?php

namespace Drupal\template_breadcrumb;

use Drupal\Core\Routing\Router;
use Drupal\Core\Breadcrumb\BreadcrumbManager;
use Drupal\Core\Url;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Drupal\Core\Routing\RouteMatch;

/**
 * Build a breadcrumb for a given URL.
 */
class BreadcrumbBuilder {

  /**
   * The router object without access checks.
   *
   * @var \Drupal\Core\Routing\Router
   */
  protected $router;

  /**
   * The breadcrumb manager.
   *
   * @var \Drupal\Core\Breadcrumb\BreadcrumbManager
   */
  protected $breadcrumb;

  /**
   * The router object index for raw variables.
   */
  const RAW_VARIABLES = '_raw_variables';

  /**
   * Constructs a breadcrumb builder.
   *
   * @param Drupal\Core\Routing\Router $router
   *   The router object without access checks.
   * @param Drupal\Core\Breadcrumb\BreadcrumbManager $breadcrumb
   *   The breadcrumb manager.
   */
  public function __construct(Router $router, BreadcrumbManager $breadcrumb) {
    $this->router = $router;
    $this->breadcrumb = $breadcrumb;
  }

  /**
   * Build the breadcrumb from a url object.
   *
   * @param Drupal\Core\Url $url
   *   The url for the page to show the breadcrumb of.
   *
   * @return Breadcrumb
   *   Breadcrumb object for the given page.
   */
  public function build(Url $url) {
    $routeMatch = $this->getRouteMatchForUrl($url);
    return $this->breadcrumb->build($routeMatch);
  }

  /**
   * Get the route match from the URL as a string.
   *
   * @param Drupal\Core\Url $url
   *   The url for the page to show the breadcrumb of.
   *
   * @return Drupal\Core\Routing\RouteMatch
   *   The RouteMatch object for the given url.
   */
  protected function getRouteMatchForUrl(Url $url) {
    $routeArray = $this->router->match($url->toString());
    $routeName = $routeArray[RouteObjectInterface::ROUTE_NAME];
    $routeObject = $routeArray[RouteObjectInterface::ROUTE_OBJECT];
    $routeRawVariables = $routeArray[self::RAW_VARIABLES]->all();
    return new RouteMatch($routeName, $routeObject, $routeArray, $routeRawVariables);
  }

}
