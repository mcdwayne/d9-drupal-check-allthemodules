<?php

namespace Drupal\itsyouonline\Controller;

use Drupal\itsyouonline\ItsyouonlineUtils;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormState;
use Symfony\Component\HttpFoundation\RedirectResponse;


/**
 * Itsyouonline integration controller.
 */
class ItsyouonlineIntegrationController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function newUser() {

    // @TODO
    $tempstore = ItsyouonlineUtils::session();
    $config = \Drupal::config('itsyouonline.account');
    $userConfig = \Drupal::config('user.settings');
    $account = \Drupal::currentUser();

    $itsyouonline_uid = $tempstore->get('itsyouonline_uid');
    $itsyouonline_auth = $tempstore->get('itsyouonline_auth');

    if (!$itsyouonline_uid) {
      ItsyouonlineUtils::logger()->error(t('Link new user error - missing itsyou session user'));
      return array(
        '#type' => 'markup',
        '#markup' => t('Could not find valid itsyou.online user account!')
      );
    }

    $register_access = ($account->isAnonymous() &&
      (($userConfig->get('register') ==  USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL) ||
        ($userConfig->get('register') ==  USER_REGISTER_VISITORS))
    );

    if ($register_access && ($config->get('auto_create_account') == 0)) {
      $entity = \Drupal::entityTypeManager()->getStorage('user')->create(array());
      $formObject = \Drupal::entityTypeManager()
        ->getFormObject('user', 'itsyou_register')
        ->setEntity($entity);
      $form = \Drupal::formBuilder()->getForm($formObject);
      return $form;
    }

    elseif ($register_access && ($config->get('auto_create_account') == 1)) {
      // register a new user

      $create_details = ItsyouonlineUtils::generateUsernameEmail($itsyouonline_uid);
      $drpal_email = $create_details['email'];
      $drpal_username = $create_details['username'];

      // Extract the domain name of this website. The domain name will be used
      // when a fake email has to be created.
      global $base_url;
      $base_url_parts = explode("/", $base_url);
      $email_domain = $base_url_parts[2];
      if ($email_domain === 'localhost') {
        $email_domain = $email_domain . '.site';
      }

      // Check if a user already exists with this email address. Only do this
      // check if the email address was received from itsyou.online
      // Note: when constructing an email address it must be 254 characters or
      //       less since the mail column in the {users} table has a maximum
      //       length of 254 characters.

      $email_exists = true;

      if ($drpal_email) {
        $email_exists = ((bool) db_select('users_field_data', 'users')->fields('users', array('uid'))->condition('mail', db_like($drpal_email), 'LIKE')->range(0, 1)->execute()->fetchField());
      }

      if (empty($drpal_email) || $email_exists) {
        if ($config->get('auto_create_account_email', 'random') == 'form') {

          $entity = \Drupal::entityTypeManager()->getStorage('user')->create(array());
          $formObject = \Drupal::entityTypeManager()
            ->getFormObject('user', 'itsyou_register')
            ->setEntity($entity);
          $form = \Drupal::formBuilder()->getForm($formObject);
          $form->setOperation('register');

          return $form;
          // Return a form that asks to enter the users email address.
          $register_form = new \Drupal\itsyouonline\UserRegistrationForm();
          $register_form->setOperation('register');

          $form = \Drupal::formBuilder()->getForm($register_form);

          return \Drupal::service('renderer')->render($form);
        } else {
          $drpal_email = substr($itsyouonline_uid . '@' . $email_domain, 0, 254);
        }
      }

      $username_exists = true;
      if ($drpal_username) {
        $username_exists = ((bool) db_select('users_field_data', 'users')->fields('users', array('uid'))->condition('name', db_like($drpal_username), 'LIKE')->range(0, 1)->execute()->fetchField());
      }

      $is_username_as_email = false;

      if (empty($drpal_username) || $username_exists) {
        if ($config->get('auto_create_account_username', 'random') == 'form') {

          $entity = \Drupal::entityTypeManager()->getStorage('user')->create(array());
          $formObject = \Drupal::entityTypeManager()
            ->getFormObject('user', 'itsyou_register')
            ->setEntity($entity);
          $form = \Drupal::formBuilder()->getForm($formObject);
          return $form;
        }
      }

      while ($email_exists) {
        $email_exists = ((bool) db_select('users_field_data', 'users')->fields('users', array('uid'))->condition('mail', db_like($drpal_email), 'LIKE')->range(0, 1)->execute()->fetchField());
        if ($email_exists) {
          $drpal_email = substr($itsyouonline_uid . '@' . user_password(4) . '.' . $email_domain, 0, 254);
        }
      }

      if (empty($drpal_username)) {
        $drpal_username = $drpal_email;
        $is_username_as_email = true;
      }

      while ($username_exists) {
        $username_exists = ((bool) db_select('users_field_data', 'users')->fields('users', array('uid'))->condition('name', db_like($drpal_username), 'LIKE')->range(0, 1)->execute()->fetchField());
        if ($username_exists) {
          if ($is_username_as_email) {
            $drpal_username = substr($itsyouonline_uid . '@' . user_password(4) . '.' . $email_domain, 0, 254);
          } else {
            $drpal_username = substr($itsyouonline_uid . user_password(4), 0, 254);
          }
        }
      }

      $password = user_password(32);
      $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

      $user = \Drupal\user\Entity\User::create();
      $user->setPassword($password);
      // $user->enforceIsNew();
      $user->setEmail($drpal_email);
      $user->setUsername($drpal_username);//This username must be unique and accept only a-Z,0-9, - _ @ .
      $user->set('init', 'mail');
      $user->set('langcode', $language);
      $user->set('preferred_langcode', $language);
      $user->set('preferred_admin_langcode', $language);
      $user->activate();

      //Save user account
      $user->save();

      // Link the Drupal user to the itsyou.online
      $fields = array(
        'drupal_uid' => $user->id(),
        'itsyou_uid' => $itsyouonline_uid,
        'auth_data' => serialize($itsyouonline_auth),
        'updated' => REQUEST_TIME
      );

      try {
        db_insert('itsyouonline_user_link')->fields($fields)->execute();
      }
      catch (\Exception $e) {
        watchdog_exception('itsyouonline', $e);
        drupal_set_message(t('An error occurred while linking the user to itsyou.online.'), 'error');
        return;
      }

      // No email verification required; log in user immediately.
      _user_mail_notify('register_no_approval_required', $user);
      user_login_finalize($user);
      drupal_set_message($this->t('Registration successful. You are now logged in.'));

      // Redirect the user to the front page.
      return new RedirectResponse(\Drupal::url('<front>'));
    }
    else {
      $output = t("According to this website's policy it is not allowed to create new accounts.");
    }

    return $output;
  }

  public function existingUser() {

    // Put form in variable before passing to drupal_render. Else we get a
    // strict warning: Only variables should be passed by reference.

    $form = \Drupal::formBuilder()->getForm(\Drupal\itsyouonline\Form\UserLoginForm::class);
   // $output .= \Drupal::servicse('renderer')->render($form);

    return $form;
  }

}
