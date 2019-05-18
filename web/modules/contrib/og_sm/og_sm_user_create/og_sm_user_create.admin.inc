<?php

/**
 * @file
 * Form alters implemented for the OG SM User Create module.
 */

/**
 * Form definition of the user create form.
 *
 * @param array $form
 *   The form structure.
 * @param array $form_state
 *   The form state.
 * @param object $site
 *   The site node.
 *
 * @return array
 *   The form definition.
 */
function og_sm_user_create_register_form(array $form, array &$form_state, $site) {
  global $user;
  $form['#site'] = $site;

  // Since user_register_form does several checks on the 'administer users'
  // permission we are overriding the static user_access cache here and setting
  // 'administer users' to TRUE, this allows users with elevated site roles but
  // without elevated global roles to access the user_register_form properly.
  $static = &drupal_static('user_access');
  $static[$user->uid]['administer users'] = TRUE;

  $form = user_register_form($form, $form_state);

  $form['administer_users'] = array(
    '#type' => 'value',
    '#value' => TRUE,
  );

  // Don't allow the global roles to be added from within a Site.
  $form['account']['roles']['#access'] = FALSE;

  $og_roles = og_roles('node', $site->type, $site->nid, FALSE, FALSE);
  if ($og_roles) {
    $form['account']['og_roles'] = array(
      '#type' => 'checkboxes',
      '#options' => $og_roles,
      '#title' => t('Roles'),
    );
  }

  $form['#validate'][] = 'og_sm_user_create_user_validate_membership';
  $form['#submit'][] = 'og_sm_user_create_user_add_membership';

  return $form;
}

/**
 * Validate handler for the user create form within a site context.
 *
 * @param array $form
 *   The form array.
 * @param array $form_state
 *   The current form state.
 */
function og_sm_user_create_user_validate_membership(array $form, array &$form_state) {
  $mail = $form_state['values']['mail'];
  $site = $form['#site'];

  if (empty($mail)) {
    return;
  }

  $account = user_load_by_mail($mail);
  if (!$account) {
    return;
  }

  $membership = og_get_membership('node', $site->nid, 'user', $account->uid);

  if ($membership) {
    return;
  }

  $query = array(
    'name' => $account->name,
    'roles' => $form_state['values']['og_roles'],
  );

  $membership_link = url('group/node/' . $site->nid . '/admin/people/add-user', array('query' => $query));
  drupal_set_message(
    t(
      'This user already exists. <a href="!link">Click here</a> to add the user to your group.',
      array('!link' => $membership_link)
    ),
    'warning'
  );
}

/**
 * Submit handler for the user create form within a site context.
 *
 * @param array $form
 *   The form array.
 * @param array $form_state
 *   The current form state.
 */
function og_sm_user_create_user_add_membership(array $form, array &$form_state) {
  $account = $form_state['user'];
  $site = $form_state['build_info']['args'][0];
  $group_type = 'node';
  $sid = $site->nid;

  $field_names = og_get_group_audience_fields('user', 'user', $group_type, $site->type);
  $membership = og_membership_create($group_type, $sid, 'user', $account->uid, key($field_names));
  $membership->save();

  if (!empty($form_state['values']['og_roles'])) {
    foreach ($form_state['values']['og_roles'] as $rid) {
      og_role_grant($group_type, $sid, $membership->etid, $rid);
    }
  }

  drupal_set_message(t('%user has been added to the group %group-title.', array('%user' => format_username($account), '%group-title' => $site->title)));
}
