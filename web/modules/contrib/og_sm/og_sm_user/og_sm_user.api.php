<?php
/**
 * @file
 * API documentation about the og_sm_user module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Defines user profile sections to be displayed on the profile page.
 *
 * The hook can be put in the yourmodule.module OR in the yourmodule.og_sm.inc
 * file. The recommended place is in the yourmodule.og_sm.inc file as it keeps
 * your .module file cleaner and makes the platform load less code by default.
 *
 * @return array
 *   Array of section info, with the section machine name as key. Possible
 *   attributes:
 *   - "render callback": The function to call to display the section on the
 *     profile page.
 *   - "weight": (optional) The weight of the section.
 *
 * @see og_sm_user_get_sections_info()
 */
function hook_og_sm_user_profile_info() {
  return array(
    'basic_info' => array(
      'render callback' => 'og_sm_user_section_basic_info',
      'weight' => 0,
    ),
  );
}

/**
 * Alters user profile sections defined by hook_og_sm_user_profile_info().
 *
 * The hook can be put in the yourmodule.module OR in the yourmodule.og_sm.inc
 * file. The recommended place is in the yourmodule.og_sm.inc file as it keeps
 * your .module file cleaner and makes the platform load less code by default.
 *
 * @param array $sections
 *   An associative array containing sections to be displayed on the profile
 *   page.
 *
 * @see hook_og_sm_user_profile_info()
 */
function hook_og_sm_user_profile_info_alter(&$sections) {
  $sections['basic_info']['weight'] = 5;
}
