<?php

/**
 * @file
 * Provides functionality to be used at install time.
 */

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Generates install tasks to install subprofile modules.
 *
 * @param $install_state
 *   An array of information about the current installation state.
 *
 * @return
 *   The install task definition.
 */
function subprofiles_get_install_tasks($install_state) {
  // If there's a custom distribution name, use it.
  if (isset($install_state['profile_info']['distribution_name']) && $install_state['profile_info']['distribution_name'] != 'Drupal') {
    $profile = $install_state['profile_info']['distribution_name'];
  }
  // Otherwise, if there's a profile name, use it.
  elseif (isset($install_state['profile_info']['name'])) {
    $profile = $install_state['profile_info']['name'];
  }
  else {
    $profile = t('profile');
  }

  $tasks = [
    // Enable the selected modules.
    'subprofiles_install_profile_modules' => [
      'display_name' => t('Install @profile modules', ['@profile' => $profile]),
      'type' => 'batch',
    ],
    // Subprofiles is no longer needed when its work is done, so uninstall.
    'subprofiles_uninstall_self' => [
      'display' => FALSE,
    ],
  ];
  return $tasks;
}

/**
 * Installation task; install profile modules via a batch process.
 *
 * @param $install_state
 *   An array of information about the current installation state.
 *
 * @return
 *   The batch definition.
 */
function subprofiles_install_profile_modules(&$install_state) {
  // If the installer was run interactively, specific modules may have been selected.
  $modules = \Drupal::state()->get('subprofiles_modules');
  \Drupal::state()->delete('subprofiles_modules');
  // If the install wasn't interactive, use the default modules of the
  // subprofile.
  // We can't test for empty since there may have been no modules selected in an
  // interactive install. NULL is the default return value of State::get().
  if ($modules === NULL) {
    // Consult a setting, which defaults to 'standard'.
    $subprofile_name = \Drupal::config('subprofiles.settings')->get('subprofile');
    $subprofile = subprofiles_get_subprofile($install_state['parameters']['profile'], $subprofile_name);
    // Filter so that we have only the modules set to be enabled by default.
    $modules = array_keys(array_filter($subprofile['modules']));
  }
  module_load_include('inc', 'subprofiles', 'subprofiles.module_batch');
  return subprofiles_get_module_batch($modules);
}

/**
 * Implements hook_form_FORM_ID_alter() for install_configure_form.
 *
 * Alter the site configuration form to enable selecting of a subprofile and
 * specific subprofile modules to be enabled.
 */
function subprofiles_form_install_configure_form_alter(&$form, $form_state) {
  if ($subprofiles = subprofiles_get_subprofiles()) {
    $profile_info = install_profile_info(drupal_get_profile());
    $form['subprofiles'] = [
      '#type' => 'fieldset',
      '#title' => t('Select the type of @profile site you wish to install.', ['@profile' => $profile_info['name']]),
      '#tree' => FALSE,
      '#weight' => -30,
    ];
    $modules = subprofiles_get_modules();
    // Add a "custom" suprofile that will show as unselected options all
    // available modules.
    $subprofiles['custom'] = [
      'name' => t('Custom'),
      'description' => t('Customize your own install.'),
      'modules' => array_fill_keys(array_keys($modules), FALSE),
    ];
    // Add an "everything" subprofile that show as selected options all
    // available modules.
    $subprofiles['everything'] = [
      'name' => t('Everything'),
      'description' => t('Install all available features.'),
      'modules' => array_fill_keys(array_keys($modules), TRUE),
    ];
    foreach ($modules as $machine_name => $module) {
      $modules[$machine_name] = [
        '#type' => 'checkbox',
        '#title' => '<strong>' . $module['name'] . '</strong><p><div>' . $module['description'] . '</div></p>'
      ];
    }

    $form['subprofiles']['subprofile'] = [
      '#type' => 'select',
      '#default_value' => \Drupal::config('subprofiles.settings')->get('subprofile'),
      // Hide by default for non-js usage.
      '#attributes' => ['class' => ['element-hidden']],
    ];
    $options = [];
    foreach ($subprofiles as $subprofile_name => $subprofile) {
      $options[$subprofile_name] = $subprofile['name'];
      if (isset($subprofile['description'])) {
        $form['subprofiles']['subprofile_' . $subprofile_name] = [
          '#type' => 'item',
          '#markup' => $subprofile['description'],
          '#states' => [
            'visible' => ['select[name=subprofile]' => ['value' => $subprofile_name]],
          ],
        ];
      }
      // Reduce selectable modules to those specified in the selected subprofile
      // and set their default enabled status.
      foreach ($subprofile['modules'] as $name => $enable) {
        $modules[$name]['#use'] = TRUE;
        $modules[$name]['#states']['visible']['select[name=subprofile]'][] = ['value' => $subprofile_name];
        if ($enable) {
          $modules[$name]['#states']['checked']['select[name=subprofile]'][] = ['value' => $subprofile_name];
        }

      }
    }
    $form['subprofiles']['subprofile']['#options'] = $options;
    $form['subprofiles']['modules'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ] + $modules;
    $form['#attached']['library'][] = 'subprofiles/drupal.subprofiles.install';
    $form['#submit'][] = 'subprofiles_install_configure_form_submit';
  }
}

