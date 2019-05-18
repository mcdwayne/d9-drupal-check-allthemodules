<?php

namespace Drupal\micro_taxonomy\LocalTask;

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
class SiteTaxonomyLocalTask extends LocalTaskDefault {

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(RouteMatchInterface $route_match) {
    $parameters = parent::getRouteParameters($route_match);

    if (isset($parameters['taxonomy_vocabulary']) && $parameters['taxonomy_vocabulary'] == 'fake_site_vocabulary') {
      $parameters['taxonomy_vocabulary'] = 'site_' . $parameters['site'];
    }

    return $parameters;
  }

}
