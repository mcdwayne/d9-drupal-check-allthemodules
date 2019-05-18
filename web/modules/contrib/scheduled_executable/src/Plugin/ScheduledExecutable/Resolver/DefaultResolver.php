<?php

namespace Drupal\scheduled_executable\Plugin\ScheduledExecutable\Resolver;

/**
 * Provides basic resolver which queues items in order of their creation.
 *
 * @ScheduledExecutableResolver(
 *   id = "default",
 *   label = @Translation("Default"),
 * )
 */
class DefaultResolver extends ResolverBase {

  /**
   * {@inheritdoc}
   */
  public function resolveScheduledItems(array $items) {
    // Sort the items by their created date.
    uasort($items, function($a, $b) {
      return $a->created->value <=> $b->created->value;
    });

    return $items;
  }

}
