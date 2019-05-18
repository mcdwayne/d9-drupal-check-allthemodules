<?php

namespace Drupal\health_check_url\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Route service class.
 */
class RouteService {

  /**
   * The stream wrapper manager service.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   *   ImmutableConfig.
   */
  protected $settings;

  /**
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->settings = $config_factory->get('health_check_url.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $route_collection = new RouteCollection();

    $endpoint = !empty($this->settings->get('endpoint')) ? trim($this->settings->get('endpoint'), '/') : 'health';
    $maintainence_access = $this->settings->get('maintainence_access');
    $route = new Route(
      '/' . $endpoint,
      [
        '_controller' => '\Drupal\health_check_url\Controller\HealthCheckController::healthCheckUrl',
        '_title' => 'Health Check URL',
        '_disable_route_normalizer' => 'TRUE',
      ],
      [
        '_access'  => 'TRUE',
      ],
      [
        'no_cache'  => 'TRUE',
        '_maintenance_access' => $maintainence_access === TRUE ? TRUE : FALSE,
      ]
    );

    $route_collection->add('health_check_url.content', $route);

    $route = new Route(
        '/admin/config/development/health',
        [
          '_form' => '\Drupal\health_check_url\Form\HealthCheckSettingsForm',
          '_title' => 'Health Check URL settings',
        ],
        [
          '_permission'  => 'health_check_url administration',
        ]
      );
    $route_collection->add('health_check_url.admin', $route);
    return $route_collection;
  }

}
