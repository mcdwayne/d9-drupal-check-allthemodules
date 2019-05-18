<?php

namespace Drupal\flexible_google_cse\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class FlexibleSearchController.
 */
class FlexibleSearchController extends ControllerBase {

  /**
   * Search.
   *
   * @return string
   *   Return SearchResult
   */
  public function fgcsSearch() {
    $searchService = \Drupal::service('flexible_google_cse_search');
    $searchResult = $searchService->search();

    return $searchResult;
  }

}
