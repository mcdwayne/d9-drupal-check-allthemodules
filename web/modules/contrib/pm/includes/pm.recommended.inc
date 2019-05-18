<?php
/**
 * @file
 * Recommended modules helper functions.
 */

/**
 * Generates a table of recommended modules.
 */
function pm_recommended_modules_table() {
  // Initialise arrays.
  $header = array(
    'name' => t('Module'),
    'functionality' => t('Functionality'),
    'operations' => t('Operations'),
  );

  $rows = array();

  // Retrieve row information.
  $recommended_modules = module_invoke_all('pm_recommended_modules_info');
  $available_modules = pm_get_available_modules();

  foreach ($recommended_modules as $shortname => $recommended_module) {

    if (isset($available_modules[$shortname])) {

      if ((bool) $available_modules[$shortname]['status']) {
        $status = '<span class="admin-enabled">enabled</span>';
        $operations = '';
      }
      else {
        $status = '<span class="admin-disabled">disabled</span>';

        if (drupal_valid_path('admin/modules')) {
          $operations = l(t('Enable'), 'admin/modules');
        }
        else {
          $operations = 'Contact an administrator to enable this module';
        }
      }

    }
    else {
      $status = '<span class="admin-missing">missing</span>';
      $operations = l(t('Download'), $recommended_module['download']);
    }

    $rows[] = array(
      'name' => '<strong>' . $recommended_module['name'] . '</strong> (' . $status . ')',
      'functionality' => $recommended_module['functionality'],
      'operations' => $operations,
    );
  }

  // Generate table.
  return theme('table', array(
    'header' => $header,
    'rows' => $rows,
    'empty' => t('No additional modules are currently recommended.'),
  ));
}

/**
 * Determines whether any recommended modules are missing or disabled.
 */
function pm_recommended_modules_problem() {
  $counts = pm_recommended_modules_count();

  if ($counts['missing'] > 0 || $counts['disabled'] > 0) {
    return TRUE;
  }
  return FALSE;
}

/**
 * Determines the number of recommended modules, by status.
 */
function pm_recommended_modules_count() {
  $recommended_modules = module_invoke_all('pm_recommended_modules_info');
  $available_modules = pm_get_available_modules();

  $num_enabled = 0;
  $num_disabled = 0;
  $num_missing = 0;

  foreach ($recommended_modules as $shortname => $recommended_module) {
    if (isset($available_modules[$shortname])) {
      if ((bool) $available_modules[$shortname]['status']) {
        $num_enabled++;
      }
      else {
        $num_disabled++;
      }
    }
    else {
      $num_missing++;
    }
  }

  return array(
    'enabled' => $num_enabled,
    'disabled' => $num_disabled,
    'missing' => $num_missing,
  );
}

/**
 * Helper function to determine all modules available to install.
 */
function pm_get_available_modules() {
  $available_modules = array();

  foreach (system_rebuild_module_data() as $available_module) {
    $name = $available_module->name;
    $available_modules[$name]['status'] = $available_module->status;
  }

  return $available_modules;
}

/**
 * Implements hook_pm_recommended_modules_info().
 */
function pm_pm_recommended_modules_info() {
  return array(
    'field_group' => array(
      'name' => 'Field Group',
      'functionality' => t('Allows fields to be structured into labelled groups. This module is pre-configured by Project Management to work "out of the box", although the configuration can also be customised.'),
      'download' => 'https://www.drupal.org/project/field_group',
    ),
    'fontawesome' => array(
      'name' => 'Font Awesome',
      'functionality' => t('Replaces the included legacy icons with modern icons in the Bootstrap style. This gives an opportunity to harmonize styles across the site.'),
      'download' => 'https://www.drupal.org/project/fontawesome',
    ),
  );
}
