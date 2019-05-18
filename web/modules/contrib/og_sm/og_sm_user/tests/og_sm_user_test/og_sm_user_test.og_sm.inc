<?php
/**
 * @file
 * Hooks implemented to test the User profile functionality.
 */

/**
 * Implements hook_og_sm_user_profile_info().
 */
function og_sm_user_test_og_sm_user_profile_info() {
  return array(
    'favorite_animal' => array(
      'render callback' => 'og_sm_user_test_section_favorite_animal',
      'weight' => -20,
    ),
  );
}
