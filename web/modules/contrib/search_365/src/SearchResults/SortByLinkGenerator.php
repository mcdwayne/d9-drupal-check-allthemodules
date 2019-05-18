<?php

namespace Drupal\search_365\SearchResults;

use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\search_365\Routing\SearchViewRoute;

/**
 * Generates the sort by links.
 */
class SortByLinkGenerator {

  /**
   * Gets the order by links.
   *
   * @param \Drupal\search_365\SearchResults\SearchQuery $searchQuery
   *   The search query.
   *
   * @return array
   *   The order by links.
   */
  public static function getSortByLinks(SearchQuery $searchQuery) {
    $links = [];
    if ($searchQuery->getSortBy() == SearchQuery::SORT_BY_DATE) {
      $links[] = Link::fromTextAndUrl(new TranslatableMarkup('Relevance'), Url::fromRoute(SearchViewRoute::ROUTE_NAME, [
        'search_query' => $searchQuery->getQuery(),
      ])->setAbsolute()->setOption('query', [
        // Search 365 pager starts at 1.
        'page' => (string) $searchQuery->getPageNum() - 1,
        'sortby' => SearchQuery::SORT_BY_RELEVANCE,
      ]));
      $links[] = new TranslatableMarkup('Date');
      return $links;
    }
    $links[] = new TranslatableMarkup('Relevance');
    $links[] = Link::fromTextAndUrl(new TranslatableMarkup('Date'), Url::fromRoute(SearchViewRoute::ROUTE_NAME, [
      'search_query' => $searchQuery->getQuery(),
    ])->setAbsolute()->setOption('query', [
      // Search 365 pager starts at 1.
      'page' => (string) $searchQuery->getPageNum() - 1,
      'sortby' => SearchQuery::SORT_BY_DATE,
    ]));
    return $links;
  }

}
