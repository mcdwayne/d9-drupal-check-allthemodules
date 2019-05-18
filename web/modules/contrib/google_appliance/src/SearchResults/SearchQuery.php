<?php

namespace Drupal\google_appliance\SearchResults;

/**
 * Defines a value object for creating a search.
 */
class SearchQuery {

  const ORDER_DATE = 'date:D:S:d1';
  const ORDER_REL = 'rel';

  /**
   * Search string.
   *
   * @var string
   */
  protected $searchQuery;

  /**
   * Page number.
   *
   * @var int
   */
  protected $page;

  /**
   * Sort order.
   *
   * @var string
   */
  protected $sort;

  /**
   * Array of languages.
   *
   * @var array
   */
  protected $languages;

  /**
   * Constructs a new SearchQuery object.
   *
   * @param string $searchQuery
   *   Search terms.
   * @param string|null $sort
   *   Sort field. Pass 'date' for date search. Defaults to rel.
   * @param int $page
   *   Results page.
   * @param array $languages
   *   Array of language codes.
   */
  public function __construct($searchQuery, $sort = self::ORDER_REL, $page = 0, array $languages = []) {
    $this->searchQuery = $searchQuery;
    $this->sort = $sort;
    $this->page = $page;
    $this->languages = $languages;
  }

  /**
   * Gets value of searchQuery.
   *
   * @return string
   *   Value of searchQuery
   */
  public function getSearchQuery() {
    return $this->searchQuery;
  }

  /**
   * Gets value of page.
   *
   * @return int
   *   Value of page
   */
  public function getPage() {
    return $this->page;
  }

  /**
   * Gets value of sort.
   *
   * @return string
   *   Value of sort
   */
  public function getSort() {
    return $this->sort;
  }

  /**
   * Gets value of languages.
   *
   * @return array
   *   Value of languages
   */
  public function getLanguages() {
    // @todo port _google_appliance_get_lr here?
    return $this->languages;
  }

}
