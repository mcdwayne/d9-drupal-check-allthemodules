<?php

namespace Drupal\cumulio\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CumulioSettingsForm.
 */
class CumulioSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cumulio.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cumulio_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cumulio.settings');

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('api_key'),
      '#description' => $this->t('Visit the \'Integration\' tab on <a href="https://app.cumul.io/start/profile/organization">https://app.cumul.io/start/profile/organization</a> and click \'Create API key\'. Enter the value of \'API key\' in this field.'),
    ];

    $form['api_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Api token'),
      '#description' => $this->t("Enter the value of 'API token' in this field."),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('api_token'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('cumulio.settings')
      ->set('api_token', $form_state->getValue('api_token'))
      ->set('api_key', $form_state->getValue('api_key'))
      ->save();

    // Invalidate cumulio_form cache tag.
    Cache::invalidateTags(['cumulio_form']);
  }

}
