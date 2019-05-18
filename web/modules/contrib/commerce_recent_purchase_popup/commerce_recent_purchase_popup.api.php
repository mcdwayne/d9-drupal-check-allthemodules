<?php

/**
 * @file
 * Doxygen documentation.
 */

use Drupal\Core\Entity\Query\Sql\Query;

/**
 * Allows other modules to alter order query.
 *
 * @param \Drupal\Core\Entity\Query\Sql\Query $query
 *   Order storage query.
 */
function hook_commerce_recent_purchase_popup_query_alter(Query &$query) {
  // Example, adds query condition.
  $query->condition('field_domain', 'my_domain');
}
