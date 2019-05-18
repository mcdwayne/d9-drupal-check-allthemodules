<?php

/**
 * @file
 * Documentation for Entity UI module APIs.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Modify the list of available Entity Tab content plugins.
 *
 * This hook may be used to modify plugin properties after they have been
 * specified by other modules.
 *
 * @param $plugins
 *   An array of all the existing plugin definitions, passed by reference.
 *
 * @see \Drupal\entity_ui\Plugin\EntityTabContentManager
 */
function hook_entity_ui_entity_tab_content_info_alter(array &$plugins) {
  $plugins['someplugin']['label'] = t('Better name');
}
