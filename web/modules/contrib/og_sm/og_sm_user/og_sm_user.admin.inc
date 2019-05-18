<?php
/**
 * @file
 * Administrative pages for the OG SM User module.
 */

/**
 * Global feature settings form.
 *
 * @param array $form
 *   The form structure.
 * @param array $form_state
 *   The form state.
 *
 * @return array
 *   The form structure.
 */
function og_sm_user_admin_feature(array $form, array &$form_state) {
  $form['info'] = array(
    '#markup' => t('Default settings for the User feature.'),
    '#prefix' => '<p>',
    '#suffix' => '</p>',
  );

  $defaults = array(
    'og_sm_user_profile_editable' => variable_get('og_sm_user_profile_editable', TRUE),
  );

  return system_settings_form(_og_sm_user_admin_form($form, $defaults));
}

/**
 * Site specific settings form.
 *
 * @param array $form
 *   The form structure.
 * @param array $form_state
 *   The form state.
 * @param object $site
 *   The Site object.
 *
 * @return array
 *   The form structure.
 */
function og_sm_user_og_admin_feature(array $form, array &$form_state, $site) {
  if (module_exists('og_sm_feature')) {
    og_sm_feature_set_breadcrumb($site);
  }
  $sid = $site->nid;

  $form['info'] = array(
    '#markup' => t('Site settings for the User feature.'),
    '#prefix' => '<p>',
    '#suffix' => '</p>',
  );
  $global_defaults = array(
    'og_sm_user_profile_editable' => variable_get('og_sm_user_profile_editable', TRUE),
  );
  $defaults = array(
    'og_sm_user_profile_editable' => og_sm_variable_get($sid, 'og_sm_user_profile_editable', $global_defaults['og_sm_user_profile_editable']),
  );

  $form = _og_sm_user_admin_form($form, $defaults);

  return og_sm_variable_settings_form($form, $form_state, $site);
}

/**
 * Shared form elements for the Global & OG settings.
 *
 * @param array $form
 *   The form structure.
 * @param array $defaults
 *   The default values for the form.
 *
 * @return array
 *   The form structure.
 */
function _og_sm_user_admin_form($form, $defaults) {
  $form['og_sm_user_profile_editable'] = array(
    '#type' => 'checkbox',
    '#title' => t('Allow users to edit their profile'),
    '#description' => t('When checked, users will be able to edit their Site profile.'),
    '#default_value' => $defaults['og_sm_user_profile_editable'],
  );

  return $form;
}
