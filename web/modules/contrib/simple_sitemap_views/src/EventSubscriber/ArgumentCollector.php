<?php

/**
 * @file
 * Contains argument collector.
 */

namespace Drupal\simple_sitemap_views\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Drupal\simple_sitemap_views\SimpleSitemapViews;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Collect information about views URLs.
 */
class ArgumentCollector implements EventSubscriberInterface {

  /**
   * Views sitemap data.
   *
   * @var \Drupal\simple_sitemap_views\SimpleSitemapViews
   */
  protected $simpleSitemapViews;

  /**
   * View entities storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $viewStorage;

  /**
   * The currently active route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * ArgumentCollector constructor.
   *
   * @param \Drupal\simple_sitemap_views\SimpleSitemapViews $simple_sitemap_views
   *   Views sitemap data.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The currently active route match object.
   */
  public function __construct(SimpleSitemapViews $simple_sitemap_views, EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $route_match) {
    $this->simpleSitemapViews = $simple_sitemap_views;
    $this->viewStorage = $entity_type_manager->getStorage('view');
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::TERMINATE] = 'onTerminate';
    return $events;
  }

  /**
   * Collect information about views URLs.
   *
   * @param \Symfony\Component\HttpKernel\Event\PostResponseEvent $event
   *   Object of event after a response was sent.
   */
  public function onTerminate(PostResponseEvent $event) {
    // Only successful requests are interesting.
    if ($event->getResponse()->isSuccessful()) {
      // Get view ID from route.
      $view_id = $this->routeMatch->getParameter('view_id');
      /** @var \Drupal\views\ViewEntityInterface $view_entity */
      if ($view_id && $view_entity = $this->viewStorage->load($view_id)) {
        // Get display ID from route.
        $display_id = $this->routeMatch->getParameter('display_id');
        // Get a set of view arguments and try to add them to the index.
        $view = $view_entity->getExecutable();
        $args = $this->getViewArgumentsFromRoute();
        $this->simpleSitemapViews->addArgumentsToIndex($view, $args, $display_id);
        // Destroy a view instance.
        $view->destroy();
      }
    }
  }

  /**
   * Get view arguments from current route.
   *
   * @return array
   *   View arguments array.
   */
  protected function getViewArgumentsFromRoute() {
    // The code of this function is taken in part from the view page controller
    // method (Drupal\views\Routing\ViewPageController::handle()).
    $route = $this->routeMatch->getRouteObject();
    $map = $route->hasOption('_view_argument_map') ? $route->getOption('_view_argument_map') : [];

    $args = [];
    foreach ($map as $attribute => $parameter_name) {
      $parameter_name = isset($parameter_name) ? $parameter_name : $attribute;
      if (($arg = $this->routeMatch->getRawParameter($parameter_name)) !== NULL) {
        $args[] = $arg;
      }
    }
    return $args;
  }

}
