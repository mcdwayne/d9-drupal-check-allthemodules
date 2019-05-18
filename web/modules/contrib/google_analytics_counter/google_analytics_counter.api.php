<?php

/**
 * @file
 * Hooks provided by the Google Analytics Counter module.
 */

/**
 * Alter Select query before it gets executed.
 *
 * Here you can customize the select query which writes into the
 * proper storage table the number of pageviews for each node.
 *
 * @param SelectQuery $query
 *   Query builder for SELECT statements.
 */
function hook_google_analytics_counter_query_alter(SelectQuery &$query) {
  // Example: Restrict node pageview storage to node type: blog.
  $query->condition('type', 'blog', 'LIKE');
}

/**
 * Informs other modules about which nodes have been updated.
 *
 * @param array $updated_nids
 *   Associative array with the new pageview total keyed by the nid.
 */
function hook_google_analytics_counter_update(array $updated_nids) {

}
