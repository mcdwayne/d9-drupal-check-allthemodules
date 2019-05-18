<?php
/**
 * @file
 * Pages for the og_sm_user module.
 */

/**
 * Returns sections info to build the user profile page.
 *
 * @return array
 *   An array of section info arrays, each section info array can contain the
 *   following attributes:
 *   - "render callback": The function to call to display the section on the
 *     profile page.
 *   - "weight": (optional) The weight of the section.
 */
function og_sm_user_get_sections_info() {
  // Check if we already cached the information.
  $cache = cache_get(__FUNCTION__);
  if ($cache) {
    return $cache->data;
  }

  $sections_info = module_invoke_all('og_sm_user_profile_info');
  drupal_alter('og_sm_user_profile_info', $sections_info);
  uasort($sections_info, 'drupal_sort_weight');

  // Store in cache.
  cache_set(__FUNCTION__, $sections_info);

  // Return data.
  return $sections_info;
}

/**
 * The site user profile page.
 *
 * @param object $site
 *   The site node.
 * @param object $account
 *   (optional) The user object.
 *
 * @return array
 *   Renderable array of profile data.
 */
function og_sm_user_page($site, $account = NULL) {
  global $user;
  if (!isset($account)) {
    $account = $user;
  }

  $path = array(
    l(format_username($account), 'group/node/' . $site->nid . '/profile/' . $account->uid),
  );
  og_sm_set_breadcrumb($site, $path);

  $sections_info = og_sm_user_get_sections_info();

  $sections = array();
  foreach ($sections_info as $section_info) {
    if (!isset($section_info['render callback'])) {
      continue;
    }
    $render_callback = $section_info['render callback'];
    if (!function_exists($render_callback)) {
      continue;
    }
    $sections[] = call_user_func($render_callback, $site, $account);
  }
  return array(
    '#theme' => 'og_sm_user_page',
    '#user' => $account,
    '#sections' => $sections,
  );
}

/**
 * Base profile form.
 *
 * @param array $form
 *   The form structure.
 * @param array $form_state
 *   The current form state.
 * @param object $site
 *   The site node.
 * @param object $account
 *   (optional) The user object.
 *
 * @return array
 *   The form structure.
 */
function og_sm_user_edit_form($form, &$form_state, $site, $account = NULL) {
  global $user;
  if (!isset($account)) {
    $account = $user;
  }

  // Breadcrumbs.
  og_sm_set_breadcrumb($site, array(
    l(format_username($account), 'group/node/' . $site->nid . '/profile/' . $account->uid),
    l(t('Edit'), 'group/node/' . $site->nid . '/profile/' . $account->uid . '/modify'),
  ));

  $form_state['user'] = $account;
  $form_state['site'] = $site;

  // Every module with profile data should alter this form and add their own
  // elements with own validate and submit handlers.
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
    '#weight' => 100,
  );
  return $form;
}

/**
 * Page callback for the logout link.
 *
 * This is page callback for the Site-aware logout link. It does exactly the
 * same as the normal user/logout path, but has a Site context active. It does
 * not log a user out from a specific Site, but from all Sites at once.
 */
function og_sm_user_logout_page() {
  menu_execute_active_handler('user/logout');
}
