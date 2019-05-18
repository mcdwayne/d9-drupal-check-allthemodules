<?php

/**
 * @file
 * Hooks specific to the Google Site Search module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Act on a GSS search result being displayed.
 *
 * This hook is invoked from the gss search plugin during search execution,
 * after retrieving the search results.
 *
 * @param stdClass $result
 *   The search result item from Google Site Search API.
 *
 * @return array
 *   Extra information to be displayed with search result. This information
 *   should be presented as an associative array. It will be concatenated with
 *   the post information (last updated, author) in the default search result
 *   theming.
 *
 * @see template_preprocess_search_result()
 * @see search-result.html.twig
 */
function hook_gss_search_result($result) {
  if (isset($result->pagemap->cse_thumbnail[0])) {
    return [
      'cse_thumbnail' => [
        '#theme' => 'image',
        '#uri'   => $result->pagemap->cse_thumbnail[0]->src,
      ],
    ];
  }
}

/**
 * @} End of "addtogroup hooks".
 */
