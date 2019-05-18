<?php

namespace Drupal\healthz\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\healthz\HealthzCheckPluginCollection;
use Drupal\healthz\HealthzPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for the healthz route.
 */
class HealthzController implements ContainerInjectionInterface {

  /**
   * An array of checks.
   *
   * @var array
   */
  protected $checks;

  /**
   * The plugin collection.
   *
   * @var \Drupal\healthz\HealthzCheckPluginCollection
   */
  protected $healthzChecksCollection;

  /**
   * HealthzController constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\healthz\HealthzPluginManager $plugin_manager
   *   The plugin manager for healthz checks.
   */
  public function __construct(ConfigFactoryInterface $config_factory, HealthzPluginManager $plugin_manager) {
    $this->checks = $config_factory->get('healthz.settings')->get('checks');
    $this->healthzChecksCollection = new HealthzCheckPluginCollection($plugin_manager, $this->checks);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.healthz')
    );
  }

  /**
   * Builds the JSON response.
   */
  public function build() {
    $response = new JsonResponse();
    $data = [];
    foreach ($this->checks as $plugin_id => $check) {
      /** @var \Drupal\healthz\Plugin\HealthzCheckInterface $plugin */
      $plugin = $this->healthzChecksCollection->get($plugin_id);

      // Ignore if the plugin is disabled, or doesn't apply.
      if (!$plugin->getStatus() || !$plugin->applies()) {
        continue;
      }

      $data['checks'][] = $plugin_id;
      if (!$plugin->check()) {
        $data['errors'][$plugin_id] = $plugin->getErrors();
        $response->setStatusCode($plugin->getFailureStatusCode());
        break;
      }
    }
    $response->setData($data);

    return $response;
  }

}
