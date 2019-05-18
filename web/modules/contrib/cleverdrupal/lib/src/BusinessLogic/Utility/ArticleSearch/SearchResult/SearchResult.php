<?php

namespace CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult;

use CleverReach\BusinessLogic\Utility\ArticleSearch\SerializableJson;

/**
 * Class SearchResult.
 *
 * @package CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult
 */
class SearchResult extends SerializableJson {
  /**
   * @var  SearchResultItem[]*/
  private $searchResultItems;

  /**
   * @return SearchResultItem[]
   */
  public function getSearchResultItems() {
    return $this->searchResultItems;
  }

  /**
   * Adds new searchable item.
   *
   * @param SearchResultItem $searchResultItem
   */
  public function addSearchResultItem(SearchResultItem $searchResultItem) {
    $this->searchResultItems[] = $searchResultItem;
  }

  /**
   * @return array
   */
  public function toArray() {
    $data = [];

    if (is_array($this->searchResultItems)) {
      /** @var SearchResultItem $searchableItem */
      foreach ($this->searchResultItems as $searchResultItem) {
        $data[] = $searchResultItem->toArray();
      }
    }

    return $data;
  }

}
