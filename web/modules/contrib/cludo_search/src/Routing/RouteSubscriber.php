<?php

namespace Drupal\cludo_search\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The config factory interface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory interface.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Get configured search path.
    $config = $this->configFactory->get('cludo_search.settings');
    $path = $config->get('search_page');
    if (empty($path)) {
      $path = constant('CLUDO_SEARCH_DEFAULT_SEARCH_PAGE');
    }

    // Change path '/csearch' to path set in admin form.
    if (!empty($path) && $route = $collection->get('cludo_search.search')) {
      $route->setPath('/' . $path);
    }
  }

}
