<?php

namespace Drupal\menu_badges;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteBuilderInterface;

class MenuBadgesManager {

  const LOCAL_TASK = 'task';
  const LOCAL_ACTION = 'action';
  
  protected $config;
  
  protected $routeBuilder;

  /**
   * Constructs a MenuBadgesManager object.
   *
   */
  public function __construct(ConfigFactory $config_factory, RouteBuilderInterface $route_builder) {
    $this->config = $config_factory->getEditable('menu_badges.settings');
    $this->routeBuilder = $route_builder;
  }

  public function getLocalRoutes($types = array(), $title = NULL, $path = NULL) {
    $routes = array();

    if (empty($types) || in_array(MenuBadgesManager::LOCAL_TASK, $types)) {
      $tabs = \Drupal::service('plugin.manager.menu.local_task')->getDefinitions();
      foreach ($tabs as $id => $info) {
        $info['menu_badges_route_type'] = MenuBadgesManager::LOCAL_TASK;
        $routes[$id] = $info;
      }
    }
    if (empty($types) || in_array(MenuBadgesManager::LOCAL_ACTION, $types)) {
      $actions = \Drupal::service('plugin.manager.menu.local_action')->getDefinitions();
      foreach ($actions as $id => $info) {
        $info['menu_badges_route_type'] = MenuBadgesManager::LOCAL_ACTION;
        $routes[$id] = $info;
      }
    }

    if (!empty($title)) {
      foreach ($routes as $id => $route) {
        if (empty($route['title']) || !stristr($route['title'], $title)) {
          unset($routes[$id]);
        }
      }
    }

    // Get path information and merge.
    $paths = db_select('router', 'r')
      ->fields('r', array('name', 'path'))
      ->execute()->fetchAllKeyed();
    foreach ($routes as $id => $route) {
      if (!empty($paths[$route['route_name']])) {
        $routes[$id]['menu_badges_route_path'] = $paths[$route['route_name']];
      }
    }

    if (!empty($path)) {
      foreach ($routes as $id => $route) {
        if (empty($route['menu_badges_route_path']) || !stristr($route['menu_badges_route_path'], $path)) {
          unset($routes[$id]);
        }
      }
    }

    return $routes;
  }

  public function getLocalBadgesForRoutes($routes = array()) {
    $local_badges = $this->config->get('local_badges');
    $badges = array(MenuBadgesManager::LOCAL_TASK => array(), MenuBadgesManager::LOCAL_ACTION => array());
    if (!empty($local_badges)) {
      foreach ($local_badges as $key_type => $key_badges) {
        foreach ($key_badges as $route_id => $info) {
          if (empty($routes) || in_array(str_replace('|', '.', $route_id), $routes)) {
            $badges[$key_type][$route_id] = $info;
          }
        }
      }
    }
    return $badges;
  }
  
  public function getLocalBadges() {
    return $this->config->get('local_badges');
  }
  
  public function setLocalBadges($local_badges) {
    $this->config->set('local_badges', $local_badges)
      ->save();
    \Drupal::service('plugin.manager.menu.local_task')->clearCachedDefinitions();
    \Drupal::service('plugin.manager.menu.local_action')->clearCachedDefinitions();
  }

}
