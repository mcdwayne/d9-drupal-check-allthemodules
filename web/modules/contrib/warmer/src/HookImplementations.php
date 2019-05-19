<?php

namespace Drupal\warmer;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\warmer\Plugin\WarmerPluginBase;

/**
 * Helper functions to use in hook implementations.
 */
class HookImplementations {

  /**
   * Helper function to implement hook_cron.
   */
  public static function enqueueWarmers() {
    /** @var \Drupal\warmer\Plugin\WarmerPluginManager $warmer_manager */
    $warmer_manager = \Drupal::service('plugin.manager.warmer');
    // Instantiate all the plugin managers.
    $warmer_definitions = $warmer_manager->getDefinitions();
    $warmers = array_map(function ($warmer_definition) use ($warmer_manager) {
      try {
        return $warmer_manager->createInstance($warmer_definition['id']);
      }
      catch (PluginException $exception) {
        return NULL;
      }
    }, $warmer_definitions);
    /** @var \Drupal\warmer\Plugin\WarmerPluginBase[] $warmers */
    $warmers = array_filter($warmers, function ($warmer) {
      return $warmer instanceof WarmerPluginBase;
    });
    $warmers = array_filter($warmers, function (WarmerPluginBase $warmer) {
      return $warmer->isActive();
    });
    $queue_manager = \Drupal::service('warmer.queue_manager');
    array_map(function (WarmerPluginBase $warmer) use ($queue_manager) {
      static::singleWarmer($warmer, $queue_manager);
    }, $warmers);
  }

  /**
   * Executes one warmer.
   *
   * @param \Drupal\warmer\Plugin\WarmerPluginBase $warmer
   *   The warmer plugin.
   * @param \Drupal\warmer\QueueManager $queue_manager
   *   The queue manager.
   */
  private static function singleWarmer(WarmerPluginBase $warmer, QueueManager $queue_manager) {
    $ids = [NULL];
    while ($ids = $warmer->buildIdsBatch(end($ids))) {
      $queue_manager->enqueueBatch(static::class . '::warmBatch', $ids, $warmer);
    }
  }

  /**
   * Warms one batch of items based on their IDs.
   *
   * @param mixed $ids
   *   The ID.
   * @param string $warmer_id
   *   The warmer plugin ID.
   *
   * @return int
   *   The number of successfully warmed items.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public static function warmBatch(array $ids, $warmer_id) {
    /** @var \Drupal\warmer\Plugin\WarmerPluginManager $warmer_manager */
    $warmer_manager = \Drupal::service('plugin.manager.warmer');
    /** @var \Drupal\warmer\Plugin\WarmerInterface $warmer */
    $warmer = $warmer_manager->createInstance($warmer_id);
    $items = $warmer->loadMultiple($ids);
    return $warmer->warmMultiple($items);
  }

}
