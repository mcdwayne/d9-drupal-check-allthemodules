<?php

namespace Drupal\cacheexclude\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class CacheexcludeSubscriber.
 *
 * @package Drupal\cacheexclude.
 */
class CacheexcludeSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['disableCacheForPage'];
    return $events;
  }

  /**
   * Subscriber Callback for the event.
   */
  public function disableCacheForPage() {

    // Get cacheexclude page configuration.
    $config = \Drupal::config('cacheexclude.settings');
    $pages = trim($config->get('cacheexclude_list'));

    // If the current page is one we want to exclude from the cache,
    // disable page cache temporarily.
    if (!empty($pages)) {
      $current_path = \Drupal::service('path.current')->getPath();
      $current_path_alias = \Drupal::service('path.alias_manager')->getAliasByPath($current_path);
      $path_matches = \Drupal::service('path.matcher')->matchPath($current_path, $pages);
      $alias_path_matches = \Drupal::service('path.matcher')->matchPath($current_path_alias, $pages);

      if ($path_matches || $alias_path_matches) {
        // Disable page cache temporarily.
        \Drupal::service('page_cache_kill_switch')->trigger();
        return;
      }
    }

    // Check if current node type is one we want to exclude from the cache.
    if ($bundle = \Drupal::routeMatch()->getParameter('node')) {
      $bundle_type = $bundle->bundle();
    }
    $node_types = $config->get('cacheexclude_node_types');

    if (!empty($node_types)) {
      $types = array_filter($node_types);
    }

    if (isset($bundle_type) && isset($types)) {
      if (in_array($bundle_type, $types)) {
        // Disable page cache temporarily.
        \Drupal::service('page_cache_kill_switch')->trigger();
      }
    }
  }

}
