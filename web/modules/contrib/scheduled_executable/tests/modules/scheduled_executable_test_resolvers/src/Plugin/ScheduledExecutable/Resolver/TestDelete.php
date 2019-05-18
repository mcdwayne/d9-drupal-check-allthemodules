<?php

namespace Drupal\scheduled_executable_test_resolvers\Plugin\ScheduledExecutable\Resolver;

use Drupal\scheduled_executable\Plugin\ScheduledExecutable\Resolver\ResolverInterface;

/**
 * @ScheduledExecutableResolver(
 *   id = "test_delete",
 *   label = @Translation("Resolver which deletes items"),
 * )
 */
class TestDelete implements ResolverInterface {

  /**
   * {@inheritdoc}
   */
  public function resolveScheduledItems(array $items) {
    // Delete an item if its key is 'delete'.
    $return = [];
    foreach ($items as $item) {
      if ($item->getKey() == 'delete') {
        $item->delete();
      }
      else {
        $return[] = $item;
      }
    }

    return $return;
  }

}
