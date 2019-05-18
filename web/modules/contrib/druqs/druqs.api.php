<?php

/**
 * @file
 * Hooks for the omnibox module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Add search results to a given omnibox search string.
 *
 * @param array $args
 *   An associate array with the following keys:
 *     'input': the search string
 *     'results_current': the current amount of results
 *     'results_per_source': the maximum amount of results per source
 *     'results_max': the total amount of results.
 *
 * @return array
 *   An array of arrays with the following keys:
 *     'type': the type of result
 *     'title': the result title
 *     'actions': an array of actions, with link titles as key urls as values.
 */
function hook_druqs_search(array &$args) {

  $results = [];

  if ($args['results_current'] < $args['results_max']) {

    // Determine how many results we can still add.
    $limit = min($args['results_per_source'], $args['results_max'] - $args['results_current']);

    // Create query for node titles.
    $q = \Drupal::database()->select('node_field_data', 'n');
    $q->fields('n', ['nid', 'title', 'type']);
    $q->condition('n.title', '%' . $q->escapeLike($args['input']) . '%', 'LIKE');

    // And format the results.
    foreach ($q->range(0, $limit)->execute() as $record) {
      $results[] = [
        'type' => 'Content (' . $record->type . ')',
        'title' => $record->title,
        'actions' => [
          'view' => '/node/' . $record->nid,
          'edit' => '/node/' . $record->nid . '/edit',
        ],
      ];

      // Increment the result counter.
      $args['results_current']++;
    }
  }

  return $results;
}

/**
 * @} End of "addtogroup hooks".
 */
