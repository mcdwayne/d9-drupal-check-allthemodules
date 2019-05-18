<?php

namespace Drupal\google_appliance\SearchResults;

use Drupal\google_appliance\Routing\SearchViewRoute;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Defines a value object for a search response.
 */
class ResultSet {

  const ERROR_PARSING = 'lib_xml_parse_error';
  const ERROR_NO_RESULTS = 'gsa_no_results';
  const ERROR_HTTP = 'http_error';

  /**
   * Display title.
   *
   * @var string
   */
  protected $searchTitle;

  /**
   * Response errors keyed by type.
   *
   * @var string[]
   */
  protected $errors = [];

  /**
   * Total results.
   *
   * @var int
   */
  protected $total = 0;

  /**
   * Result objects.
   *
   * @var \Drupal\google_appliance\SearchResults\Result[]
   */
  protected $results = [];

  /**
   * Spelling suggestions.
   *
   * @var string[]
   */
  protected $spellingSuggestions = [];

  /**
   * Key matches.
   *
   * @var \Drupal\google_appliance\SearchResults\KeyMatch[]
   */
  protected $keyMatches = [];

  /**
   * Synonyms.
   *
   * @var \Drupal\google_appliance\SearchResults\Synonym[]
   */
  protected $synonyms = [];

  /**
   * Last result.
   *
   * @var int
   */
  protected $lastResult = 0;

  /**
   * One box result keyed by module name.
   *
   * @var \Drupal\google_appliance\SearchResults\OneBoxResultSet[]
   */
  protected $oneBoxResultSets = [];

  /**
   * Original query object.
   *
   * @var \Drupal\google_appliance\SearchResults\SearchQuery
   */
  protected $query;

  /**
   * Results per page.
   *
   * @var int
   */
  protected $resultsPerPage;

  /**
   * Check if response is in error.
   *
   * @return bool
   *   TRUE if errors occurred.
   */
  public function hasErrors() {
    return (bool) array_filter($this->errors);
  }

  /**
   * Sets total.
   *
   * @param int $total
   *   Total.
   *
   * @return $this
   */
  public function setTotal($total) {
    $this->total = $total;
    return $this;
  }

  /**
   * Adds error.
   *
   * @param string $message
   *   Error.
   * @param string $type
   *   Error type.
   *
   * @return $this
   */
  public function addError($message, $type = self::ERROR_PARSING) {
    $this->errors[$type][] = $message;
    return $this;
  }

  /**
   * Adds spelling suggestion.
   *
   * @param string $string
   *   Suggestion.
   *
   * @return $this
   */
  public function addSpellingSuggestion($string) {
    $this->spellingSuggestions[] = $string;
    return $this;
  }

  /**
   * Adds key match.
   *
   * @param \Drupal\google_appliance\SearchResults\KeyMatch $match
   *   Match.
   *
   * @return $this
   */
  public function addKeyMatch(KeyMatch $match) {
    $this->keyMatches[] = $match;
    return $this;
  }

  /**
   * Adds synonym.
   *
   * @param \Drupal\google_appliance\SearchResults\Synonym $synonym
   *   Synonym.
   *
   * @return $this
   */
  public function addSynonym(Synonym $synonym) {
    $this->synonyms[] = $synonym;
    return $this;
  }

  /**
   * Adds a result.
   *
   * @param \Drupal\google_appliance\SearchResults\Result $result
   *   Result.
   *
   * @return $this
   */
  public function addResult(Result $result) {
    $this->results[] = $result;
    return $this;
  }

  /**
   * Gets value of errors.
   *
   * @return array
   *   Value of errors
   */
  public function getErrors() {
    return $this->errors;
  }

  /**
   * Gets value of total.
   *
   * @return int
   *   Value of total
   */
  public function getTotal() {
    return $this->total;
  }

  /**
   * Gets value of results.
   *
   * @return \Drupal\google_appliance\SearchResults\Result[]
   *   Value of results
   */
  public function getResults() {
    return $this->results;
  }

  /**
   * Gets value of spellingSuggestions.
   *
   * @return array
   *   Value of spellingSuggestions
   */
  public function getSpellingSuggestions() {
    return $this->spellingSuggestions;
  }

  /**
   * Gets value of keyMatches.
   *
   * @return array
   *   Value of keyMatches
   */
  public function getKeyMatch() {
    return $this->keyMatches;
  }

  /**
   * Gets value of synonyms.
   *
   * @return array
   *   Value of synonyms
   */
  public function getSynonyms() {
    return $this->synonyms;
  }

  /**
   * Gets value of lastResult.
   *
   * @return string
   *   Value of lastResult
   */
  public function getLastResult() {
    return $this->lastResult;
  }

  /**
   * Sets last result.
   *
   * @param string $lastResult
   *   New value for last result.
   *
   * @return $this
   */
  public function setLastResult($lastResult) {
    $this->lastResult = $lastResult;
    return $this;
  }

  /**
   * Adds one box result set.
   *
   * @param string $module_name
   *   One box module.
   * @param \Drupal\google_appliance\SearchResults\OneBoxResultSet $onebox
   *   Results set.
   *
   * @return $this
   */
  public function addOneBoxResultSet($module_name, OneBoxResultSet $onebox) {
    $this->oneBoxResultSets[$module_name] = $onebox;
    return $this;
  }

