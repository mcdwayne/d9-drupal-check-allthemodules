<?php /**
 * @file
 * Contains \Drupal\username_check\Controller\DefaultController.
 */
namespace Drupal\username_check\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\SafeMarkup;
// Updated after https://www.drupal.org/node/2488054
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Default controller for the username_check module.
 */
class DefaultController extends ControllerBase {

  public function username_check_callback() {
    $output = [];
    $username = $_GET['username'];
    $ret = user_validate_name($username);
    if ($ret) {
      $output['allowed'] = FALSE;
      $output['msg'] = $ret;
    }
    else {
      $ret = user_is_blocked($username);
      if ($ret) {
        $output['allowed'] = FALSE;
        $output['msg'] = t('The username %username is not allowed.', [
          '%username' => $username
          ]);
      }
      else {
        //$username = check_plain($username);
        $username = SafeMarkup::checkPlain($username);
        $ret = $this->_username_check_is_user_exists($username);
        if ($ret) {
          $url = Url::fromRoute("user.page");
          $login_link = \Drupal::l(t('login'), $url);
          $forgot_link = \Drupal::l(t(' password'), $url);
          $output['allowed'] = FALSE;
          $output['msg'] = t('The username %username is already taken. If this is you, please ' . $login_link . ' or if you\'ve forgotten your password, ' . $forgot_link . '.', [
            '%username' => $username
            ]);
        }
        else {
          $output['allowed'] = TRUE;
        }
      }
    }
    return new JsonResponse($output);
  }

/**
 * Query user table to check if such username is already exists.
 */
function _username_check_is_user_exists($username) {
  return db_query("SELECT COUNT(u.name) count FROM {users_field_data} u WHERE LOWER(u.name) = LOWER(:username)", array(':username' => $username))->fetchField();
}

  public function username_check_profile_callback() {
    $output = [];

    $username = $_GET['profile'];
    $ret = user_validate_name($username);
    if ($ret) {
      $output['allowed'] = FALSE;
      $output['msg'] = $ret;
    }
    else {
      $ret = user_is_blocked($username);
      if ($ret) {
        $output['allowed'] = FALSE;
        $output['msg'] = t('The username %username is not allowed.', [
          '%username' => $username
          ]);
      }
      else {
        $username = SafeMarkup::checkPlain($username);
        // check to see if this username is the current users username
        $ret = $this->_username_check_is_current_user($username);
        print_r($ret);die();
        if ($ret==0) {
          $output['allowed'] = TRUE;
          $output['msg'] = t('The username %username is your username.', [
            '%username' => $username
            ]);
        }
        else {
          $ret = $this->_username_check_is_user_exists($username);
          if ($ret) {
            $output['allowed'] = FALSE;
            $output['msg'] = t('The username %username is already taken.', [
              '%username' => $username
              ]);
          }
          else {
            $output['allowed'] = TRUE;
          }
        }
      }
    }
    return new JsonResponse($output);
  }

/**
 * Query user table to check if this is the current user.
 */
function _username_check_is_current_user($username) {
  $account = \Drupal::currentUser();
  return db_query("SELECT COUNT(u.name) count FROM {users_field_data} u WHERE LOWER(u.name) = LOWER(:username) AND u.uid =" . $account->id(), array(':username' => $username))->fetchField();
}

  public function username_check_mail_callback() {
    $output = [];
    $mail = $_GET['mail'];
    $ret = valid_email_address($mail);
    if (!$ret) {
      $output['msg'] = $ret;
    }
    else {
      $ret = user_is_blocked($mail);
      $output['allowed'] = FALSE;
      if ($ret) {
        $output['allowed'] = FALSE;
        $output['msg'] = t('The e-mail address %mail is not allowed.', [
          '%mail' => $mail
          ]);
      }
      else {
        $mail = SafeMarkup::checkPlain($mail);
        $ret = $this->_username_check_is_mail_exists($mail);

        if ($ret) {
          $url = Url::fromRoute("user.page");
          $login_link = \Drupal::l(t('login'), $url);
          $forgot_link = \Drupal::l(t(' password'), $url);
          $output['allowed'] = FALSE;
          $output['msg'] = t('The e-mail address %mail is already in the system, you have an account here. Please ' . $login_link . ' or if you\'ve forgotten your password, ' . $forgot_link . '.', [
            '%mail' => $mail
            ]);
        }
        else {
          $output['allowed'] = TRUE;
        }
      }
    }

    return new JsonResponse($output);
  }

/**
 * Query user table to check if such mail is already exists.
 */
public function _username_check_is_mail_exists($mail) {
  return db_query("SELECT COUNT(u.mail) count FROM {users_field_data} u WHERE LOWER(u.mail) = LOWER(@mail)", array('@mail' => $mail))->fetchField();
}

}
