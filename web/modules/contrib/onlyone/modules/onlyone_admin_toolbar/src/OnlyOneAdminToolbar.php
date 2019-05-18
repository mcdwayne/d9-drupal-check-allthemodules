<?php

namespace Drupal\onlyone_admin_toolbar;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteBuilderInterface;

/**
 * Class OnlyOneAdminToolbar.
 */
class OnlyOneAdminToolbar implements OnlyOneAdminToolbarInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The route builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   The route builder.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RouteBuilderInterface $route_builder) {
    $this->configFactory = $config_factory;
    $this->routeBuilder = $route_builder;
  }

  /**
   * {@inheritdoc}
   */
  public function rebuildMenu($content_type) {
    // Getting the configured content types.
    $onlyone_content_types = $this->configFactory->get('onlyone.settings')->get('onlyone_node_types');
    // Checking if the content type is configured.
    if (in_array($content_type, $onlyone_content_types)) {
      // If is configured then we need to rebuild the menu.
      $this->routeBuilder->rebuild();
    }
  }

}
