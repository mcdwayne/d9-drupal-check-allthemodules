<?php

namespace Drupal\one_time_password\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * A form for setting up one time passwords.
 */
class PasswordSetupForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'one_time_password_setup_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, User $user = NULL) {
    $form_state->set('user', $user);
    if ($user->one_time_password->isEmpty()) {
      $form['instructions'] = [
        '#theme' => 'one_time_password_enable_instructions',
      ];
      $form['generate'] = [
        '#type' => 'submit',
        '#value' => $this->t('Enable Two Factor Authentication'),
        '#submit' => [[$this, 'enableTwoFactorAuth']],
      ];
    }
    else {
      $password = $user->one_time_password->getOneTimePassword();
      $form['qr_code'] = [
        '#theme' => 'image',
        '#uri' => $password->getQrCodeUri(),
      ];
      $form['instructions'] = [
        '#theme' => 'one_time_password_setup_instructions',
      ];
      $form['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Disable Two Factor Authentication'),
        '#submit' => [[$this, 'disableTwoFactorAuth']],
        '#button_type' => 'danger',
      ];
    }

    $form['#cache']['max-age'] = 0;
    return $form;
  }

  /**
   * Submit callback to enable two factor authentication.
   */
  public function enableTwoFactorAuth(array &$form, FormStateInterface $form_state) {
    $user = $form_state->get('user');
    $user->one_time_password->regenerateOneTimePassword();
    $user->save();
    drupal_set_message($this->t('Two factor authentication has been enabled. See the instructions below for setting up your one time password.'));
  }

  /**
   * Submit callback to disable two factor authentication.
   */
  public function disableTwoFactorAuth(array &$form, FormStateInterface $form_state) {
    $user = $form_state->get('user');
    $user->one_time_password = [];
    $user->save();
    drupal_set_message($this->t('Two factor authentication has been disabled for this account.'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
