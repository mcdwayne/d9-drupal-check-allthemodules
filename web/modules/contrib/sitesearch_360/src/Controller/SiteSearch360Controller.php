<?php

namespace Drupal\sitesearch_360\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\search\Controller\SearchController;

/**
 * Controller for Site Search 360.
 */
class SiteSearch360Controller extends SearchController {

  /**
   * Retrieves search suggestions.
   *
   * @param string $query
   *   The search query.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The json encoded suggest items.
   */
  public function suggests($query, Request $request) {
    $plugin = sitesearch_360_get_page_plugin();
    $suggests = $plugin->getSuggests($query);

    return new JsonResponse($suggests);
  }

}
