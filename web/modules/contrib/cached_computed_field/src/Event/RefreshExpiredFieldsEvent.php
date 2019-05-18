<?php

namespace Drupal\cached_computed_field\Event;

use Drupal\cached_computed_field\ExpiredItemCollectionInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * An event that fires when cached computed fields expire.
 */
class RefreshExpiredFieldsEvent extends Event implements RefreshExpiredFieldsEventInterface {

  /**
   * A collection of expired items.
   *
   * @var \Drupal\cached_computed_field\ExpiredItemCollectionInterface
   */
  protected $expiredItems;

  /**
   * Constructs a new RefreshExpiredFieldsEvent.
   *
   * @param \Drupal\cached_computed_field\ExpiredItemCollectionInterface $expiredItems
   *   The collection of expired items.
   */
  public function __construct(ExpiredItemCollectionInterface $expiredItems) {
    $this->expiredItems = $expiredItems;
  }

  /**
   * {@inheritdoc}
   */
  public function getExpiredItems() {
    return $this->expiredItems;
  }

}
