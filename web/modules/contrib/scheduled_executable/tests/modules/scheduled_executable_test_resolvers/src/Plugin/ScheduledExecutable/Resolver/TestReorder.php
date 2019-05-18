<?php

namespace Drupal\scheduled_executable_test_resolvers\Plugin\ScheduledExecutable\Resolver;

use Drupal\scheduled_executable\Plugin\ScheduledExecutable\Resolver\ResolverInterface;

/**
 * @ScheduledExecutableResolver(
 *   id = "scheduled_executable_test_resolvers_test_reorder",
 *   label = @Translation("TODO: replace this with a value"),
 * )
 */
class TestReorder implements ResolverInterface {

  /**
   * {@inheritdoc}
   */
  public function resolveScheduledItems(array $items) {
    // Resolve a group of scheduled executable items.
  }

}
