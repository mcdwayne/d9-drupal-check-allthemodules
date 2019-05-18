<?php

namespace Drupal\cognito\Form\Email;

use Drupal\cognito\Form\CognitoAccountForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Override default profile form.
 */
class ProfileForm extends CognitoAccountForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['account']['name']['#access'] = FALSE;
    $form['#validate'][] = '::validatePasswordChange';
    $form['#validate'][] = '::validateAccountStatus';
    $form['#validate'][] = '::validateEmailChange';
    return $form;
  }

  /**
   * Validates and updates the email address.
   */
  public function validateEmailChange(array &$form, FormStateInterface $form_state) {
    $current_email = strtolower($this->entity->getEmail());
    $new_email = strtolower($form_state->getValue('mail'));

    if ($current_email !== $new_email) {
      if ((!$current_password = $form_state->getValue('current_pass')) && !$this->currentUser()->hasPermission('administer users')) {
        $form_state->setErrorByName('current_pass', $this->t('You must provide your existing password to update your email'));
        return;
      }

      $cognitoResult = $this->cognito->adminUpdateUserAttributes($current_email, 'email', $new_email);

      if ($cognitoResult->hasError()) {
        $form_state->setErrorByName('mail', $cognitoResult->getError());
      }
    }
  }

  /**
   * Validates and updates the password field.
   */
  public function validatePasswordChange(array &$form, FormStateInterface $form_state) {
    $email = strtolower($form_state->getValue('mail'));
    $oldPassword = trim($form_state->getValue('current_pass'));
    $newPassword = trim($form_state->getValue('pass'));

    if ($newPassword && !$oldPassword) {
      $form_state->setErrorByName('current_pass', $this->t('You must provide your existing password to set a new password'));
    }

    if ($error = $this->handlePasswordUpdate($email, $oldPassword, $newPassword)) {
      $form_state->setErrorByName(NULL, $error);
    }
  }

  /**
   * Validates and updates the account status.
   */
  public function validateAccountStatus(array &$form, FormStateInterface $form_state) {
    // If they changed their account status, then update the Cognito account
    // status.
    $email = strtolower($form_state->getValue('mail'));
    $is_currently_blocked = $this->entity->isBlocked();
    $submitted_status_blocked = $form_state->getValue('status') == 0;
    if ($error = $this->calculateUserStatus($email, $is_currently_blocked, $submitted_status_blocked)) {
      $form_state->setErrorByName(NULL, $error);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $authData = $this->authmap->getAuthData($this->entity->id(), 'cognito');
    $this->authmap->save($this->entity, 'cognito', $this->entity->getEmail(), $authData);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->messenger()->addMessage(t('Your account has been updated'));
    return parent::save($form, $form_state);
  }

  /**
   * Re-calculates a users blocked status and sends it to Cognito.
   *
   * @param string $username
   *   The user we're checking.
   * @param bool $is_currently_blocked
   *   Is the user currently blocked.
   * @param bool $submitted_status_blocked
   *   The submitted value from the form.
   *
   * @return string|null
   *   The error if any or null.
   */
  protected function calculateUserStatus($username, $is_currently_blocked, $submitted_status_blocked) {
    if ($is_currently_blocked !== $submitted_status_blocked) {

      if ($submitted_status_blocked) {
        $result = $this->cognito->adminDisableUser($username);

        if ($result->hasError()) {
          return $result->getError();
        }

        $this->messenger()->addMessage($this->t('Account disabled in Cognito'));
      }
      else {
        $result = $this->cognito->adminEnableUser($username);

        if ($result->hasError()) {
          return $result->getError();
        }

        $this->messenger()->addMessage($this->t('Account enabled in Cognito'));
      }
    }
  }

  /**
   * Handle changing the users password.
   *
   * @param string $email
   *   The users email.
   * @param string $oldPassword
   *   The old password.
   * @param string $newPassword
   *   The new password.
   *
   * @return null|string
   *   The error if any otherwise NULL.
   */
  protected function handlePasswordUpdate($email, $oldPassword, $newPassword) {
    // If they didn't supply both a new and old password then we cannot even
    // attempt to change it.
    if (!$oldPassword || !$newPassword) {
      return;
    }

    // We first trigger an adminInitiateAuth to ensure that their current
    // password is correct and to retrieve an access token. Technically we could
    // reuse the access token that we store in a cookie to save this request
    // but that wouldn't prevent account hijacking if your computer was
    // unattended.
    $authorizeResult = $this->cognito->authorize($email, $oldPassword);
    if ($authorizeResult->hasError()) {
      return $authorizeResult->getError();
    }

    $accessToken = $authorizeResult->getResult()['AuthenticationResult']['AccessToken'];
    $changeResult = $this->cognito->changePassword($accessToken, $oldPassword, $newPassword);

    if ($changeResult->hasError()) {
      return $changeResult->getError();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $account = parent::buildEntity($form, $form_state);

    // Keep the username in sync with the email.
    $account->setUsername($account->getEmail());

    // The password change is handled by ::handlePasswordUpdate() and not
    // Drupal.
    $account->_skipProtectedUserFieldConstraint = TRUE;

    return $account;
  }

}
