<?php

/**
 * @file
 * Forms for profile management.
 */

/**
 * Form displaying all available profile and groups.
 *
 * @return array
 */
function navigation_timing_admin() {
  $form = array();

  $query_profile = 'SELECT nt.*, COUNT(*) as auto_groups
    FROM {navigation_timing_profile} nt
     INNER JOIN {navigation_timing_group} ng USING (profile_name)
     GROUP BY nt.profile_name
     ORDER BY profile_name ASC';

  $form['profile'] = array(
    '#type' => 'fieldset',
    '#title' => t('Profiles'),
  );
  $form['profile']['add'] = array(
    '#markup' => l(t('Create new profile'), 'admin/config/development/navigation_timing/profile/add'),
  );
  $profile_rows = array();
  $profiles = db_query($query_profile);
  foreach ($profiles as $profile) {
    $profile_rows[] = array(
      check_plain($profile->title),
      check_plain($profile->description),
      check_plain($profile->auto_groups),
      format_date($profile->created),
      check_plain($profile->log_percent) . '%',
      l(t('Edit'), 'admin/config/development/navigation_timing/profile/' . $profile->profile_name . '/edit'),
    );
  }
  $form['profile']['list'] = array(
    '#theme' => 'table',
    '#header' => array(
      t('Title'),
      t('Description'),
      t('Sub groups'),
      t('Created'),
      t('Log percent'),
      t('Operations'),
    ),
    '#rows' => $profile_rows,
  );

  return $form;
}

/**
 * Navigation timing profile creation form.
 *
 * @param array $form
 * @param array $form_state
 *
 * @return array
 */
function navigation_timing_admin_create_profile($form, $form_state) {
  $form = array();
  $form['profile_name'] = array(
    '#type' => 'textfield',
    '#maxlength' => 32,
    '#title' => t('Profile machine name'),
    '#required' => TRUE,
  );
  $form['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Title'),
    '#required' => TRUE,
  );
  $form['description'] = array(
    '#type' => 'textarea',
    '#title' => t('Description'),
    '#rows' => 2,
  );

  $log_percent_options = array();
  foreach (range(100, 10, -10) as $percent) {
    $log_percent_options[$percent] = $percent . "%";
  }

  $form['log_percent'] = array(
    '#type' => 'select',
    '#title' => t('Percent of requests to log'),
    '#options' => $log_percent_options,
    '#description' => t("Note that the percent of requests that will log timings isn't exact, but based on a client-side random number so this option is compatible with reverse proxy caches."),
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );
  return $form;
}

/**
 * Navigation timing profile creation form submit handler.
 *
 * @param array $form
 * @param array $form_state
 */
function navigation_timing_admin_create_profile_submit($form, &$form_state) {
  $values = $form_state['values'];
  navigation_timing_create_profile($values['profile_name'], $values);
  $form_state['redirect'] = 'admin/config/development/navigation_timing';
}

/**
 * Navigation timing profile edit form.
 *
 * @param array $form
 * @param array $form_state
 * @param string $profile_name
 *
 * @return array
 */
function navigation_timing_admin_edit_profile($form, $form_state, $profile_name) {
  $res = db_query('SELECT * FROM {navigation_timing_profile} WHERE profile_name = :profile_name', array(
    ':profile_name' => $profile_name,
  ));
  $current_profile = $res->fetchAssoc();

  $form = navigation_timing_admin_create_profile($form, $form_state);
  foreach ($current_profile as $item => $value) {
    $form[$item]['#default_value'] = $value;
  }
  $form['profile_name']['#value'] = $current_profile['profile_name'];
  $form['profile_name']['#disabled'] = TRUE;
  return $form;
}

/**
 * Navigation timing profile edit form submit handler.
 *
 * @param array $form
 * @param array $form_state
 */
function navigation_timing_admin_edit_profile_submit($form, &$form_state) {
  $values = $form_state['values'];
  db_update('navigation_timing_profile')
    ->fields(array(
      'title' => $values['title'],
      'description' => $values['description'],
      'log_percent' => $values['log_percent'],
    ))
    ->condition('profile_name', $values['profile_name'], '=')
    ->execute();
  $form_state['redirect'] = 'admin/config/development/navigation_timing';
}
