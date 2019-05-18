<?php

namespace Drupal\scheduled_executable\Plugin\ScheduledExecutable\Resolver;

/**
 * Defines the interface for resolvers.
 */
interface ResolverInterface {

  /**
   * Resolve a group of scheduled executable items.
   *
   * @param \Drupal\scheduled_executable\Entity\ScheduledExecutable[] $items
   *   The array of scheduled_executable entities.
   *
   * @return
   *   The modified array of entities. These will be queued in the order of this
   *   array, and some may be removed from the array and the entities deleted
   *   entirely.
   */
  public function resolveScheduledItems(array $items);

}
