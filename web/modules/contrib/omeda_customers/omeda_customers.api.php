<?php

/**
 * @file
 * Hooks related to the Omeda Customers module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the information that gets sent to Omeda when a user gets saved.
 *
 * @param array $data
 *   The array of data to be submitted to Omeda.
 * @param \Drupal\user\Entity\User $user
 *   The user being saved.
 */
function hook_omeda_customer_data_alter(array &$data, \Drupal\user\Entity\User $user) {
  $data['CustomDataAttribute'] = 'CustomValue';
}

/**
 * @} End of "addtogroup hooks".
 */
