<?php

namespace Drupal\chatbro\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class ChatBroForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'chatbro_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'chatbro.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('chatbro.settings');

    $form['chatbro_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ChatBro ID'),
      '#default_value' => $config->get('chatbro_id'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable('chatbro.settings')
    // Set the submitted configuration setting.
      ->set('chatbro_id', $form_state->getValue('chatbro_id'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
