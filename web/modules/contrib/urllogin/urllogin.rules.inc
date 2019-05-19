<?php
/**
 * @file
 * Defines rules for urllogin functionality.
 */

/**
 * Implements hook_rules_action_info.
 */
function urllogin_rules_action_info() {
  $actions = array();

  $actions['urllogin_user_link'] = array(
    'label' => t('Get user url login link.'),
    'parameter' => array(
      'user_id' => array(
        'type' => 'integer',
        'label' => t('User ID'),
        'description' => t('ID of the user to look up.'),
      ),
    ),
    'provides' => array(
      'urllogin' => array(
        'type' => 'text',
        'label' => t('Link to log user in.'),
      ),
    ),
    'group' => t('Urllogin'),
  );

  $actions['urllogin_user_token'] = array(
    'label' => t('Get user url login token.'),
    'parameter' => array(
      'user_id' => array(
        'type' => 'integer',
        'label' => t('User ID'),
        'description' => t('ID of the user to look up.'),
      ),
    ),
    'provides' => array(
      'urllogin' => array(
        'type' => 'text',
        'label' => t('Urllogin token for user.'),
      ),
    ),
    'group' => t('Urllogin'),
  );

  return $actions;
}

/**
 * Callback gets a users login link
 * @param  integer $user_id
 *   The user ID to look up the link for.
 * @return string
 *   The url found for the user.
 */
function urllogin_user_link($user_id) {
  if ($link = urllogin_retrieve($user_id)) {
    return array('urllogin' => $link->url);
  }
}

/**
 * Callback gets a users login token
 * @param  integer $user_id
 *   The user ID to look up the link for.
 * @return string
 *   The token found for the user.
 */
function urllogin_user_token($user_id) {
  if ($link = urllogin_retrieve($user_id)) {
    return array('urllogin' => $link->token);
  }
}
