<?php

namespace CleverReach\BusinessLogic\Utility\ArticleSearch\SearchItem;

use CleverReach\BusinessLogic\Utility\ArticleSearch\SerializableJson;

/**
 *
 */
class SearchableItems extends SerializableJson {
  /**
   * @var  SearchableItem[]*/
  private $searchableItems;

  /**
   * Adds new searchable item.
   *
   * @param SearchableItem $searchableItem
   */
  public function addSearchableItem(SearchableItem $searchableItem) {
    $this->searchableItems[] = $searchableItem;
  }

  /**
   * @return array
   */
  public function toArray() {
    $data = [];

    /** @var SearchableItem $searchableItem */
    foreach ($this->searchableItems as $searchableItem) {
      $data[] = $searchableItem->toArray();
    }

    return $data;
  }

}
