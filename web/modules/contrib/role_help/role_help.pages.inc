<?php
/**
 * @file
 * Contains page callbacks for the Role help module.
 */

/**
 * Menu callback for the role help page.
 *
 * Access control admins are shown all roles.
 */
//function role_help_page() {
//  global $user;
//
//  if (user_access('administer permissions')) {
//    $header = t('You are an access control administrator. All roles with help text are listed below:');
//
//    $result = db_query("SELECT r.name, h.help FROM {role} r INNER JOIN {role_help} h ON r.rid = h.rid WHERE h.help <> '' AND h.help IS NOT NULL");
//  }
//  else {
//    $header = t('Your user account is enabled to do the following on this site:');
//
//    $user_rids = array_keys($user->roles);
//    $place_holder_data = array();
//    $place_holder_vals = array();
//    foreach ($user_rids as $index => $user_rid) {
//      $place_holder_data[] = ':user_rid_' . $index;
//      $place_holder_vals[':user_rid_' . $index] = $user_rid;
//    }
//    $placeholders = implode(',', $place_holder_data);
//    $result = db_query("SELECT r.name, h.help FROM {role} r INNER JOIN {role_help} h ON r.rid = h.rid WHERE r.rid IN ($placeholders) AND h.help <> '' AND h.help IS NOT NULL ORDER BY r.rid", $place_holder_vals);
//  }
//
//  $role_help_items = array();
//  while ($item = $result->fetchAssoc()) {
//    $role_help_items[] = theme('role_help_section', array('name' => $item['name'], 'description' => $item['help']));
//  }
//  if (!count($role_help_items)) {
//    $header = t('No role help text has been set.');
//  }
//
//  $output = "<p>$header</p>";
//  $output .= theme('item_list', array('items' => $role_help_items));
//
//  return $output;
//}
//
///**
// * Display the role help admin settings page.
// *
// * Set the description for the anonymous and authorized users here.
// */
//function role_help_admin_settings() {
//  $descriptions = array(
//    DRUPAL_ANONYMOUS_RID => '',
//    DRUPAL_AUTHENTICATED_RID => '',
//  );
//  $summary = array(
//    DRUPAL_ANONYMOUS_RID => '',
//    DRUPAL_AUTHENTICATED_RID => '',
//  );
//  $result = db_query('SELECT * FROM {role_help} WHERE rid IN (:anon_rid, :auth_rid)', array(
//    ':anon_rid' => DRUPAL_ANONYMOUS_RID,
//    ':auth_rid' => DRUPAL_AUTHENTICATED_RID,
//  ));
//  while ($item = $result->fetchAssoc()) {
//    $descriptions[$item['rid']] = $item['help'];
//    $summary[$item['rid']] = $item['summary'];
//  }
//
//  $form['anonymous_help'] = array(
//    '#type' => 'textarea',
//    '#title' => t('Anonymous user'),
//    '#default_value' => $descriptions[DRUPAL_ANONYMOUS_RID],
//    '#description' => t('A description of what an anonymous user can accomplish. You should only set this if anonymous users have rights beyond accessing content. If non-empty, this will be shown to anonymous users on the site help page.'),
//  );
//  $form['authenticated_help'] = array(
//    '#type' => 'textarea',
//    '#title' => t('Authenticated user'),
//    '#default_value' => $descriptions[DRUPAL_AUTHENTICATED_RID],
//    '#description' => t('A description of what an authenticated user can accomplish. If non-empty, this will be shown to authenticated users on the site help page. You should only'),
//  );
//
//  $form['anonymous_summary'] = array(
//    '#type' => 'textfield',
//    '#title' => t('Anonymous user summary'),
//    '#default_value' => $summary[DRUPAL_ANONYMOUS_RID],
//    '#description' => t('Summary of what permissions are assigned to this role, and how the role is used. This will be shown on the role list and user profile edit pages.'),
//  );
//  $form['authenticated_summary'] = array(
//    '#type' => 'textfield',
//    '#title' => t('Authenticated user summary'),
//    '#default_value' => $summary[DRUPAL_AUTHENTICATED_RID],
//    '#description' => t('Summary of what permissions are assigned to this role, and how the role is used. This will be shown on the role list and user profile edit pages.'),
//  );
//
//  $form['role_help_format'] = array(
//    '#type' => 'text_format',
//    '#base_type' => 'value',
//    '#default_value' => '',
//    '#format' => variable_get('role_help_format', NULL),
//  );
//
//  $form['submit'] = array(
//    '#type' => 'submit',
//    '#value' => t('Save configuration'),
//  );
//
//  return $form;
//}
//
///**
// * Submit function for admin settings form.
// *
// * Save or update text, delete if blanked.
// */
//function role_help_admin_settings_submit($form, &$form_state) {
//  variable_set('role_help_format', $form_state['values']['role_help_format']['format']);
//
//  foreach (array(
//             DRUPAL_ANONYMOUS_RID => array('help' => 'anonymous_help', 'summary' => 'anonymous_summary'),
//             DRUPAL_AUTHENTICATED_RID => array('help' => 'authenticated_help', 'summary' => 'authenticated_summary'),
//           ) as $rid => $role) {
//    db_query('DELETE FROM {role_help} WHERE rid = :rid', array(':rid' => $rid));
//    if ($form_state['values'][$role['help']] != '' || $form_state['values'][$role['summary']] != '') {
//      db_query("INSERT INTO {role_help} (rid, help, summary) VALUES (:rid, :help_text, :summary)", array(':rid' => $rid, ':help_text' => $form_state['values'][$role['help']], ':summary' => $form_state['values'][$role['summary']]));
//    }
//  }
//  // Rebuild menus so anon users either gain or lose the site help menu item.
//  menu_rebuild();
//
//  drupal_set_message(t('The role help settings have been saved.'));
//}
