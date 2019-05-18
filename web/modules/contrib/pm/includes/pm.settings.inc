<?php
/**
 * @file
 * Admin settings form functions.
 */

/**
 * Defines the administration settings form.
 */
function pm_admin_settings() {
  $form = array();
  $w = -10;

  // Recommended modules.
  module_load_include('inc', 'pm', 'includes/pm.recommended');

  $form['recommended'] = array(
    '#type' => 'fieldset',
    '#title' => t('Recommended modules'),
    '#collapsible' => TRUE,
    '#collapsed' => !pm_recommended_modules_problem(),
    '#weight' => $w++,
  );

  $form['recommended']['help'] = array(
    '#type' => 'markup',
    '#markup' => t('The following modules significantly enhance Project Management and are recommended for installation.'),
  );

  $form['recommended']['table'] = array(
    '#type' => 'markup',
    '#markup' => pm_recommended_modules_table(),
  );

  // Icons.
  $form['icons'] = array(
    '#type' => 'fieldset',
    '#title' => t('Icons'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#weight' => $w++,
  );

  $icon_options = array(
    PM_ICON_SET_DEFAULT_BEHAVIOUR => t('<strong>Default:</strong> Displays Font Awesome icons if the Font Awesome module and library are installed. Otherwise reverts to PM legacy icons.'),
    PM_ICON_SET_FONTAWESOME => t('<strong>Font Awesome:</strong> Displays Font Awesome icons. Requires Font Awesome module and library to be installed.'),
    PM_ICON_SET_STORM => t('<strong>PM legacy:</strong> Displays PM legacy icons. Deprecated - this option will be removed in a future release.'),
    PM_ICON_SET_NO_ICON => t('<strong>None:</strong> Hides all icons. Select this option if the icons available do not suit your selected theme.'),
  );

  $form['icons']['pm_icon'] = array(
    '#type' => 'radios',
    '#multiple' => FALSE,
    '#title' => t('Project Management icons'),
    '#options' => $icon_options,
    '#default_value' => variable_get('pm_icon', PM_ICON_SET_DEFAULT_BEHAVIOUR),
    '#weight' => $w++,
  );

  if (module_exists('fontawesome')) {
    $fa_module_status = 'The <em>Font Awesome</em> module is <span class="admin-enabled">enabled</span>.';

    if (($library = libraries_detect('fontawesome')) && !empty($library['installed'])) {
      $fa_library_status = ' The <em>Font Awesome</em> library is <span class="admin-enabled">installed</span>.';
    }
    else {
      $fa_library_status = ' <span class="admin-missing">' . $library['error message'] . '</span>';
    }
  }
  else {
    $fa_module_status = 'The <em>Font Awesome</em> module is <span class="admin-missing">not enabled</span>.';
    $fa_library_status = '';
  }

  $form['icons']['fa_status'] = array(
    '#type' => 'markup',
    '#markup' => $fa_module_status . $fa_library_status,
  );

  return system_settings_form($form);
}
