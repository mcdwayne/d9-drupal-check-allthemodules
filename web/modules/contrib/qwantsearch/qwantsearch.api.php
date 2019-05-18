<?php

/**
 * @file
 * Hooks related to qwantsearch.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allows user to alter thumbnail generation (image style ...).
 *
 * @param array $variables
 *   Variables to alter (image style, dimensions...)
 */
function hook_qwantsearch_result_thumbnail_alter(array &$variables) {
  $variables['#style_name'] = 'large';
  $variables['#width'] = '500';
  $variables['#height'] = '300';
}

/**
 * Allows user to alter search result (title, snippet...)
 *
 * @param array $renderable_result
 *   Variables to alter (snippet, title...).
 * @param array $row
 *   Result row from Qwant.
 */
function hook_qwantsearch_search_result_alter(array &$renderable_result, array $row) {
  $renderable_result['#title'] = strtoupper(strip_tags(html_entity_decode($row['title'], ENT_QUOTES)));
  $renderable_result['#snippet'] = substr(strip_tags(html_entity_decode($row['desc'], ENT_QUOTES)), 125);
}

/**
 * @} End of "addtogroup hooks".
 */
