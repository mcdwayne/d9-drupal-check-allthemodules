<?php

namespace Drupal\timed_node_page\Service;

use Drupal\timed_node_page\Controller\TimedNodePageController;
use Drupal\timed_node_page\TimedNodePagePluginManager;
use Drupal\Core\Cache\Cache;
use Drupal\node\NodeInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Service for the timed node page plugins.
 *
 * @package Drupal\timed_node_page
 */
class TimedNodePage {

  protected $timedNodePageManager;

  /**
   * TimedNodePage constructor.
   *
   * @param \Drupal\timed_node_page\TimedNodePagePluginManager $timedNodePageManager
   *   The timed node page plugin manager.
   */
  public function __construct(TimedNodePagePluginManager $timedNodePageManager) {
    $this->timedNodePageManager = $timedNodePageManager;
  }

  /**
   * Gets the routes for each bundle timed node pages.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   The routes.
   */
  public function getRoutes() {
    $routes = new RouteCollection();

    foreach ($this->timedNodePageManager->getApplyingPerBundle() as $bundle => $definition) {
      $route = (new Route($definition['path']))
        ->setDefaults([
          '_controller' => TimedNodePageController::class . '::displayPage',
          '_title_callback' => TimedNodePageController::class . '::getPageTitle',
          'bundle' => $bundle,
          // We will be populating this later when we have found node for it.
          // But we have to declare it to be registrable on the route match.
          'node' => NULL,
        ])
        ->setRequirements([
          '_permission' => 'access content',
        ]);

      $routes->add('node.page.' . $bundle, $route);
    }

    return $routes;
  }

  /**
   * Attempts to clear cache of timed node page.
   *
   * Clears the cache of the current page of the bundle. This has to be done
   * because max age in certain cases has to be updated.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The saved node.
   */
  public function clearCacheFor(NodeInterface $node) {
    /** @var \Drupal\timed_node_page\TimedNodePagePluginInterface $timedPage */
    if (!($timedPage = \Drupal::service('plugin.manager.timed_node_page')->getBy($node->bundle()))) {
      return;
    }

    /** @var \Drupal\node\NodeInterface $original */
    if (!$node->isNew() && ($original = $node->original)) {
      $originalPublished = $original->isPublished();
      $entityPublished = $node->isPublished();

      // If original and current are both unpublished then it won't affect.
      if (!$originalPublished && !$entityPublished) {
        return;
      }

      // If both are published we can still check if the start or end date
      // values have changed and only then to clear the cache.
      if ($originalPublished && $entityPublished) {
        $startField = $timedPage->getStartFieldName();
        $startFieldChanged = $original->get($startField)->getString() != $node->get($startField)->getString();

        $endField = $timedPage->getEndFieldName();
        $endFieldChanged = $endField ? $original->get($endField)->getString() != $node->get($endField)->getString() : FALSE;

        if (!$startFieldChanged && !$endFieldChanged) {
          return;
        }
      }

    }
    // If the node is new and it is not published then it should not affect
    // the timed pages.
    elseif (!$node->isPublished()) {
      return;
    }

    Cache::invalidateTags($timedPage->getCacheTags());
  }

}
