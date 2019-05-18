<?php

namespace Drupal\secure_password_reset\Controller;

use Drupal\Core\Form\FormStateInterface;

/**
 * Overides the user password reset form.
 */
class SecurePasswordResetController {

  /**
   * Custom password reset validate function.
   */
  public static function validateForm(array &$form, FormStateInterface $form_state) {
    $name = trim($form_state->getValue('name'));
    // Try to load by email.
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['mail' => $name]);
    if (empty($users)) {
      // No success, try to load by name.
      $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => $name]);
    }

    $account = reset($users);

    if ($account && $account->id()) {
      // Blocked accounts cannot request a new password.
      if (!$account->isActive()) {
        \Drupal::logger('secure_password_reset')->notice('%name is blocked or has not been activated yet.', ['%name' => $name]);
      }
      else {
        $form_state->setValueForElement(['#parents' => ['account']], $account);
      }
    }
    else {
      \Drupal::logger('secure_password_reset')->notice('%name is not recognized as a username or an email address.', ['%name' => $name]);
    }

  }

  /**
   * Custom password reset submit function.
   */
  public static function submitForm(array &$form, FormStateInterface $form_state) {

    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();

    $account = $form_state->getValue('account');
    if ($account) {
      // Mail one time login URL and instructions using current language.
      $mail = _user_mail_notify('password_reset', $account, $langcode);
      if (!empty($mail)) {
        \Drupal::logger('secure_password_reset')->notice('Password reset instructions mailed to %name at %email.', ['%name' => $account->getUsername(), '%email' => $account->getEmail()]);
      }
    }
    drupal_set_message(t('If provided information is valid then further information has been sent to your email address.'));
    $form_state->setRedirect('user.page');
  }

}
