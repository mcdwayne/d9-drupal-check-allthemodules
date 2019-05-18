<?php

namespace Drupal\path_theme\Theme;

use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Creates a Path Theme theme negotiator interface.
 */
class PathThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return !empty($this->getActiveConfigItem($route_match));
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return $this->getTheme($route_match);
  }

  /**
   * Get the theme to use for the current route.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   * @return string
   */
  protected function getTheme(RouteMatchInterface $routeMatch) {
    $config = \Drupal::config('path_theme.settings');
    $paths = $config->get('paths');
    $path = $this->getActiveConfigItem($routeMatch);

    $theme = (!empty($paths[$path])) ? $paths[$path] : '';

    return $theme;
  }

  /**
   * Gets the active config item based on the current path.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   * @return bool|int|string
   */
  protected function getActiveConfigItem(RouteMatchInterface $routeMatch) {
    $config = \Drupal::config('path_theme.settings');
    $paths = $config->get('paths');

    $item = false;

    /** @var PathMatcherInterface $pathMatcher */
    $pathMatcher = \Drupal::service('path.matcher');

    if ($pathMatcher->isFrontPage()) {
      return $item;
    }

    $currentPath = \Drupal::request()->getRequestUri();
    if (strpos($currentPath, '?') !== FALSE) {
      list($currentPath) = explode('?', $currentPath);
    }

    if (!empty($paths)) {
      foreach ($paths as $path => $theme) {
        if (\Drupal::service('path.matcher')->matchPath($currentPath, $path)) {
          $item = $path;

          break;
        }
      }
    }

    return $item;
  }
}
