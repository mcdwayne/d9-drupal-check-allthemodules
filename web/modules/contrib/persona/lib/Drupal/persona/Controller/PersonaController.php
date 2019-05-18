<?php

/**
 * @file
 * Contains \Drupal\persona\Controller\PersonaController.
 */

namespace Drupal\persona\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;

/**
 * Controller routines for user routes.
 */
class PersonaController extends ControllerBase {

  /**
   * Get XSRF token.
   *
   * @return array
   *   Array containing XSRF token for conversion into JSON.
   */
  function token() {
    return new JsonResponse(array('token' => persona_xsrf_token()));
  }

  /**
   * Callback function to sign in with Persona.
   */
  function signIn() {
    $request = _persona_request_variables();
    try {
      persona_check_xsrf_token($request['token']);
      $email = persona_verify($request['assertion']);
    }
    catch (\Exception $exception) {
      drupal_set_message(t("Sorry, there was a problem signing you in with Persona."), 'error');
      return new JsonResponse(array(), 401);  // Unauthorized
    }
    $transaction = db_transaction();
    // Is the browser already signed in as this user.
    if (user_is_logged_in() && (\Drupal::currentUser()->getEmail() == $email)) {
      $_SESSION['persona_sign_in'] = TRUE;
      return new JsonResponse(array(), 204);  // No Content
    }
    // Does an account with this email already exist.
    elseif ($account = user_load_by_mail($email)) {
      _persona_existing_account_sign_in($account, $email);
    }
    // Are users allows to create new accounts?
    elseif ($user_register = \Drupal::config('user.settings')->get('register')) {
      // Create an account for this user.
      $fields = array(
        'name' => _persona_extract_username($email),
        'mail' => $email,
        'access' => REQUEST_TIME,
        'status' => $user_register == USER_REGISTER_VISITORS,
        'init' => $email,
      );
      foreach ($fields as $field => &$key) {
        $key = array('und' => $key);
      }
      $account = new User($fields, 'user');
      $account->save();
      // Sign into account if it doesn't need approval.
      if ($account->isBlocked()) {
        watchdog('persona', "Account created during sign in by %email pending approval.", array('%email' => $email));
        _user_mail_notify('register_pending_approval', $account);
        drupal_set_message(t("Thank you for applying for an account. Your account is currently pending approval by the site administrator.<br />In the meantime, a welcome message with further instructions has been sent to your email address."));
        return new JsonResponse(array(), 403);  // Forbidden
      }
      else {
        watchdog('persona', "Sign in to new account by %email successful.", array('%email' => $email));
        // Sign the browser into the account.
        _persona_sign_in($account);
        // Redirect to account edit page if new accounts are set to do so.
        $data = array();
        if (\Drupal::config('persona.settings')->get('new_account_edit')) {
          return $data['redirect'] = 'user/' . $account->id() . '/edit';
        }
        return new JsonResponse($data, 201);  // Created
      }
    }
    else {
      // Visitors cannot create accounts.
      watchdog('persona', "Attempted sign in without an account by %email.", array('%email' => $email), WATCHDOG_WARNING);
      drupal_set_message(t("Only administrators are permitted to register new accounts on this website."), 'error');
      return new JsonResponse(array(), 403);  // Forbidden
    }
  }

  /**
   * Callback function to change email address with Persona.
   */
  function changeEmail() {
    $user = user_load(\Drupal::currentUser()->id());
    $request = _persona_request_variables();
    try {
      persona_check_xsrf_token($request['token']);
      $email = persona_verify($request['assertion']);
    }
    catch (\Exception $exception) {
      drupal_set_message(t("Sorry, there was a problem changing your email address with Persona."), 'error');
      return new JsonResponse(array(), 401);  // Unauthorized
    }
    $transaction = db_transaction();
    // Check if the user is using this email address in another account.
    if (($account = user_load_by_mail($email)) && $account->id() != $user->id()) {
      // Sign into existing account as it is all we can do...
      if (_persona_existing_account_sign_in($account, $email)) {
        drupal_set_message(t("You are already using %email for another account, which you have now been signed into.",
          array('%email' => $user->getEmail())), 'error');
      }
    }
    else {
      // Update account email address.
      $old_email = $user->getEmail();
      $user->setEmail($email);
      if (\Drupal::config('persona.settings')->get('email_usernames')) {
        // Update account username.
        $user->setUsername(_persona_extract_username($email));
      }
      $user->save();
      watchdog('persona', "%old_email changed to %email.", array(
        '%name' => $old_email,
        '%email' => $email,
      ));
      drupal_set_message(t("Your email address has been set to %email.", array('%email' => $email)));
      // Store in the session that the user is now signed in with Persona.
      $_SESSION['persona_sign_in'] = TRUE;
      return new JsonResponse(array(), 204);  // No Content
    }
  }

  /**
   * Callback function to sign out with Persona.
   *
   * Unlike core's sign out handler, this function does not issue an HTTP
   * redirect.
   *
   * @see user_logout()
   */
  function signOut() {
    $user = \Drupal::currentUser();
    $request = _persona_request_variables();
    try {
      persona_check_xsrf_token($request['token']);
    }
    catch (\Exception $exception) {
      return new JsonResponse(array(), 401);
    }
    if (user_is_logged_in()) {
      watchdog('user', 'Session closed for %name.', array('%name' => $user->getUsername()));
      module_invoke_all('user_logout', $user);
      // Destroy the current session, and reset $user to the anonymous user.
      session_destroy();
    }
    return new JsonResponse(array(), 204);  // No Content
  }

}
