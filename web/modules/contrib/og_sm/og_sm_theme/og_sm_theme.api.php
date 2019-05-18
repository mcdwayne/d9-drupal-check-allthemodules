<?php
/**
 * @file
 * API documentation about the og_sm_theme module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the list of allowed site themes for a Site.
 *
 * The hook can be put in the yourmodule.module OR in the yourmodule.og_sm.inc
 * file. The recommended place is in the yourmodule.og_sm.inc file as it keeps
 * your .module file cleaner and makes the platform load less code by default.
 *
 * @param array $themes
 *   The allowed site themes for this Site.
 * @param array $context
 *   The context - contains the Site for which to alter the themes list.
 */
function hook_og_sm_theme_themes_site_alter(&$themes, $context) {

}

/**
 * Alters theme operation links for a Site.
 *
 * The hook can be put in the yourmodule.module OR in the yourmodule.og_sm.inc
 * file. The recommended place is in the yourmodule.og_sm.inc file as it keeps
 * your .module file cleaner and makes the platform load less code by default.
 *
 * @param array $theme_groups
 *   An associative array containing groups of themes.
 *
 * @see og_sm_theme_themes_page()
 */
function hook_og_sm_theme_themes_page_alter(&$theme_groups) {

}

/**
 * @} End of "addtogroup hooks".
 */
