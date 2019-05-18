<?php

namespace Drupal\reset_pass_email_otp_auth\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Form class rest otp validate form.
 */
class OTPCheck extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'resume_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // User Session set.
    $request = \Drupal::requestStack()->getCurrentRequest();
    $current_path = \Drupal::service('path.current')->getPath();
    $uid = explode('/', $current_path);
    $uid = end($uid);
    $query = Database::getConnection()
      ->select('reset_pass_email_otp_auth_track', 'tracker')
      ->fields('tracker', ['uid', 'time', 'hash'])
      ->condition('status', 'reset-auth', 'LIKE')
      ->condition('uid', $uid, '=');
    $user_track = $query->execute()->fetchAssoc();
    // kint($user_track);die;
    $account = User::load($uid);
    if (!is_null($account) && isset($account)) {
      if ($user_track != FALSE && !empty($account->get('login')->value)) {
        // Get and set session for user reset login.
        $session = $request->getSession();
        $session->set('pass_reset_hash', $user_track['hash']);
        $session->set('pass_reset_timeout', $user_track['time']);
        $form['#attributes']['autocomplete'] = 'off';
        $form['otp_validate'] = [
          '#type' => 'password',
          '#description' => $this->t('Validate your OTP and reset your password.'),
          '#title' => $this->t('OTP Validate:'),
          '#size' => 32,
          '#maxlength' => 32,
          '#required' => TRUE,
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Validate'),
          '#button_type' => 'primary',
        ];

        return $form;
      }
      else {
        if (!empty($account->get('login')->value)) {
          drupal_set_message($this->t('You have tried to use a old one-time login link that has expired. Please request a new one using the form below.'), 'error');
          $response = new RedirectResponse('/user/password');
          $response->send();
          exit;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_path = \Drupal::service('path.current')->getPath();
    $uid = explode('/', $current_path);
    $uid = end($uid);
    $query = Database::getConnection()
      ->select('reset_pass_email_otp_auth_track', 'tracker')
      ->fields('tracker', ['uid', 'time', 'hash'])
      ->condition('status', 'reset-auth', 'LIKE')
      ->condition('uid', $uid, '=');
    $user_track = $query->execute()->fetchAssoc();

    if (isset($user_track) && $user_track != FALSE) {
      $form_state->setRedirect('user.reset.login', [
        'uid' => $user_track['uid'],
        'timestamp' => $user_track['time'],
        'hash' => $user_track['hash'],
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Get current user uid and validate with db.
    $request = \Drupal::requestStack()->getCurrentRequest();
    $current_path = \Drupal::service('path.current')->getPath();
    $uid = explode('/', $current_path);
    $uid = end($uid);

    // Get database connection to get has detail.
    $query = Database::getConnection()
      ->select('reset_pass_email_otp_auth_track', 'tracker')
      ->fields('tracker', ['uid', 'time', 'hash', 'OTP', 'count'])
      ->condition('status', 'reset-auth', 'LIKE')
      ->condition('uid', $uid, '=');
    $user_track = $query->execute()->fetchAssoc();
    // Get config limit of OTP wrong attempt.
    $limit_wrong_otp = \Drupal::config('citi_reset_email_opt_auth.settings')
      ->get('citi_reset_email_opt_auth_wrong_attempt');
    // Form get OTP values.
    $otp = $form_state->getValue('otp_validate');
    if ($otp != $user_track['OTP'] && $user_track != FALSE) {
      // Set session for current user.
      $session = $request->getSession();
      if ((int) $user_track['count'] < $limit_wrong_otp) {
        $session->set('pass_reset_hash', $user_track['hash']);
        $session->set('pass_reset_timeout', $user_track['time']);
        $name_value = $form_state->getValue('otp_validate');
        $form_state->setErrorByName('otp_validate', $this->t('The OTP is not valid. Please try again.', [
          ':password' => $name_value,
        ]));

        // Update counter for wrong attempt OTP.
        $con = Database::getConnection();
        $query = $con->merge('reset_pass_email_otp_auth_track')
          ->key(['uid' => $uid])
          ->insertFields([
            'uid' => $uid,
            'count' => 1,
          ])
          ->updateFields([
            'uid' => $uid,
          ])
          ->expression('count', 'count + :inc', [':inc' => 1]);
        $query->execute();
      }
      else {
        // After wrong attempt limit cross.
        $session->remove('pass_reset_hash');
        $session->remove('pass_reset_timeout');
        $query = Database::getConnection()
          ->delete('reset_pass_email_otp_auth_track');
        $query->condition('uid', $uid);
        $query->execute();

        // User block after wrong attempt limit cross.
        $account = User::load($uid);
        $account->set('status', 0);
        $account->save();

        // User blocked mail notification.
        user_account_block_after_attempt_wrong_password($account->getEmail());

        // Redirect after cross wrong attempt.
        drupal_set_message($this->t('You have tried to use a old one-time login link that has expired. Please request a new one using the form below.'), 'error');
        $response = new RedirectResponse('/user/password');
        $response->send();
        exit;
      }
    }
  }

}
