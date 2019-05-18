<?php

namespace Drupal\icecat\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\icecat\Form
 *
 * @ingroup icecat
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'icecat_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['icecat.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('icecat.settings');
    $config->set('username', $form_state->getValue('username'));

    // Only update the password when it's entered.
    if ($form_state->getValue('password')) {
      $config->set('password', $form_state->getValue('password'));
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('icecat.settings');

    $form['icecat'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Account settings'),
    ];

    $form['icecat']['username'] = [
      '#title' => $this->t('Username'),
      '#description' => $this->t('Your username used to access Icecat. You can register <a href="@link">Here</a>.', ['@link' => 'https://icecat.biz/registration/']),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $config->get('username'),
    ];

    $form['icecat']['password'] = [
      '#title' => $this->t('Password'),
      '#description' => $this->t('Your Icecat password. Only enter to change.'),
      '#type' => 'password',
      '#required' => FALSE,
      '#default_value' => $config->get('password'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
