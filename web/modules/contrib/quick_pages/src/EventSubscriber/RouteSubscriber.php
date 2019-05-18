<?php

/**
 * @file
 * Contains \Drupal\quick_pages\EventSubscriber\RouteSubscriber.
 */

namespace Drupal\quick_pages\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\quick_pages\QuickPageInterface;
use Drupal\views\Plugin\ViewsPluginManager;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Quick pages route subscriber.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * Quick page storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $quickPageStorage
   */
  protected $quickPageStorage;

  /**
   * The access manager.
   *
   * @var \Drupal\views\Plugin\ViewsPluginManager;
   */
  protected $accessManager;

  /**
   * Constructs route subscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\views\Plugin\ViewsPluginManager $access_manager
   *   Access manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, ViewsPluginManager $access_manager) {
    $this->quickPageStorage = $entity_manager->getStorage('quick_page');
    $this->accessManager = $access_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {

    /** @var \Drupal\quick_pages\QuickPageInterface[] $quick_pages */
    $quick_pages = $this->quickPageStorage->loadMultiple();
    $path_index = [];
    foreach ($quick_pages as $page) {
      if ($page->status()) {
        $path_index[$page->id()] = $page->get('path');
      }
    }

    // First update existing routes.
    foreach ($collection->all() as $route) {
      if ($page_id = array_search($route->getPath(), $path_index)) {
        $this->updateRoute($route, $quick_pages[$page_id]);
        unset($path_index[$page_id]);
      }
    }

    // Create new routes.
    foreach ($path_index as $page_id => $path) {
      $route = new Route($path);
      $this->updateRoute($route, $quick_pages[$page_id]);
      $collection->add('quick_pages.' . $page_id, $route);
    }

  }

  /**
   * Attaches quick page attributes to a given route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   Route to update.
   * @param \Drupal\quick_pages\QuickPageInterface $quick_page
   *   A quick page connected the route.
   */
  protected function updateRoute(Route $route, QuickPageInterface $quick_page) {
    $route->setOption('display_variant', $quick_page->get('display_variant'));
    $route->setOption('theme', $quick_page->get('theme'));

    if ($title = $quick_page->get('title')) {
      $route->setDefault('_title', $title);
    }

    $main_content_provider = $quick_page->get('main_content_provider');
    if ($main_content_provider['id']) {
      $route->setOption('main_content_provider', $quick_page->get('main_content_provider'));
      $route->setDefault('_controller', '\Drupal\quick_pages\Controller\QuickPagesController::build');
    }
    $access = $quick_page->get('access');
    if (isset($access['id'])) {
      $plugin_configuration = isset($access['configuration']) ?
        $access['configuration'] : [];
      $access_instance = $this->accessManager->createInstance($access['id']);
      $access_instance->options = $plugin_configuration;
      $access_instance->alterRouteDefinition($route);
    }
  }

}