/**
 * Submit handler for subprofiles_install_configure_form_submit.
 *
 * Saves the selected subprofile and adds selected modules to the state.
 */
function subprofiles_install_configure_form_submit($form, FormStateInterface $form_state) {
  // Save the selected subprofile to a setting.
  $subprofile = $form_state->getValue('subprofile');
  \Drupal::configFactory()->getEditable('subprofiles.settings')
    ->set('subprofile', $subprofile)
    ->save();
  // Save selected modules in the state.
  $modules = array_keys(array_filter($form_state->getValue('modules')));
  \Drupal::state()->set('subprofiles_modules', $modules);
}

/**
 * Returns an array of data about subprofiles of a profile.
 */
function subprofiles_get_subprofiles($profile = NULL) {
  return subprofiles_get_data($profile, 'subprofiles');
}

/**
 * Returns an array of data about subprofiles of a profile.
 */
function subprofiles_get_modules($profile = NULL) {
  return subprofiles_get_data($profile, 'modules');
}

/**
 * Returns subprofiles data.
 */
function subprofiles_get_data($profile = NULL, $key = NULL) {
  static $data = NULL;

  if (is_null($data)) {
    $profile = _subprofiles_get_profile($profile);
    $filename = drupal_get_path('profile', $profile) . '/' . $profile . '.subprofiles.yml';
    if (file_exists($filename)) {
      $data = Yaml::decode(file_get_contents($filename));
    }
    else {
      $data = FALSE;
    }
  }

  if ($data) {
    return $key ? $data[$key] : $data;
  }

  return FALSE;
}

/**
 * Returns an array of data about a specified subprofile of a profile.
 *
 * @param $profile
 *   The name of a profile.
 * @param $subprofile
 *   The name of a subprofile.
 *
 * @return
 *   Array of information about the specified subprofile, if available.
 */
function subprofiles_get_subprofile($profile = NULL, $subprofile = NULL) {
  if ($subprofile === NULL) {
    $subprofile = \Drupal::config('subprofiles.settings')->get('subprofile');
  }
  $subprofiles = subprofiles_get_subprofiles($profile);
  return $subprofiles && isset($subprofiles[$subprofile]) ? $subprofiles[$subprofile] : FALSE;
}

/**
 * Helper function to determine the site's profile if none is specified.
 *
 * @param $profile
 *   The name of a profile.
 *
 * @return
 *   The name of the site's install_profile.
 */
function _subprofiles_get_profile($profile) {
  return empty($profile) ? drupal_get_profile() : $profile;
}

/**
 * Uninstall subprofiles when its work is done.
 */
function subprofiles_uninstall_self() {
  \Drupal::service('module_installer')->uninstall(['subprofiles']);
}
