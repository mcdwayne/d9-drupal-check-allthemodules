<?php

namespace Drupal\search_365\SearchResults;

/**
 * Defines a value object for creating a search.
 */
class SearchQuery {

  /**
   * Sort by date.
   */
  const SORT_BY_DATE = 'date';

  /**
   * Sort by relevance.
   */
  const SORT_BY_RELEVANCE = 'relevance';

  /**
   * Search query.
   *
   * @var string
   */
  protected $query;

  /**
   * Number of results.
   *
   * @var int
   */
  protected $size;

  /**
   * Page number.
   *
   * @var int
   */
  protected $pageNum;

  /**
   * The order by parameter.
   *
   * @var string
   */
  protected $sortBy;

  /**
   * Constructs a new SearchQuery object.
   *
   * @param string $query
   *   Search terms.
   * @param int $pageNum
   *   Results page.
   * @param int $size
   *   Number of results.
   * @param string $sortBy
   *   The sort order. One of 'date' or 'relevance'.
   */
  public function __construct($query, $pageNum = 1, $size = 10, $sortBy = NULL) {
    $this->query = $query;
    $this->pageNum = $pageNum;
    $this->size = $size;
    $this->sortBy = $sortBy;
  }

  /**
   * Gets value of searchQuery.
   *
   * @return string
   *   Value of searchQuery
   */
  public function getQuery() {
    return $this->query;
  }

  /**
   * Gets value of page.
   *
   * @return int
   *   Value of page
   */
  public function getPageNum() {
    return $this->pageNum;
  }

  /**
   * Gets number of results.
   *
   * @return int
   *   Returns number of results.
   */
  public function getSize(): int {
    return $this->size;
  }

  /**
   * Gets the sort by param.
   *
   * @return null|string
   *   The sort parameter, or NULL if none.
   */
  public function getSortBy() {
    return $this->sortBy;
  }

}
