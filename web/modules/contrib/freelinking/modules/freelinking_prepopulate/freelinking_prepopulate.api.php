<?php

/**
 * @file
 * Freelinking Prepopulate API.
 *
 * Extends the Freelinking API with Prepopulate functions.
 *
 * These functions are for Freelinking Plugin developers.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allows to alter the query parameters for prepopulate links.
 *
 * @param array &$query
 *   The query array for the prepopulate link.
 * @param array $target
 *   The target array in the plugin.
 */
function hook_freelinking_prepopulate_query_alter(array &$query, array $target) {
  // Add some default fields.
  $query['edit[field_default]'] = 'some value';
}

/**
 * @} End of "addtogroup hooks".
 */
