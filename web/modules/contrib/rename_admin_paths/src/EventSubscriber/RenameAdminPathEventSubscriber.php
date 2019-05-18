<?php

namespace Drupal\rename_admin_paths\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RenameAdminPathEventSubscriber implements EventSubscriberInterface {

  /**
   * Default list of admin paths.
   *
   * @var array
   */
  const DEFAULT_ADMIN_PATHS = ['admin', 'user'];

  /**
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('rename_admin_paths.settings');
  }

  /**
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   */
  public function onRoutesAlter(RouteBuildEvent $event) {
    foreach (self::DEFAULT_ADMIN_PATHS as $pathName) {
      if ($this->config->get(sprintf('%s_path', $pathName))) {
        $this->alterRouteCollection($event->getRouteCollection(), $pathName, $this->config->get(sprintf('%s_path_value', $pathName)));
      }
    }
  }

  /**
   * @param \Symfony\Component\Routing\RouteCollection $routeCollection
   * @param string $from
   * @param string $to
   */
  private function alterRouteCollection(RouteCollection $routeCollection, $from, $to) {
    foreach ($routeCollection as $route) {
      $this->replaceRoutePath($route, $from, $to);
    }
  }

  /**
   * @param \Symfony\Component\Routing\Route $route
   * @param string $from
   * @param string $to
   */
  private function replaceRoutePath(Route $route, $from, $to) {
    if ($this->matchRouteByPath($route, $from)) {
      $route->setPath(preg_replace(sprintf('~^/%s~', $from), sprintf('/%s', $to), $route->getPath(), 1));
    }
  }

  /**
   * @param \Symfony\Component\Routing\Route $route
   * @param string $path
   *
   * @return boolean
   *
   * match /path, /path/ and /path/* but not /path*
   */
  private function matchRouteByPath(Route $route, $path) {
    return (bool) preg_match(sprintf('~^/%s(?:/(.*))?$~', $path), $route->getPath());
  }

  /**
   * Use a very low priority so we are sure all routes are correctly marked
   * as admin route which is mostly done in other event subscribers the AdminRouteSubscriber
   *
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      RoutingEvents::ALTER => [
        ['onRoutesAlter', -2048],
      ],
    ];
  }
}
