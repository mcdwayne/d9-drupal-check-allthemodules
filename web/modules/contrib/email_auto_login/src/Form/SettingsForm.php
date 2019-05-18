<?php

/**
 * @file
 * Contains \Drupal\email_auto_login\Form\SettingsForm.
 */

namespace Drupal\email_auto_login\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configures aggregator settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'email_auto_login_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $password_reset_timeout = $this->config('user.settings')->get('password_reset_timeout');
    $timeout = $this->config('email_auto_login.settings')->get('expiration_time');

    $form['expiration_time'] = array(
      '#type' => 'select',
      '#title' => $this->t('How long should tokens remain valid for?'),
      '#options' => array(
        60 * 60 => $this->t('1 hour'),
        60 * 60 * 24 => $this->t('1 day'),
        60 * 60 * 24 * 7 => $this->t('1 week'),
        60 * 60 * 24 * 30 => $this->t('30 days'),
      ),
      '#default_value' => $timeout ?: $password_reset_timeout,
    );

    $form['status'] = array(
      '#type' => 'details',
      '#title' => $this->t('Status'),
    );
    $form['status']['status'] = array(
      '#markup' => $this->t('There are %token_count tokens in the database', array(
        '%token_count' => $this->getTokenCount(),
      ))
    );
    $form['status']['flush'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Clear Tokens'),
      '#submit' => array(array($this, 'flushTokensSubmit')), //@TODO: Doesn't work.
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Return the number of tokens in the database.
   */
  private function getTokenCount() {
    return db_select('email_auto_login_tokens')->countQuery()->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('email_auto_login.settings')
      // Remove unchecked types.
      ->set('expiration_time', $form_state->getValue('expiration_time'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Form submission handler for the flush button.
   * Will flush all the tokens from the database.
   */
  private function flushTokensSubmit(array &$form, FormStateInterface $form_state) {
    db_truncate('email_auto_login_tokens')->execute();
  }
}
