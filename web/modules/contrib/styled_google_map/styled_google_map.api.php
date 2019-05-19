<?php

/**
 * @file
 * Describes the list of available hooks for the module.
 *
 * @addtogroup hooks
 */

/**
 * Defines hooks provided by styled google map module.
 *
 * Should be used only to change map settings and/or locations.
 *
 * $variables contains two subarrays:
 *  - map_settings: all map settings starting from icons to cluster settings,
 *      besides map_settings['locations'] has all points of the map that you
 *      can change inside this hook;
 *  - context: contains of view object and view options, this is only for
 *      condition checks, there is no need to changes this setting, because
 *      the output does not depend on them and there are other hooks for this
 *      purpose.
 *
 * @param array $variables
 *   The variables that are passed to the hook.
 */
function hook_styled_google_map_views_style_alter(array &$variables) {
  if ($variables['map_settings']['settings']['cluster']) {
    $cluster_icon = 'cluster_icon.png';
  }
  if ($variables['context']['view']->name == 'my_awesome_view') {
    drupal_set_message('Alter the output of my view');
  }
  if (!empty($variables['context']['options'])) {
    $icon = $variables['context']['options']['main']['styled_google_map_view_active_pin'];
  }
}
