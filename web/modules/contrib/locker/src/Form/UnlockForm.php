<?php

namespace Drupal\locker\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Locker login form.
 */
class UnlockForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'locker-unlock-form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['locker.login'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityInterface $node = NULL) {
    $config = $this->config('locker.settings');
    $locker_access_options = $config->get('locker_access_options');

    if (in_array($locker_access_options, ['user_pass', 'roles'])) {
      $form['username'] = [
        '#type' => 'textfield',
        '#attributes' => ['placeholder' => $this->t('Username')],
        '#prefix' => '<div class="form-item--label">' . $this->t('Enter your') . ' <span>' . $this->t('Username') . ' </span> ' . $this->t('to gain access') . '</div>',
      ];

      $form['password'] = [
        '#type' => 'password',
        '#attributes' => ['placeholder' => $this->t('Password')],
        '#prefix' => '<div class="form-item--label">' . $this->t('Enter your Account') . ' <span>' . $this->t('Password') . '</span></div>',
      ];
    }
    elseif ($locker_access_options == 'passphrase') {
      $form['passphrase'] = [
        '#type' => 'password',
        '#attributes' => ['placeholder' => $this->t('Passphrase')],
        '#prefix' => '<div class="form-item--label">' . $this->t('Enter the') . ' <span>' . $this->t('Passphrase') . '</span> ' . $this->t('to gain access') . '</div>',
      ];
    }

    $form['submit'] = [
      // '#prefix' => '<div class="js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-username form-item-username form-no-label">', '#sufix' => '</div>',.
      '#type' => 'submit',
      '#attributes' => ['class' => ['form-submit'], 'style' => ''],
      '#value' => $this->t('Unlock'),
    ];

    // $form['developed'] = [
    //  '#markup' => '<p class="error__message">Developed by <a class="error__message" href="http://www.websolutions.hr/">WEBSOLUTIONS | HR</a></p>',
    // ];.
    $form['#attached']['library'][] = 'locker/locker-unlockform';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $unlocked = FALSE;

    $config = $this->config('locker.settings');

    $locker_access_options = $config->get('locker_access_options');

    if ($locker_access_options == 'user_pass') {
      $user_unlock = $form_state->getValue('username');
      $pass_unlock = $form_state->getValue('password');
      $password_unlock = $config->get('locker_password');
      $username_unlock = $config->get('locker_user');
      if ($user_unlock == $username_unlock && md5($pass_unlock) == $password_unlock) {
        $unlocked = TRUE;
      }
    }
    elseif ($locker_access_options == 'passphrase') {
      $passphrase = $form_state->getValue('passphrase');
      $passphrase_unlock = $config->get('locker_passphrase');
      if (md5($passphrase) == $passphrase_unlock) {
        $unlocked = TRUE;
      }
    }

    if ($unlocked) {
      $_SESSION['locker_unlocked'] = 'yes';
      \Drupal::state()->delete('locker_login_error');
    }
    else {
      \Drupal::state()->set('locker_login_error', 'yes');
    }

    parent::submitForm($form, $form_state);
  }

}
