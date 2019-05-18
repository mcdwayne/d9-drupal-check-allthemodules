<?php
/**
 * @file
 * List of available procedural hook and alter APIs for use in your sub-theme.
 */

/**
 * @addtogroup plugins_alter
 *
 * @{
 */

/**
 * Allows sub-themes to alter the "Theme" plugin annotation discover info.
 *
 * This is all the definitions that were discovered in all active themes prior
 * to them being cached in the database.
 *
 * Note: this alter hook must remain procedural and reside in its .theme file.
 *
 * @param array $definitions
 *   An associative array of plugin definitions keyed by their machine name,
 *   passed by reference.
 */
function hook_plus_theme_plugins_alter(array &$definitions) {
  // Change the base class to something else.
  $definitions['_base']['class'] = '\\Drupal\\SUBTHEME_NAME\\src\\Plugin\\Theme\\SubthemeCustomBaseClass';
  $definitions['_base']['provider'] = 'SUBTHEME_NAME';
}

/**
 * @} End of "addtogroup".
 */
