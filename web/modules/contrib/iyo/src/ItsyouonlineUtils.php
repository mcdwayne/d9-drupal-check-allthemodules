<?php

namespace Drupal\itsyouonline;

use Symfony\Component\HttpFoundation\RedirectResponse;

class ItsyouonlineUtils {
  const USER_INFO_URL = 'https://itsyou.online/api/users/{{USERNAME}}/info?access_token={{TOKEN}}';

  public static function logger() {
    return \Drupal::logger('itsyouonline');
  }

  public static function session() {
    $session = \Drupal::service('session');
    $account = \Drupal::currentUser();

    if ($account->isAnonymous() &&
        !$session->isStarted()) {
      $session->migrate();
    }

    return $session;
    /*
    // @TODO
    $session = \Drupal::request()->getSession();
    $account = \Drupal::currentUser();

    if ($account->isAnonymous() &&
        !$session->isStarted() &&
        !isset($_SESSION['session_started'])) {
        $sessionManager = \Drupal::getContainer()->get('session_manager');
        $_SESSION['session_started'] = true;
        $sessionManager->start();
    }

    return $session;
    */
  }

  public static function  generateUsernameEmail($itsyouonline_uid) {
    $config = \Drupal::config('itsyouonline.account');

    // register a new user
    $query = db_select('itsyouonline_user_data', 'iud')
      ->fields('iud', array('attribute_key', 'attribute_value'))
      ->condition('itsyou_uid', $itsyouonline_uid);

    $itsyou_details = $query->execute()->fetchAllKeyed(0, 1);
    
    $return = array(
      'email' => isset($itsyou_details['email']) ? $itsyou_details['email'] : NULL
    );

    $param_replacement = array();
    $param_placeholder = array();

    foreach (_itsyouonline_scope_params_attributes() as $param) {
      if (!empty($itsyou_details[$param])) {
        $param_replacement[] = trim($itsyou_details[$param]);
      } else {
        $param_replacement[] = '';
      }

      $param_placeholder[] = '{itsyou.' . $param . '}';
    }

    $drupal_username = trim(str_replace(
      $param_placeholder,
      $param_replacement,
      $config->get('username_pattern')
    ));

    $return['username'] = $drupal_username ? $drupal_username : null;

    return $return;
  }

  public static function getItsyouUserInfo($username, $authData) {
    $getInfoRestUrl = self::USER_INFO_URL;
    $getInfoRestUrl = str_replace("{{USERNAME}}", $username, $getInfoRestUrl);
    $getInfoRestUrl = str_replace("{{TOKEN}}", $authData->access_token, $getInfoRestUrl);

    $userInfo = NULL;

    $httpClient = \Drupal::httpClient();
    try {
      $response = $httpClient->request('GET', $getInfoRestUrl);
      $result = $response->getBody()->getContents();
      $statusCode = $response->getStatusCode();
    } catch (\Exception $e) {
      watchdog_exception('itsyouonline', $e->getMessage());

      return $userInfo;
    }

    switch ($statusCode) {
      case 200:
      case 301:
      case 302:
        $resp = json_decode($result, TRUE);

        if (json_last_error()) {

          self::logger()->error(
            t('Get user info error - error while loading itsyou data for user {username}'),
            array('username' => $username)
          );
        } else {
          $userInfo = $resp;
        }

        break;

      default:
        self::logger()->error(
          t('Get user info error - error while loading itsyou data for user {username}'),
          array('username' => $username)
        );
    }

    return $userInfo;
  }

  public static function processIntegration($type, $info, $auth) {
    $config = \Drupal::config('itsyouonline.account');

    $itsyouonline_uid = $info['username'];

    // Update the user data in the database.
    db_delete('itsyouonline_user_data')
      ->condition('itsyou_uid', $itsyouonline_uid)
      ->execute();

    // Insert the user data using a multi-insert query.
    $query = db_insert('itsyouonline_user_data')
      ->fields(array('itsyou_uid', 'attribute_key', 'attribute_value'));

    foreach ($info as $key => $value) {
      $query->values(array(
        'itsyou_uid' => $itsyouonline_uid,
        'attribute_key' => $key,
        'attribute_value' => $value,
      ));
    }

    $query->execute();

    // Check whether this end-user is already linked to a Drupal user.
    $query = db_select('itsyouonline_user_link', 'iul')
      ->condition('iul.itsyou_uid', $itsyouonline_uid)
      ->fields('iul', array('drupal_uid'));
    $result = $query->countQuery()->execute();

    if ($result->fetchField() == 1) {
      // The itsyou.online end-user is already linked to an existing Drupal
      // user, let's authenticate the Drupal user.
      $uid = $query->execute()->fetchField();
      $account = user_load($uid);

      // Store new auth data
      db_update('itsyouonline_user_link')
      ->fields(array(
        'auth_data' => serialize($auth),
        'updated' => REQUEST_TIME
      ))
      ->condition('itsyou_uid', $itsyouonline_uid)
      ->execute();

      // The following authentication flow is copied from drupal RegisterForm.
      // if (!$userModuleConfig->get('user_email_verification') || $account->login) {
      if (!(\Drupal::config('user.settings')->get('verify_mail')) || $account->isActive()) {

        user_login_finalize($account);

        $resp = new RedirectResponse(\Drupal::url('<front>'));
        return $resp->send();
      }
      else {
        drupal_set_message(t('You must validate your email address for this account before logging in via itsyou.online.'), 'warning');
      }

      // Redirect the user to the front page.
      $resp = new RedirectResponse(\Drupal::url('<front>'));
      return $resp->send();

    }
    else {
      $account = \Drupal::currentUser();

      // The end-user is not yet linked to an existing Drupal
      // user.
      if (!$account->isAnonymous() && $type == 'link') {
        //@see: Cleanup the session data: the link code is already consumed.

        // Link to currently logged on user.
        $fields = array(
          'drupal_uid' => $account->id(),
          'itsyou_uid' => $itsyouonline_uid,
          'auth_data' => serialize($auth),
          'updated' => REQUEST_TIME
        );
        try {
          db_insert('itsyouonline_user_link')->fields($fields)->execute();
        }
        catch (\Exception $e) {
          watchdog_exception('itsyouonline', $e);
          drupal_set_message(t('An error occurred while linking the user to itsyou.online'), 'error');
          $resp = new RedirectResponse('/user');
          return $resp->send();
        }

        drupal_set_message(t('The user has been successfully linked to itsyou.online'));

        //@see: should connect back to org?

        // Redirect to the user edit form
        $resp = new RedirectResponse('/user');
        return $resp->send();
      }
      else {

        if ($type && ($type == 'register')) {
          $resp = new RedirectResponse(\Drupal::url('itsyouonline.link_new_user'));
          return $resp->send();
        }
        else {
          if ($config->get('skip_link_wizard') == 0) {
            $resp = new RedirectResponse(\Drupal::url('itsyouonline.link'));
            return $resp->send();
          } else {
            $resp = new RedirectResponse(\Drupal::url('itsyouonline.link_new_user'));
            return $resp->send();
          }
        }
      }
    }
  }


}