<?php

/**
 * @file
 * Contains \Drupal\mailjet_api_statistics\Form\ConfigForm.
 */

namespace Drupal\mailjet_api_statistics\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *  Config form.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'api_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'mailjet_api_statistics.collect_data.settings',
    ];
  }

  /**
   * Создание нашей формы.
   *
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('mailjet_api_statistics.collect_data.settings');

    $form['default_mailjet_api_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Mailjet API key'),
      '#default_value' => $config->get('mailjet_api_key'),
    );

    $form['default_mailjet_api_secret_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Mailjet API secret key'),
      '#default_value' => $config->get('mailjet_api_secret_key'),
    );

    $form['default_mailjet_enable_debug'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Save Mailjet API response to recent log messages'),
      '#default_value' => $config->get('mailjet_api_enable_debug'),
    );
    // Субмит наследуем от ConfigFormBase
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->config('mailjet_api_statistics.collect_data.settings')
      ->set('mailjet_api_key', $values['default_mailjet_api_key'])
      ->set('mailjet_api_secret_key', $values['default_mailjet_api_secret_key'])
      ->set('mailjet_api_enable_debug', $values['default_mailjet_enable_debug'])
      ->save();
  }
}
