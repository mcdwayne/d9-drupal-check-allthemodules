<?php

/**
 * @file
 * Sends events related to users to Mixpanel.
 */

use \Drupal\user\UserInterface;
use \Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Implements hook_user_view().
 */
function mixpanel_defaults_user_view(&$build, UserInterface $account, EntityViewDisplay $display, $view_mode) {

  $current_path = \Drupal::service('path.current')->getPath();
  $path_args = explode('/', $current_path);
  // Track visits to user pages.
  if ($view_mode == 'full' && $path_args[0] == "user" && is_numeric($path_args[1])) {
    mixpanel_track("user-view-profile", array('visited-user' => $path_args[1]));
  }
}

/**
 * Implements hook_user_insert().
 */
function mixpanel_defaults_user_insert(UserInterface $account) {
  mixpanel_track("user-inserted", array(), $account);
}

/**
 * Implements hook_user_cancel().
 */
function mixpanel_defaults_user_cancel($edit, UserInterface $account, $method) {
  mixpanel_track("user-deleted", array(), $account);
}

/**
 * Implements hook_user_login().
 */
function mixpanel_defaults_user_login(UserInterface $account) {
  mixpanel_track("user-login", array(), $account);
}

/**
 * Implements hook_user_logout().
 */
function mixpanel_defaults_user_logout(UserInterface $account) {
  mixpanel_track("user-logout", array(), $account);
}

/**
 * Submit function for contact_mail_user to track usage of the contact form.
 */
function mixpanel_defaults_contact_personal_form_submit($form, $form_state) {
  mixpanel_track("contact-form-user");
}

/**
 * Submit function for the user_profile_form to changes.
 */
function mixpanel_defaults_user_profile_form_submit($form, $form_state) {
  mixpanel_track("user-account-updated");

  // If a picture was uploaded, submit an event for that as well.
  if (!empty($form_state['values']['picture_delete']) || !empty($form_state['values']['picture_upload'])) {
    mixpanel_track("user-picture-changed");
  }
}
