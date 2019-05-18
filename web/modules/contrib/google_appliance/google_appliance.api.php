<?php

/**
 * @file
 * This file contains no working PHP code; it exists to provide additional
 * documentation for doxygen as well as to document hooks in the standard
 * Drupal manner.
 */

use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * @defgroup google_appliance Google Search Appliance module integrations.
 *
 * Module integrations with the Google Search Appliance module.
 */

/**
 * @defgroup google_appliance_hooks Google Search Appliance's hooks
 * @{
 * Hooks that can be implemented by other modules in order to extend the Google
 * Search Appliance module.
 */

/**
 * Alter Google Search Appliance queries.
 *
 * This hook is invoked after Google Search Appliance builds the query, just
 * before the query is sent to the appliance.
 *
 * Use this to alter which collection is queried, which frontend is used, add
 * meta tag constraints, restrict queries to specific languages, etc. A
 * complete
 * list of search parameters is available in the Google developer documentation
 * referenced below.
 *
 * @param $params
 *   The search params before they're sent to the host.
 *
 * @see \Drupal\google_appliance\Service\Search::search
 * @see https://developers.google.com/search-appliance/documentation/614/xml_reference#request_parameters
 */
function hook_google_appliance_query_alter(&$params) {
  // Only return English language results.
  $query['lr'] = 'en';
}

/**
 * Alter Google Search Appliance results.
 *
 * This hook is invoked after the search appliance returns with a response and
 * the Google Search Appliance module does its basic parsing.
 *
 * Use this to make result alterations that are inappropriate at the theme
 * level.
 *
 * @param \Drupal\google_appliance\SearchResults\ResultSet $results
 *   The search results.
 *
 * @see \Drupal\google_appliance\Service\Search::search
 */
function hook_google_appliance_response_alter(Drupal\google_appliance\SearchResults\ResultSet $results) {
  $results->addSynonym(new \Drupal\google_appliance\SearchResults\Synonym('Foo', 'foo'));
}

/**
 * Alter the cluster list render array containing related searches.
 *
 * This hook is invoked after the list render array is constructed and just
 * before it is passed to drupal_render().
 *
 * Use this to alter the render array properties.
 *
 * @param $cluster_list
 *   A renderable array conforming to theme_item_list().
 *
 * @param $cluster_results
 *   The raw cluster results returned via the Google Appliance instance.
 *
 * @see google_appliance_get_clusters()
 * @see theme_item_list()
 *
 * @todo Update as appropriate.
 */
function hook_google_appliance_cluster_list_alter(&$cluster_list, $cluster_results) {
  // Add some CSS classes.
  $cluster_list['#attributes']['class'][] = 'foo-list';
  $cluster_list['#items'] = [];

  // Construct a new list of links using the raw results with a custom path.
  foreach ($cluster_results as $cluster) {
    $cluster_list['#items'][] = Link::fromTextAndUrl(
      $cluster['label'],
      Url::fromUri('search/my/path/' . $cluster['label'])
    )->toString();
  }

  // Change the first item of the list.
  $cluster_list['#items'][0] = '<span>A new item</span>';
}

/**
 * @}
 */
