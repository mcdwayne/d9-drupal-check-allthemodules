<?php

/**
 * @file
 * User page callbacks for the Droogle Hangout module.
 */

use Drupal\Core\Database\Connection;

/**
 * Preprocesses variables for invite button.
 */
function template_preprocess_droogle_hangout_invite_button(&$variables) {
  $email = $variables['email'];
  $fullname = $variables['fullname'];
  $src = $variables['src'];
  if ($variables['text'] == TRUE) {
    $text = 'Hangout';
  }
  else {
    $text = '';
  }
  if (is_null($fullname) || $fullname == '') {
    $fullname = $email;
  }
  if (is_null($src) || $src == '') {
    $src = ""; /* place default value here sometime later */
  }
}

/**
 * Preprocesses variables for invite button.
 */
function template_preprocess_droogle_hangout_contact_me_popup(&$variables) {
  if (!module_exists('jabber')) {
    $variables['chat'] = FALSE;
    $variables['hangout'] = FALSE;
  }

  $uid = $variables['uid'];

  $sql = Database::getConnection()->select('users', 'u');
  // Get Fullname from Realname module if installed.
  if (module_exists('realname')) {
    $sql->leftJoin('realname', 'r', 'r.uid = u.uid');
    $sql->fields('realname', array('realname'));
  }
  // If not using Realname module check if first and last name
  if (!module_exists('realname') &&
    Database::getConnection()->schema()->tableExists('field_data_field_first_name') &&
    Database::getConnection()->schema()->tableExists('field_data_field_last_name')) {
    $sql->leftJoin('field_data_field_first_name', 'fn', 'fn.entity_id = u.uid');
    $sql->leftJoin('field_data_field_last_name', 'ln', 'ln.entity_id = u.uid');
    $sql->fields('first', array('field_first_name_value'));
    $sql->fields('last', array('field_last_name_value'));
  }
  $sql->fields('u', array('name', 'mail'));
  $and = new Condition('AND');
  $and->condition('u.uid', $uid);
  $sql->condition($and);

  $result = $sql->execute();

  foreach ($result as $row) {
    $username = $row->name;
    if (module_exists('realname')) {
      $fullname = $row->realname;
    }
    if (!module_exists('realname') &&
      Database::getConnection()->schema()->tableExists('field_data_field_first_name') &&
      Database::getConnection()->schema()->tableExists('field_data_field_last_name')) {
      $firstname = $row->first;
      $lastname = $row->last;
      $fullname = $firstname . ' ' . $lastname;
    }
    else {
      $fullname = '';
    }
    $user_mail = $row->mail;
  }

  $variables['contact_me_popup'] = theme('droogle_hangout_invite_button', array('email' => $user_mail, 'fullname' => $fullname));
}

/**
 * Preprocesses variables for invite button.
 */
function template_preprocess_droogle_hangout_invitee_window(&$variables) {
  $variables['scheduler'] = FALSE;

  if ($path = libraries_get_path('datetimepicker')) {
    $variables['scheduler'] = TRUE;
  }
}
