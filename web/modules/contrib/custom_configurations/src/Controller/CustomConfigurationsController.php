<?php

namespace Drupal\custom_configurations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\ProxyClass\Routing\RouteBuilder;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\custom_configurations\CustomConfigurationsManager;

/**
 * Class CustomConfigurationsController.
 *
 * @package Drupal\custom_configurations\Controller
 */
class CustomConfigurationsController extends ControllerBase {

  /**
   * Drupal\custom_configurations\CustomConfigurationsManager definition.
   *
   * @var \Drupal\custom_configurations\CustomConfigurationsManager
   */
  protected $customConfigurationsManager;

  /**
   * The route builder.
   *
   * @var \Drupal\Core\ProxyClass\Routing\RouteBuilder
   */
  protected $routerBuilder;

  /**
   * The route builder.
   *
   * @var \Drupal\Core\Routing\RouteProvider
   */
  protected $routerProvider;

  /**
   * {@inheritdoc}
   */
  public function __construct(CustomConfigurationsManager $custom_configurations_manager, RouteBuilder $router_builder, RouteProvider $router_provider) {
    $this->customConfigurationsManager = $custom_configurations_manager;
    $this->routerBuilder = $router_builder;
    $this->routerProvider = $router_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('custom_configurations.manager'),
      $container->get('router.builder'),
      $container->get('router.route_provider')
    );
  }

  /**
   * Returns index custom configurations plugin page.
   */
  public function getIndex() {
    $route_name = \Drupal::routeMatch()->getRouteName();
    $category_id = explode('.', $route_name)[1];

    $plugins = $this->customConfigurationsManager->getConfigPlugins();

    if ($category_id == 'index') {
      // Sort plugins by title.
      uasort($plugins, function ($a, $b) {
        if ($a['title'] == $b['title']) {
          return 0;
        }
        return ($a['title'] < $b['title']) ? -1 : 1;
      });
    }

    if (!empty($plugins)) {
      $content = [];

      foreach ($plugins as $plugin) {

        // If it's a category, skip plugins which are not related to it.
        if (
          $category_id != 'index' &&
          (empty($plugin['category_id']) || $plugin['category_id'] != $category_id)) {
          continue;
        }

        $route = 'custom_configurations.' . $plugin['id'] . '.form';
        if (empty($this->routerProvider->getRoutesByNames([$route]))) {
          $this->routerBuilder->rebuild();
        }
        $content[$plugin['id']] = [
          'title' => $plugin['title'],
          'description' => $plugin['description'] ?? '',
          'url' => Url::fromRoute($route, ['plugin_id' => $plugin['id']]),
        ];
      }
      $build = [
        '#theme' => 'admin_block_content',
        '#content' => $content,
      ];
    }
    else {
      $path = 'custom_configurations/src/Plugin/CustomConfigurations/ExampleConfigPlugin.php';
      $build = ['#markup' => $this->t('No active plugins found. To define your custom plugin you can use an example placed in %path', ['%path' => $path])];
    }

    return $build;
  }

}
