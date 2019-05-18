<?php

namespace Drupal\rokka\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Rokka settings for this site.
 */
class RokkaSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rokka_admin_settings_form';
  }

  /**
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('rokka.settings');

    $form = [
      'is_enabled' => [
        '#title' => $this->t('Enable Rokka.io service'),
        '#description' => $this->t('Enable or disable the Rokka.io integration'),
        '#type' => 'checkbox',
        '#default_value' => $config->get('is_enabled'),
      ],
      'credentials' => [
        '#type' => 'fieldset',
        '#title' => $this->t('API Credentials'),
        '#description' => $this->t('Enter your Rokka.io API credentials'),
        '#collapsible' => FALSE,

        'api_key' => [
          '#title' => $this->t('API Key'),
          '#description' => $this->t('The API Key credential provided by the Rokka.io service'),
          '#type' => 'textfield',
          '#required' => TRUE,
          '#default_value' => $config->get('api_key'),
        ],
      ],
      'organization' => [
        '#type' => 'fieldset',
        '#title' => $this->t('Organization Credentials'),
        '#description' => $this->t('Enter the Organization at Rokka.io'),
        '#collapsible' => FALSE,

        'organization_name' => [
          '#title' => $this->t('Organization Name'),
          '#description' => $this->t('The Organization Name given from the Rokka.io service'),
          '#type' => 'textfield',
          '#required' => TRUE,
          '#default_value' => $config->get('organization_name'),
        ],
      ],
      'stack_default_settings' => [
        '#type' => 'fieldset',
        '#title' => $this->t('Rokka Stack: Default Values'),
        '#description' => $this->t('These values will be used, when creating new stacks or the value is not set.'),
        '#collapsible' => FALSE,

        'jpg_quality' => [
          '#type' => 'textfield',
          '#title' => t('JPG quality'),
          '#description' => t('JPEG Quality: from 1 (high compression, low quality) to 100 (low compression, high quality)'),
          '#size' => 20,
          '#maxlength' => 3,
          '#required' => FALSE,
          '#min' => 0,
          '#max' => 100,
          '#default_value' => $config->get('jpg_quality') ?? 0,
        ],
        'webp_quality' => [
          '#type' => 'textfield',
          '#title' => t('WEBP quality'),
          '#description' => t('WEBP Quality: from 1 (high compression, low quality) to 100 (low compression, high quality)'),
          '#size' => 20,
          '#maxlength' => 3,
          '#required' => FALSE,
          '#min' => 0,
          '#max' => 100,
          '#default_value' => $config->get('webp_quality') ?? 0,
        ],
        'output_format' => [
          '#type' => 'select',
          '#title' => t('Output format '),
          '#description' => t('Defines the delivered image format.'),
          '#required' => TRUE,
          '#default_value' => $config->get('output_format') ?? 'jpg',
          '#options' => [
            'jpg' => t('JPG'),
            'png' => t('PNG'),
            'gif' => t('GIF'),
          ],
        ],
        'autoformat' => [
          '#type' => 'radios',
          '#title' => t('autoformat '),
          '#description' => t('If set, rokka delivers the WEBP instead of JPG to supported browsers.'),
          '#required' => FALSE,
          '#default_value' => $config->get('autoformat') ?? 'none',
          '#options' => [
            'true' => t('True'),
            'false' => t('False'),
            'none' => t('Rokka default (false)'),
          ],
        ],
      ],
      'api_endpoint' => [
        '#title' => $this->t('API Endpoint'),
        '#description' => $this->t('The API endpoint'),
        '#type' => 'textfield',
        '#required' => TRUE,
        '#default_value' => $config->get('api_endpoint'),
      ],
      'stack_prefix' => [
        '#title' => $this->t('Stack Name Prefix'),
        '#description' => $this->t('Adds a prefix for newly created Rokka stacks. Helps preventing overwriting existing stacks created in the Rokka.io dashboard. '),
        '#type' => 'textfield',
        '#required' => FALSE,
        '#default_value' => $config->get('stack_prefix'),
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('rokka.settings');

    $config->set('is_enabled', $values['is_enabled']);
    $config->set('api_key', $values['api_key']);
    $config->set('api_endpoint', $values['api_endpoint']);
    $config->set('jpg_quality', $values['jpg_quality']);
    $config->set('webp_quality', $values['webp_quality']);
    $config->set('autoformat', $values['autoformat']);
    $config->set('output_format', $values['output_format']);
    $config->set('organization_name', $values['organization_name']);
    $config->set('stack_prefix', $values['stack_prefix']);
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['rokka.settings'];
  }

}
