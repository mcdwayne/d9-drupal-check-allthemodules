<?php

namespace Drupal\remove_meta_and_headers\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Admin settings for META and Response headers.
 */
class ConfigSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'remove_meta_and_headers_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'remove_meta_and_headers.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get config data.
    $config = $this->config('remove_meta_and_headers.settings');
    $meta_generator = !empty($config->get('meta_generator')) ? (int) $config->get('meta_generator') : 0;
    $response_header_x_generator = !empty($config->get('response_header_x_generator')) ? (int) $config->get('response_header_x_generator') : 0;

    $form['meta_head_fieldset'] = [
      '#type' => 'details',
      '#title' => $this->t('Meta tags'),
      '#description' => $this->t('Remove meta tags from <head>'),
      '#open' => TRUE,
    ];

    $form['response_header_fieldset'] = [
      '#type' => 'details',
      '#title' => $this->t('Response header'),
      '#open' => TRUE,
      '#description' => $this->t('Remove from response header'),
    ];

    $form['meta_head_fieldset']['meta_generator'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('META tag <strong>Generator</strong>') . ' - ' . ($meta_generator ? $this->t('Removed') : $this->t('Not Removed')),
      '#default_value' => $meta_generator,
    ];

    $form['response_header_fieldset']['response_header_x_generator'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Response Header <strong>X-Generator</strong>') . ' - ' . ($response_header_x_generator ? $this->t('Removed') : $this->t('Not Removed')),
      '#default_value' => $response_header_x_generator,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get configuration.
    $config = $this->configFactory->getEditable('remove_meta_and_headers.settings');

    // Set data.
    $config->set('meta_generator', $form_state->getValue('meta_generator'));
    $config->set('response_header_x_generator', $form_state->getValue('response_header_x_generator'));

    // Save data.
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
