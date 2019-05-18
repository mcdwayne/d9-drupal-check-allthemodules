<?php

/**
 * @file
 * Hooks provided by the Offline application module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alters the pages configuration.
 *
 * @param array $pages
 *   All the explicit page/assets entries.
 */
function hook_offline_app_appcache_pages_alter(&$pages) {
  $pages[] = '/additional-url';
}

/**
 * Alters the fallback configuration.
 *
 * @param array $fallback
 *   All the fallback entries.
 */
function hook_offline_app_appcache_fallback_alter(&$fallback) {
  $fallback[] = '/image1.png /fallback-image.png';
}

/**
 * Alters the network configuration.
 *
 * @param array $network
 *   All the network entries.
 */
function hook_offline_app_appcache_network_alter(&$network) {
  $network[] = '/this-page';
}

/**
 * @} End of "addtogroup hooks".
 */


