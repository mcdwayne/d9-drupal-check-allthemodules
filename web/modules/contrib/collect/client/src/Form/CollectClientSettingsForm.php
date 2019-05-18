<?php

/**
 * @file
 * Contains \Drupal\collect_client\Form\CollectClientSettingsForm.
 */

namespace Drupal\collect_client\Form;

use Drupal\collect_common\Form\CollectSettingsFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure collect client settings.
 */
class CollectClientSettingsForm extends CollectSettingsFormBase {

  /**
   * The configuration name.
   *
   * @var string
   */
  protected $configurationName = 'collect_client.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'collect_client_settings_page';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['collect_client.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('collect_client.settings');

    $form['service_url'] = array(
      '#type' => 'textfield',
      '#title' => t('Service URL'),
      '#default_value' => $config->get('service.url'),
      '#size' => 60,
      '#maxlength' => 128,
    );

    $form['service_user'] = array(
      '#type' => 'textfield',
      '#title' => t('Username'),
      '#default_value' => $config->get('service.user'),
      '#size' => 60,
      '#maxlength' => 128,
    );

    $form['service_password'] = array(
      '#type' => 'password',
      '#title' => t('Password'),
      '#default_value' => $config->get('service.password'),
      '#size' => 60,
      '#maxlength' => 128,
    );
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config($this->configurationName)
      ->set('service.url', $form_state->getValue('service_url'))
      ->set('service.user', $form_state->getValue('service_user'))
      ->set('service.password', $form_state->getValue('service_password'))
      ->save();
  }

}
