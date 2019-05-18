<?php

/**
 * @file
 * Contains \Drupal\offline_app\OfflineApp.
 */

namespace Drupal\offline_app;

use Drupal\Core\Url;

class OfflineApp {

  /**
   * Returns whether this request is an Offline app request or not.
   *
   * @return bool
   *   TRUE if offline request, FALSE otherwise.
   */
  public static function isOfflineRequest() {
    $offline_routes = [
      'offline_app.appcache.iframe',
      'offline_app.appcache.fallback',
      'offline_app.appcache.offline'
    ];
    $route_match = \Drupal::routeMatch();
    if (in_array($route_match->getRouteName(), $offline_routes)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Returns whether this request is for the homepage or not.
   *
   * @return bool
   *   TRUE if homepage, FALSE otherwise.
   */
  public static function isOfflineHomepage() {
    $route_match = \Drupal::routeMatch();
    if ($route_match->getRouteName() == 'offline_app.appcache.fallback') {
      return TRUE;
    }

    if ($route_match->getRouteName() == 'offline_app.appcache.offline') {
      $alias = $route_match->getParameter('offline_alias');
      if (empty($alias)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Returns the offline alias for a node.
   *
   * @param integer $nid
   *   The node ID.
   * @param bool $return_as_string
   *   Whether to return as string or not.
   *
   * @return string $alias
   *   The alias string.
   */
  public static function getOfflineNodeAlias($nid, $return_as_string = TRUE) {
    static $aliases = NULL;

    if (!$aliases) {
      $pages = explode("\n", trim(\Drupal::config('offline_app.appcache')->get('pages')));
      foreach ($pages as $page) {
        list ($alias, $conf) = explode('/', trim($page));
        if (!empty($conf)) {
          list ($type, $id) = explode(':', $conf, 2);
          if ($type == 'node') {
            $aliases[$id] = $alias;
          }
        }
      }
    }

    if (isset($aliases[$nid])) {
      if ($return_as_string) {
        return Url::fromRoute('offline_app.appcache.offline', ['offline_alias' => $aliases[$nid]])
          ->toString();
      }
      else {
        return Url::fromRoute('offline_app.appcache.offline', ['offline_alias' => $aliases[$nid]]);
      }
    }

    return '/';
  }
}
