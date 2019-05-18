<?php

/**
 * @file
 * API function documentation and examples.
 */

use Drupal\node\Entity\Node;

/**
 * Implements hook_recurly_aegir_quota_info().
 *
 * Define quota limits for sites based on the site itself, its associated plan
 * code and any accompanying add-ons.
 *
 * The add-ons will come in as an array, like so:
 *
 * [0] => [
 *   [code] => add_on_code_1
 *   [quantity] => 4
 * ]
 *
 * [1] => [
 *   [code] => add_on_code_2
 *   [quantity] => 8
 * ]
 *
 * @param Drupal\node\Entity\Node $site
 *   The site whose quota limits are to be set.
 * @param string $plan_code
 *   The plan code for this site's subscription.
 * @param array $addons
 *   The list of add-ons included in the subscription.
 *
 * @return array
 *   The list of quota limits to set for the site in question, keyed by quota
 *   ID. The values are the limits themselves, in integer format.
 *
 * @see https://www.drupal.org/project/quenforcer
 */
function hook_recurly_aegir_quota_info(Node $site, $plan_code, array $addons) {
  // Set identical storage and user quotas for all sites.
  return [
    'config||quenforcer.settings~users_max_number' => 4,
    'config||quenforcer.settings~storage_max_megabytes' => 2048,
  ];
}
