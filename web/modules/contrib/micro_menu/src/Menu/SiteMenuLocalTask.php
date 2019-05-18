<?php

namespace Drupal\micro_menu\Menu;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Menu\LocalTaskInterface;
use Drupal\Core\Menu\LocalTaskDefault;

/**
 * Custom object used for Site Menu LocalTask Plugins.
 */
class SiteMenuLocalTask extends LocalTaskDefault {

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(RouteMatchInterface $route_match) {
    $parameters = parent::getRouteParameters($route_match);

    if (isset($parameters['menu']) && $parameters['menu'] == 'site-menu') {
      $parameters['menu'] = 'site-' . $parameters['site'];
    }

    return $parameters;
  }

}
