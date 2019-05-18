<?php

namespace Drupal\inmail_user\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the configuration form.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'inmail_user_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'inmail_user.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('inmail_user.settings');

    $form['email'] = [
      '#title' => $this->t('Unknown sender'),
      '#type' => 'email',
      '#required' => TRUE,
      '#description' => $this->t('If the sender can not detected, this address will be used.'),
      '#default_value' => $config->get('email'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->configFactory->getEditable('inmail_user.settings')
      ->set('cron', $form_state->getValue('email'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
