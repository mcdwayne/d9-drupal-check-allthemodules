<?php

/**
 * @file
 * Hooks for mustache module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Register Mustache templates by a module.
 *
 * Themes cannot register templates. If a theme contains a template
 * which ends with .mustache.tpl, it will be preferred automatically.
 *
 * Information about registered templates will be cached.
 * Changes inside any hook implementation would only
 * take effect after the next cache clear.
 *
 * @return array
 *   Keyed by template name, which is a machine name and must not
 *   contain hyphens. Each entry is an array, with the following keys:
 *   - file: A string which is the path of the template file.
 *     Mustache templates must have the file ending .mustache.tpl.
 *   - default: (Optional) An array of default values for building
 *     a render element with this template. Equals the structure
 *     of render elements of type 'mustache'.
 *     See also the README, or the class MustacheRenderTemplate,
 *     for building arrays to render Mustache templates.
 *     When using DOM content synchronization, it always must
 *     be enabled by defining the '#sync' array subset, or via
 *     MustacheRenderTemplate::withClientSynchronization().
 */
function hook_mustache_templates() {
  $path = drupal_get_path('module', 'mymodule') . '/templates/';
  return [
    'social_share_buttons' => ['file' => $path . 'social-share-buttons.mustache.tpl'],
  ];
}

/**
 * Alter the registration of Mustache templates.
 *
 * @param array &$registered
 *   An array of registered templates via hook_mustache_templates().
 */
function hook_mustache_templates_alter(array &$registered) {
  $path = drupal_get_path('module', 'othermodule') . '/templates/';
  $registered['social_share_buttons']['file'] = $path . 'some_other_buttons.mustache.tpl';
}

/**
 * @} End of "addtogroup hooks".
 */
