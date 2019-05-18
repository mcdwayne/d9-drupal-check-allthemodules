<?php

/**
 * @file
 * API documentation file for micro_node module.
 */

/**
 * Alter the grants set for an account in a site context.
 *
 * Note that this hook does not fire for users with the 'administer site entities'
 * permission in a site context or "administer nodes" in the master host context.
 *
 * @param array $grants
 *   The $grants array returned by micro_node_get_account_grants.
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The account of the user.
 * @param array $context
 *   A keyed array passing two items:
 *   - op : The operation for the grants (view, update, delete)
 *   - sites_user : an array of sites id on which the account is refrenced.
 *   - site : The site entity if it exists, otherwise if on the master hosts NULL.
 *
 * No return value. Modify the $grants array if needed.
 */
function hook_get_account_grants_alter(&$grants, $account, $context) {

}
