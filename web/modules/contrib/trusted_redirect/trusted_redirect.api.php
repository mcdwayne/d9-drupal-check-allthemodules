<?php

/**
 * @file
 * Hooks provided by the Trusted redirect module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allow modules to alter the list of trusted hosts.
 *
 * @param array $trusted_hosts
 *   Array of trusted hosts.
 *
 * @ingroup trusted_redirect
 */
function hook_trusted_redirect_hosts_alter(array &$trusted_hosts) {
  $satellite_hosts = \Drupal::config('satellite.settings')->get('hosts');
  array_merge($trusted_hosts, $satellite_hosts);
}

/**
 * @} End of "addtogroup hooks".
 */
