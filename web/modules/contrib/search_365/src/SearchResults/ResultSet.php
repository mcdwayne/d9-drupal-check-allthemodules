<?php

namespace Drupal\search_365\SearchResults;

/**
 * Defines a value object for a search response.
 */
class ResultSet {

  /**
   * Factory method for a ResultSet.
   *
   * @return self
   *   Static object.
   */
  public static function create() {
    return new static();
  }

  /**
   * Total results.
   *
   * @var int
   */
  protected $resultsCount = 0;

  /**
   * Results per page.
   *
   * @var int
   */
  protected $pageSize;

  /**
   * Page to start at.
   *
   * @var int
   */
  protected $pageNum;

  /**
   * Suggestions.
   *
   * @var array
   */
  protected $didYouMean = [];

  /**
   * Featured result.
   *
   * @var string
   */
  protected $featured;

  /**
   * Result objects.
   *
   * @var \Drupal\search_365\SearchResults\Result[]
   */
  protected $results = [];

  /**
   * Checks if there are Did You Mean results.
   *
   * @return bool
   *   TRUE if there are Did You Mean results.
   */
  public function hasDidYouMean(): bool {
    return (bool) count($this->didYouMean);
  }

  /**
   * Gets suggestions array.
   *
   * @return array
   *   Value of didYouMean.
   */
  public function getDidYouMean(): array {
    return $this->didYouMean;
  }

  /**
   * Gets page number to start with.
   *
   * @return int
   *   Value of page number.
   */
  public function getPageNum(): int {
    return $this->pageNum;
  }

  /**
   * Checks if there are results.
   *
   * @return bool
   *   TRUE if there are results.
   */
  public function hasResults(): bool {
    return (bool) $this->getResultsCount();
  }

  /**
   * Gets the first result index for the current page.
   *
   * Used for displaying results from - to.
   *
   * @return int
   *   The first result index for the current page.
   */
  public function getFirstResultIndex() {
    // Page numbers need to be set to index 0 instead of index 1.
    return ($this->getPageNum() - 1) * $this->getPageSize() + 1;
  }

  /**
   * Gets the last result index for the current page.
   *
   * @return int
   *   The last result index for the current page.
   */
  public function getLastResultIndex() {
    // Page numbers need to be set to index 0 instead of index 1.
    $remainingResults = $this->getResultsCount() - $this->getFirstResultIndex() + 1;
    if ($remainingResults <= $this->getPageSize()) {
      return ($this->getPageNum() - 1) * $this->getPageSize() + $remainingResults;
    }
    else {
      return $this->getFirstResultIndex() - 1 + $this->getPageSize();
    }
  }

  /**
   * Gets results array.
   *
   * @return \Drupal\search_365\SearchResults\Result[]
   *   Value of results.
   */
  public function getResults(): array {
    return $this->results;
  }

  /**
   * Gets result count.
   *
   * @return int
   *   Value of result count.
   */
  public function getResultsCount(): int {
    return $this->resultsCount;
  }

  /**
   * Sets the ResultsCount.
   *
   * @param int $resultsCount
   *   The ResultsCount.
   *
   * @return $this
   */
  public function setResultsCount(int $resultsCount): ResultSet {
    $this->resultsCount = $resultsCount;
    return $this;
  }

  /**
   * Sets the PageSize.
   *
   * @param int $pageSize
   *   The PageSize.
   *
   * @return $this
   */
  public function setPageSize(int $pageSize): ResultSet {
    $this->pageSize = $pageSize;
    return $this;
  }

  /**
   * Sets the PageNum.
   *
   * @param int $pageNum
   *   The PageNum.
   *
   * @return $this
   */
  public function setPageNum(int $pageNum): ResultSet {
    $this->pageNum = $pageNum;
    return $this;
  }

  /**
   * Adds a DidYouMean.
   *
   * @param string $didYouMean
   *   The DidYouMean.
   *
   * @return $this
   */
  public function addDidYouMean(string $didYouMean): ResultSet {
    $this->didYouMean[] = $didYouMean;
    return $this;
  }

  /**
   * Sets the Results.
   *
   * @param \Drupal\search_365\SearchResults\Result $results
   *   The Result.
   *
   * @return $this
   */
  public function addResult(Result $results): ResultSet {
    $this->results[] = $results;
    return $this;
  }

  /**
   * Sets the DidYouMean.
   *
   * @param array $didYouMean
   *   The DidYouMean.
   *
   * @return $this
   */
  public function setDidYouMean(array $didYouMean): ResultSet {
    $this->didYouMean = $didYouMean;
    return $this;
  }

  /**
   * Gets the PageSize.
   *
   * @return int
   *   The PageSize.
   */
  public function getPageSize(): int {
    return $this->pageSize;
  }

  /**
   * Checks if there are featured results.
   *
   * @return bool
   *   TRUE if there are featured results.
   */
  public function hasFeatured(): bool {
    return isset($this->featured);
  }

  /**
   * Gets the Featured.
   *
   * @return string
   *   The Featured.
   */
  public function getFeatured(): string {
    return $this->featured;
  }

  /**
   * Sets the Featured.
   *
   * @param string $featured
   *   The Featured.
   *
   * @return $this
   */
  public function setFeatured(string $featured): ResultSet {
    $this->featured = $featured;
    return $this;
  }

  /**
   * Sets the Results.
   *
   * @param \Drupal\search_365\SearchResults\Result[] $results
   *   The Results.
   *
   * @return $this
   */
  public function setResults(array $results): ResultSet {
    $this->results = $results;
    return $this;
  }

}
