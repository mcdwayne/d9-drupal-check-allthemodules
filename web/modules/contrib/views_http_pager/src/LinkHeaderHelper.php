<?php

namespace Drupal\views_http_pager;

use Drupal\views\ViewExecutable;
use Drupal\Core\Url;
use Drupal\Core\Cache\CacheableResponse;

/**
 * Facilitate extracting pager URLs from REST views and add them as HTTP Links.
 */
class LinkHeaderHelper {

  /**
   * Add Link headers to the designated response.
   *
   * @param \Drupal\Core\Cache\CacheableResponse $response
   *   The response object.
   * @param array $links
   *   Array of URIs keyed on link relation.
   */
  public static function addLinkHeaders(CacheableResponse $response, array $links = []) {
    foreach ($links as $rel => $uri) {
      $uri = $uri->toString(TRUE);
      $response->headers->set('Link', '<' . $uri->getGeneratedUrl() . '>; rel="' . $rel . '"', FALSE);
      $response->addCacheableDependency($uri);
    }
  }

  /**
   * Generate an array of pager link data.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view object from which we extract pager data.
   *
   * @return array
   *   Array of URIs keyed on link relation.
   *
   * @see https://www.drupal.org/node/2100637
   */
  public static function getPagerLinksFromView(ViewExecutable $view) {
    $links = [];
    $pager = $view->getPager();

    $display = $view->getDisplay();
    // Build full view-display id.
    $view_id = $view->storage->id();
    $display_id = $display->display['id'];

    // Route as defined in e.g. \Drupal\rest\Plugin\views\display\RestExport.
    $route_names = \Drupal::state()->get('views.view_route_names');
    $route_name = $route_names["$view_id.$display_id"];

    // Get base url path for the view; getUrl returns a path not an absolute
    // URL (and no page information).
    /** @var \Drupal\Core\Url $view_base_url */
    $view_base_url = $view->getUrl();

    // Inject the page into the canonical URI of the view.
    if ($view->getCurrentPage() > 0) {
      $links['self'] = Url::fromRoute($route_name, $view_base_url->getRouteParameters(), [
        'query' => [
          'page' => $view->getCurrentPage(),
        ],
        'absolute' => TRUE,
      ]);
    }
    else {
      $links['self'] = Url::fromRoute($route_name, $view_base_url->getRouteParameters(), [
        'absolute' => TRUE,
      ]);
    }

    // Determine whether we have more items than we are showing, in that case
    // we are a pageable collection.
    if ($pager->getTotalItems() > $pager->getItemsPerPage()) {
      // Calculate pager links.
      $current_page = $pager->getCurrentPage();
      // Starting at page=0 we need to decrement.
      $total = ceil($pager->getTotalItems() / $pager->getItemsPerPage()) - 1;
      // The total number of page for mini pager generates a big float number.
      $total = number_format($total, 0, NULL, '');

      $links['first'] = Url::fromRoute($route_name, [], [
        'query' => [
          'page' => 0,
        ],
        'absolute' => TRUE,
      ]);

      // If we are not on the first page add a previous link.
      if ($current_page > 0) {
        $links['prev'] = Url::fromRoute($route_name, [], [
          'query' => [
            'page' => $current_page - 1,
          ],
          'absolute' => TRUE,
        ]);
      }

      // If we are not on the last page add a next link.
      if ($current_page < $total) {
        $links['next'] = Url::fromRoute($route_name, [], [
          'query' => [
            'page' => $current_page + 1,
          ],
          'absolute' => TRUE,
        ]);
      }

      $links['last'] = Url::fromRoute($route_name, [], [
        'query' => [
          'page' => $total,
        ],
        'absolute' => TRUE,
      ]);
    }

    return $links;
  }

}
