<?php

namespace Drupal\purest;

use Drupal\Core\Menu\MenuActiveTrail;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * PurestMenuActiveTrail.
 */
class PurestMenuActiveTrail extends MenuActiveTrail {

  /**
   * RequestContext.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $context;

  /**
   * LanguageManagerInterface.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * MenuTrailByPathActiveTrail constructor.
   *
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   MenuLinkManagerInterface.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   RouteMatchInterface.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   CacheBackendInterface.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   LockBackendInterface.
   * @param \Drupal\Core\Routing\RequestContext $context
   *   RequestContext.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   LanguageManagerInterface.
   */
  public function __construct(
    MenuLinkManagerInterface $menu_link_manager,
    RouteMatchInterface $route_match,
    CacheBackendInterface $cache,
    LockBackendInterface $lock,
    RequestContext $context,
    LanguageManagerInterface $languageManager
  ) {
    parent::__construct($menu_link_manager, $route_match, $cache, $lock);

    $this->context = $context;
    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveTrailIds($menu_name) {
    return $this->get($menu_name);
  }

  /**
   * Get the active trail by IDs by route and route_parameter.
   *
   * Useful for situations where you wish to simulate an active trail without
   * being on the 'correct' route already.
   *
   * @param string $menu_name
   *   The menu's ID.
   * @param string $route
   *   The route ID.
   * @param array $route_parameters
   *   Route parameters.
   *
   * @return array
   *   An array of active trail IDs.
   */
  public function getActiveTrailIdsByRoute($menu_name, $route, array $route_parameters) {
    return $this->doGetActiveTrailIds($menu_name, $route, $route_parameters);
  }

  /**
   * {@inheritdoc}
   */
  protected function doGetActiveTrailIds($menu_name, $route_name = NULL, $route_parameters = NULL) {

    // Parent ids; used both as key and value to ensure uniqueness.
    // We always want all the top-level links with parent == ''.
    $active_trail = ['' => ''];

    // If a link in the given menu indeed matches the route, then use it to
    // complete the active trail.
    if ($active_link = $this
      ->getActiveLink($menu_name, $route_name, $route_parameters)) {
      if ($parents = $this->menuLinkManager
        ->getParentIds($active_link
          ->getPluginId())) {
        $active_trail = $parents + $active_trail;
      }
    }
    return $active_trail;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveLink($menu_name = NULL, $route_name = NULL, $route_parameters = NULL) {

    // Note: this is a very simple implementation. If you need more control
    // over the return value, such as matching a prioritized list of menu names,
    // you should substitute your own implementation for the 'menu.active_trail'
    // service in the container.
    // The menu links coming from the storage are already sorted by depth,
    // weight and ID.
    $found = NULL;
    if (is_null($route_name)) {
      $route_name = $this->routeMatch
        ->getRouteName();
    }

    // On a default (not custom) 403 page the route name is NULL. On a custom
    // 403 page we will get the route name for that page, so we can consider
    // it a feature that a relevant menu tree may be displayed.
    if ($route_name) {
      if (is_null($route_parameters)) {
        $route_parameters = $this->routeMatch
          ->getRawParameters()
          ->all();
      }

      // Load links matching this route.
      $links = $this->menuLinkManager
        ->loadLinksByRoute($route_name, $route_parameters, $menu_name);

      // Select the first matching link.
      if ($links) {
        $found = reset($links);
      }
    }

    return $found;
  }

}
