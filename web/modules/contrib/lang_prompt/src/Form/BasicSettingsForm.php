<?php

namespace Drupal\lang_prompt\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class BasicSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lang_prompt_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'lang_prompt.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('lang_prompt.config');

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Prompt message'),
      '#description' => $this->t('Use the token !href where you would like to print the url of the current page\'s preferred language counterpart.'),
      '#default_value' => $config->get('message'),
    ];

    $form['append_to_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Append to selector'),
      '#description' => $this->t('Provide a Javascript selector to which the language prompt will be appended via the \'afterBegin\' method'),
      '#default_value' => $config->get('append_to_selector'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::configFactory()->getEditable('lang_prompt.config')
      ->set('message', $form_state->getValue('message'))
      ->set('append_to_selector', $form_state->getValue('append_to_selector'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
