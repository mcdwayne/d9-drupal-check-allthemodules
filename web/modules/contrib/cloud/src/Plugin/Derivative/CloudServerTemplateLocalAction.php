<?php

namespace Drupal\cloud\Plugin\Derivative;

use Drupal\cloud\Plugin\CloudServerTemplatePluginManagerInterface;
use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides plugin definitions for local action.
 */
class CloudServerTemplateLocalAction extends LocalActionDefault {

  /**
   * The ServerTemplatePluginManager.
   *
   * @var \Drupal\cloud\Plugin\CloudServerTemplatePluginManager
   */
  protected $serverTemplatePluginManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteProviderInterface $route_provider, CloudServerTemplatePluginManagerInterface $server_template_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $route_provider);

    $this->routeProvider = $route_provider;
    $this->serverTemplatePluginManager = $server_template_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('router.route_provider'),
      $container->get('plugin.manager.cloud_server_template_plugin')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(RouteMatchInterface $route_match) {
    $parameters = parent::getRouteParameters($route_match);
    $cloud_context = $route_match->getParameter('cloud_context');
    $plugin = $this->serverTemplatePluginManager->loadPluginVariant($cloud_context);

    if ($plugin != FALSE) {
      $parameters['cloud_server_template_type'] = $plugin->getEntityBundleName();
    }
    $parameters['cloud_context'] = $cloud_context;
    return $parameters;
  }

}
