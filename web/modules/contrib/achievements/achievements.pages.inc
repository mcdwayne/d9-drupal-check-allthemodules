<?php

/**
 * @file
 * Page callbacks for the Achievements module.
 */

/**
 * Default theme for the wrapper around a user's achievements page.
 *
 * @param $variables
 *   An associative array containing:
 *   - element: A render containing the user's achievements page.
 */
function theme_achievement_groups_wrapper($variables) {
  return '<div id="achievement-groups">' . $variables['element']['#children'] . '</div>';
}

/**
 * Default theme for the wrapper around an achievement group.
 *
 * @param $variables
 *   An associative array containing:
 *   - element: A render containing the achievement group.
 */
function theme_achievement_group_wrapper($variables) {
  $group_id = 'achievement-group-' . $variables['element']['#group_id'];
  return '<div id="' . $group_id . '" class="achievement-group">' . $variables['element']['#children'] . '</div>';
}
