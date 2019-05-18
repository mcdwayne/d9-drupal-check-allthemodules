<?php

declare(strict_types = 1);

namespace Drupal\link_trail_by_path\Path;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

/**
 * Class CurrentPathHelper.
 */
class CurrentPathHelper {
  /**
   * The RouteMatch service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The Request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  private $context;

  /**
   * CurrentPathHelper constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\Core\Routing\RequestContext $context
   *   The request context.
   */
  public function __construct(RouteMatchInterface $route_match, RequestContext $context) {
    $this->routeMatch = $route_match;
    $this->context = $context;
  }

  /**
   * Get urls.
   *
   * @return \Drupal\Core\Url[]
   *   The URLs.
   */
  public function getUrls() {
    $trail_urls = $this->getCurrentPathUrls();
    if ($current_request_url = $this->getCurrentRequestUrl()) {
      $trail_urls[] = $current_request_url;
    }

    return $trail_urls;
  }

  /**
   * Create a Url Object from a relative uri (e.g. /news/drupal8-release-party)
   *
   * @param string $relativeUri
   *   The URL.
   *
   * @return \Drupal\Core\Url
   *   The URL object.
   */
  protected function createUrlFromRelativeUri($relativeUri) {
    // @see https://www.drupal.org/node/2810961
    if (UrlHelper::isExternal(substr($relativeUri, 1))) {
      return Url::fromUri('base:' . $relativeUri);
    }

    return Url::fromUserInput($relativeUri);
  }

  /**
   * Get current path urls.
   *
   * @return \Drupal\Core\Url[]
   *   The paths.
   */
  protected function getCurrentPathUrls() {
    $urls = [];

    $path = trim($this->context->getPathInfo(), '/');
    $path_elements = explode('/', $path);

    while (count($path_elements) > 1) {
      array_pop($path_elements);
      $url = $this->createUrlFromRelativeUri('/' . implode('/', $path_elements));

      if ($url->isRouted()) {
        $urls[] = $url;
      }
    }

    return array_reverse($urls);
  }

  /**
   * Returns the current request Url.
   *
   * NOTE: There is a difference between $this->routeMatch->getRouteName and
   * $this->context->getPathInfo() for now it seems more logical to prefer the
   * latter, because that's the "real" url that visitors enter in their browser.
   *
   * @return \Drupal\Core\Url|null
   *   The URLs or null.
   */
  protected function getCurrentRequestUrl() {
    $current_pathinfo_url = $this->createUrlFromRelativeUri($this->context->getPathInfo());

    if ($current_pathinfo_url->isRouted()) {
      return $current_pathinfo_url;
    }

    if ($route_name = $this->routeMatch->getRouteName()) {
      $route_parameters = $this->routeMatch->getRawParameters()->all();

      return new Url($route_name, $route_parameters);
    }

    return NULL;
  }

}
