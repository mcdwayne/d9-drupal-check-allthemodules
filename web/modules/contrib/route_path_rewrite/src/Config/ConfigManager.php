<?php

namespace Drupal\route_path_rewrite\Config;

use Drupal\Core\Config\ConfigFactory;

/**
 * Manages module configuration.
 *
 * @package Drupal\route_path_rewrite\Configuration
 */
class ConfigManager {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * ConfigManager constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The config factory service.
   */
  public function __construct(ConfigFactory $configFactory) {
    $this->config = $configFactory->get('route_path_rewrite.settings');
  }

  /**
   * Gets the information of the routes that should be rewritten.
   *
   * @return array[\Drupal\route_path_rewrite\Config\RouteRewriteConfig]
   *   Route rewrite configurations.
   */
  public function getRoutesToRewrite() {
    $routesToRewrite = [];
    $routesToRewriteConfigData = $this->config->get('routes_to_rewrite');

    /* Do not create array of RouteRewriteConfig objects if no routes are configured. */
    if (is_array($routesToRewriteConfigData)) {
      foreach ($routesToRewriteConfigData as $routeToRewriteConfigData) {
        $routeRewriteConfig = new RouteRewriteConfig($routeToRewriteConfigData['name'], $routeToRewriteConfigData['new_path']);
        $routesToRewrite[] = $routeRewriteConfig;
      }
    }

    return $routesToRewrite;
  }

}
