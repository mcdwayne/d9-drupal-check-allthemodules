<?php

/**
 * @file
 * Describes the api for add to cart matrix.
 */

/**
 * Allows you to alter the data to be displayed.
 *
 * This can be used to add prefixes or suffixes. A usecase could be to display
 * the variation stock.
 *
 * @param array $data
 *   The data.
 */
function hook_matrix_product_item_alter(array &$data) {
  $data['#suffix'] = t('No stock');
}
