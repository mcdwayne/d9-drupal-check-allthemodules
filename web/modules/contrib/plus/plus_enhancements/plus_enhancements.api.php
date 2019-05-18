<?php
/**
 * @file
 * Hooks provided by the user_enhancements module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide user enhancement definitions.
 *
 * @return array
 *   An associative array, keyed by machine name, containing:
 *   - conditions: (array) (Optional) An indexed array of conditions that must
 *     be met for the enhancement to be loaded. Each condition must be an
 *     associative array that contains one of the following supported types:
 *     - entity: (string) An entity type or an entity_type:bundle value, e.g.
 *       array('entity' => 'node:page')
 *     - path: (string) A path to patch, e.g. array('path' => 'node/1234')
 *   - css: (array) (Optional) An array of CSS files to load. Follows the
 *     standard array syntax as defined in drupal_add_css(). An enhancement
 *     does not need to explicitly specify this value if a CSS file matching
 *     one the following paths is found (where {path} equals the path defined
 *     in the user enhancement definition and {name} is the machine name of
 *     the user enhancement):
 *     - {path}/{name}.min.css
 *     - {path}/{name}.css
 *     - {path}/{name}/{name}.min.css
 *     - {path}/{name}/{name}.css
 *   - dependencies: (array) (Optional) An indexed array of dependencies.
 *     Follows the standard array syntax as defined in drupal_add_library().
 *     You can, however, simply specify the name of a dependent user
 *     enhancement and it will automatically convert into the appropriate array
 *     syntax for loading library definition of that user enhancement, e.g.
 *     '{name}' will be converted into: array('user_enhancements', '{name}')
 *   - description: (string) (Optional) A description of the user enhancement.
 *     This will be displayed to the user in the UI where it can be enabled or
 *     disabled. It should reflect what the enhancement does so the user can
 *     make an informed decision about whether or not they wish to enable it.
 *   - enabled: (int|boolean) (Optional) Flag determining whether or not the
 *     user enhancement is enabled. Defaults to 0 (disabled).
 *   - experimental: (int|boolean) (Optional) Flag that indicates whether or
 *     not a user enhancement is experimental and should be indicated as such
 *     in the UI.
 *   - group: (string) (Optional) The group the user enhancement belongs to.
 *     This can be any value that has been defined by
 *     hook_user_enhancements_group_info(). If the specified group does not
 *     exist, it will fallback to the default "general" group.
 *   - js: (array) (Optional) An array of JavaScript files to load. Follows the
 *     standard array syntax as defined in drupal_add_js(). An enhancement does
 *     not need to explicitly specify this value if a JavaScript file matching
 *     one the following paths is found (where {path} equals the path defined
 *     in the user enhancement definition and {name} is the machine name of
 *     the user enhancement):
 *     - {path}/{name}.min.js
 *     - {path}/{name}.js
 *     - {path}/{name}/{name}.min.js
 *     - {path}/{name}/{name}.js
 *   - path: (string) (Optional) The path that is used to automatically
 *     discover CSS and JavaScript files for user enhancement. It defaults to a
 *     "user_enhancements" sub-directory of the module that defined this hook.
 *   - settings: (array) (Optional) An associative array of settings keyed by
 *     machine name.
 *   - title: (string) (Optional) The human readable title of the user
 *     enhancement. If not provided, it will simply use the machine name of the
 *     user enhancement as a fallback. It is highly recommended that this is
 *     defined.
 *   - ui: (int|boolean) (Optional) Flag determining whether or not the user
 *     enhancement should be visible in the UI. This is primarily used to
 *     "hide" a user enhancement from a user so they cannot "toggle" it on or
 *     off. Can also be used in conjunction with the "enabled" property to
 *     effectively make a user enhancement permanently enabled or disabled.
 *   - version: (string) (Optional) A version string.
 *
 */
function hook_user_enhancements_info() {
  // Default values provided by the user_enhancements module.
  $module_path = drupal_get_path('module', 'my_module');
  $name = 'my.enhancement';

  $enhancements[$name] = array(
    'conditions' => array(),
    'css' => array(),
    'dependencies' => array(),
    'description' => '',
    'enabled' => FALSE,
    'experimental' => TRUE,
    'group' => 'general',
    'js' => array(),
    'path' => "$module_path/user_enhancements",
    'settings' => array(),
    'title' => $name,
    'ui' => TRUE,
    'version' => NULL,
  );

  return $enhancements;
}

/**
 * Allow modules to alter enhancement definitions.
 *
 * @param array $enhancements
 *   An associative array of enhancement definitions, keyed by machine name.
 *
 * @see hook_user_enhancements_info()
 */
function hook_user_enhancements_info_alter(array &$enhancements) {
  // Flag enhancement as "experimental".
  $enhancements['my.enhancement']['experimental'] = TRUE;
}

/**
 * Provide user enhancement groups.
 *
 * @return array
 *   An associative array of user enhancement groups definitions, keyed by
 *   their machine name and containing:
 *   - title: The group translatable title.
 *   - description: The group translatable description.
 */
function hook_user_enhancements_group_info() {
  return array(
    'my.group' => array(
      'title' => t('My Group'),
      'description' => t('Gives an explaination for the enhancements that belong to this group.'),
    ),
  );
}

/**
 * Allow modules to alter user enhancement group definitions.
 *
 * @param array $groups
 *   An associative array of user enhancement groups definitions, keyed by
 *   their machine name and containing:
 *   - title: The group translatable title.
 *   - description: The group translatable description.
 */
function hook_user_enhancements_group_info_alter(&$groups) {
  // Rename the default group.
  $groups['general']['title'] = t('Global Settings');
}

/**
 * Allow modules to alter the "Enhancements" JavaScript settings array.
 *
 * This is primarily useful for adding additional runtime information to the
 * user enhancements.
 *
 * @param array $settings
 *   The "Enhancements" JavaScript settings array, passed by reference.
 */
function hook_user_enhancements_js_settings_alter(array &$settings) {
  if (isset($settings['my.enhancement']) && ($node = menu_get_object())) {
    $settings['my.enhancement']['nodeTitle'] = check_plain($node->title);
  }
}

/**
 * Provide a UI for user enhancement settings.
 *
 * @param array $enhancement
 *   The user enhancement definition array.
 *
 * @return array
 *  The user enhancement settings render array.
 */
function hook_user_enhancements_settings_form(array $enhancement) {
  $element = array();

  // Provide a enhancement specific setting.
  switch ($enhancement['name']) {

    case 'my.enhancement':
      $element['someSetting'] = array(
        '#type' => 'checkbox',
        '#title' => t('Some setting'),
        '#default_value' => !empty($enhancement['settings']['someSetting']) ? 1 :0,
      );
      break;

  }

  return $element;
}

/**
 * Alter hook for user enhancement settings UI.
 *
 * @param array $element
 *   The user enhancement setting render array, passed by reference.
 * @param array $enhancement
 *   The user enhancement definition array.
 */
function hook_user_enhancements_settings_form_alter(array &$element, array $enhancement) {
  // Provide a global setting.
  $element['globalSetting'] = array(
    '#type' => 'checkbox',
    '#title' => t('Global setting'),
    '#default_value' => !empty($enhancement['settings']['globalSetting']) ? 1 :0,
  );
}

/**
 * @} End of "addtogroup hooks".
 */
