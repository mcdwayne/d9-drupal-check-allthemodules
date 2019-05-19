<?php

/**
 * @file
 * Contains \Drupal\book\Cache\BookNavigationCacheContext.
 */

namespace Drupal\subsite\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\subsite\SubsiteManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the book navigation cache context service.
 *
 * Cache context ID: 'route.book_navigation'.
 *
 * This allows for book navigation location-aware caching. It depends on:
 * - whether the current route represents a book node at all
 * - and if so, where in the book hierarchy we are
 *
 * This class is container-aware to avoid initializing the 'book.manager'
 * service when it is not necessary.
 */
class SubsiteCacheContext implements CacheContextInterface, ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new BookNavigationCacheContext service.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t("Subsite");
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    // Find the current book's ID.
    $current_bid = 0;
    if ($node = $this->requestStack->getCurrentRequest()->get('node')) {
      /** @var SubsiteManager $subsite_manager */
      $subsite_manager = \Drupal::service('subsite.manager');

      if ($subsite_node = $subsite_manager->getSubsiteNode($node)) {
        return 'subsite.' . $subsite_node->id();
      }
      else {
        return 'subsite.none';
      }
    }

    return 'subsite.none';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    // The book active trail depends on the node and data attached to it.
    // That information is however not stored as part of the node.
    $cacheable_metadata = new CacheableMetadata();
    if ($node = $this->requestStack->getCurrentRequest()->get('node')) {
      // If the node is part of a book then we can use the cache tag for that
      // book. If not, then it can't be optimized away.
      /** @var SubsiteManager $subsite_manager */
      $subsite_manager = \Drupal::service('subsite.manager');

      if ($subsite_node = $subsite_manager->getSubsiteNode($node)) {
        $cacheable_metadata->addCacheTags(['subsite:' . $subsite_node->id()]);
      }
      else {
        $cacheable_metadata->setCacheMaxAge(0);
      }
    }
    return $cacheable_metadata;
  }

}
