<?php

/**
 * @file
 * Describes API functions for the RedHen Asset module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the display name for a asset.
 *
 * @param string $name
 *   The generated name.
 * @param Drupal\redhen_asset\AssetInterface $asset
 *   The asset whose name is being generated.
 *
 * @return string
 */
function hook_redhen_asset_name_alter(&$name, Drupal\redhen_asset\AssetInterface $asset) {
  return $asset->get('last_name')->value . ', ' . $asset->get('first_name')->value;
}

/**
 * @} End of "addtogroup hooks".
 */
