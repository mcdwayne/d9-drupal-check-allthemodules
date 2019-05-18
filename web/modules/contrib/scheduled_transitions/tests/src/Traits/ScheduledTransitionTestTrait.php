<?php

declare(strict_types = 1);

namespace Drupal\Tests\scheduled_transitions\Traits;

/**
 * Test trait helpers.
 */
trait ScheduledTransitionTestTrait {

  /**
   * Enable bundles for use with scheduled transitions.
   *
   * @param array $bundles
   *   Arrays of bundles. Where each bundle is an array containing:
   *    - 0: Entity type ID.
   *    - 1: Bundle ID.
   */
  protected function enabledBundles(array $bundles): void {
    $enabledBundles = [];
    foreach ($bundles as $bundle) {
      $enabledBundles[] = [
        'entity_type' => $bundle[0],
        'bundle' => $bundle[1],
      ];
    }
    \Drupal::configFactory()->getEditable('scheduled_transitions.settings')
      ->set('bundles', $enabledBundles)
      ->save(TRUE);
  }

}
