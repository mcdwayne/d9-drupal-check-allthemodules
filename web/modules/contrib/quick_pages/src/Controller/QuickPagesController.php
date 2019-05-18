<?php

/**
 * @file
 * Contains \Drupal\quick_pages\Controller\QuickPagesController.
 */

namespace Drupal\quick_pages\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\quick_pages\MainContentPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


/**
 * Returns responses for Quick pages routes.
 */
class QuickPagesController extends ControllerBase {

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The variant manager.
   *
   * @var \Drupal\quick_pages\MainContentPluginManager;
   */
  protected $mainContentManager;

  /**
   * Constructs the controller.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\quick_pages\MainContentPluginManager $main_content_manager
   *   The main content manager.
   */
  public function __construct(RouteMatchInterface $route_match, MainContentPluginManager $main_content_manager) {
    $this->routeMatch = $route_match;
    $this->mainContentManager = $main_content_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('plugin.manager.main_content_provider')
    );
  }

  /**
   * Builds main content.
   */
  public function build() {

    $main_content_provider = $this->routeMatch
      ->getRouteObject()
      ->getOption('main_content_provider');

    $plugin_instance = $this->mainContentManager->createInstance(
      $main_content_provider['id'],
      isset($main_content_provider['configuration']) ? $main_content_provider['configuration'] : []
    );

    $main_content = $plugin_instance->getMainContent();

    if (!$main_content) {
      throw new NotFoundHttpException();
    }

    // @todo: Figure out why renderer throws an exception if cache keys are
    // specified.
    unset($main_content['#cache']['keys']);

    return $main_content;
  }

}