  /**
   * Check if there are results.
   *
   * @return bool
   *   TRUE if there are results.
   */
  public function hasResults() {
    return (bool) count($this->results);
  }

  /**
   * Check if there are synonyms.
   *
   * @return bool
   *   TRUE if synonyms exist.
   */
  public function hasSynonyms() {
    return (bool) count($this->synonyms);
  }

  /**
   * Check if there are key matches.
   *
   * @return bool
   *   TRUE if key matches exist.
   */
  public function hasKeyMatch() {
    return (bool) count($this->keyMatches);
  }

  /**
   * Check if there are suggestions.
   *
   * @return bool
   *   TRUE if suggestions exist.
   */
  public function hasSpellingSuggestions() {
    return (bool) count($this->spellingSuggestions);
  }

  /**
   * Gets value of searchTitle.
   *
   * @return string
   *   Value of searchTitle
   */
  public function getSearchTitle() {
    return $this->searchTitle;
  }

  /**
   * Sets searchTitle.
   *
   * @param string $searchTitle
   *   New value for searchTitle.
   *
   * @return $this
   */
  public function setSearchTitle($searchTitle) {
    $this->searchTitle = $searchTitle;
    return $this;
  }

  /**
   * Gets value of query.
   *
   * @return \Drupal\google_appliance\SearchResults\SearchQuery
   *   Value of query
   */
  public function getQuery() {
    return $this->query;
  }

  /**
   * Sets query.
   *
   * @param \Drupal\google_appliance\SearchResults\SearchQuery $query
   *   New value for query.
   *
   * @return $this
   */
  public function setQuery($query) {
    $this->query = $query;
    return $this;
  }

  /**
   * Gets statistics information for search result.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Statistics information.
   */
  public function getStatistics() {
    return new TranslatableMarkup('Showing results @first - @last for %query', [
      '@first' => (int) $this->lastResult - count($this->results) + 1,
      '@last' => $this->lastResult,
      '%query' => $this->query->getSearchQuery(),
    ]);
  }

  /**
   * Returns an array of sort links.
   *
   * @return array
   *   Sort links.
   */
  public function getSortLinks() {
    $links = [];
    if ($this->query->getSort() == SearchQuery::ORDER_DATE) {
      $links[] = Link::fromTextAndUrl(new TranslatableMarkup('Relevance'), Url::fromRoute(SearchViewRoute::ROUTE_NAME, [
        'search_query' => $this->query->getSearchQuery(),
        'result_sort' => 'rel',
      ])->setAbsolute()->setOption('query', [
        'page' => (string) $this->query->getPage(),
      ]));
      $links[] = new TranslatableMarkup('Date');
      return $links;
    }
    $links[] = new TranslatableMarkup('Relevance');
    $links[] = Link::fromTextAndUrl(new TranslatableMarkup('Date'), Url::fromRoute(SearchViewRoute::ROUTE_NAME, [
      'search_query' => $this->query->getSearchQuery(),
      'result_sort' => 'date',
    ])->setAbsolute()->setOption('query', [
      'page' => (string) $this->query->getPage(),
    ]));
    return $links;
  }

  /**
   * Returns render array for pager.
   *
   * @return array
   *   Render array.
   */
  public function getPager() {
    // Globals required to manually configure the pager.
    global $pager_page_array, $pager_total, $pager_total_items;

    $element = 0;
    /**
     * NOTE: the total results count from the GSA is unreliable. The docs
     * state that it is an *approximation*, but if you click around on
     * enough searches, you'll find that it's off by a considerable amount -
     * enough to break the math used to create pager links. It's not all that
     * noticeable unless you click on the "last" link in the pager, and
     * notice that in some searches, the last page is paginated at a number
     * less than was previously viewable when the first page of results came
     * up on the initial search view.
     *
     * The problem is rooted in public vs. access-controlled indexing.
     * @see groups.google.com/group/Google-Search-Appliance-Help/browse_thread/thread/019b77fb3e7950c7
     * Access-controlled results are counted before we know if we can actually
     * view it. Device configuration can help.
     *
     * @TODO: perhaps a better solution is to just query the device for the
     * first X (up to 1000) results and cache them locally.
     */

    // Manually configure (fake) the pager.
    $page = $this->query->getPage();

    // Convert page id to array.
    $pager_page_array = explode(',', $page);

    // Set the total results.
    $pager_total_items[$element] = (int) $this->total;

    // Set the total # of pages.
    $pager_total[$element] = ceil($pager_total_items[$element] / $this->resultsPerPage);
    $pager_page_array[$element] = max(0, min((int) $pager_page_array[$element], ((int) $pager_total[$element]) - 1));

    return ['#type' => 'pager'];
  }

  /**
   * Sets results per page.
   *
   * @param int $resultsPerPage
   *   New value for resultsPerPage.
   *
   * @return $this
   */
  public function setResultsPerPage($resultsPerPage) {
    $this->resultsPerPage = $resultsPerPage;
    return $this;
  }

}
