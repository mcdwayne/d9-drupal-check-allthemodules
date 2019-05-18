<?php

namespace Drupal\gapi\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase {

  /**
   * The form id.
   *
   * @var string
   */
  const FORM_ID = 'gapi_settings_form';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return static::FORM_ID;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'gapi.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('gapi.settings');

    $form['application_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Application Name'),
      '#default_value' => $config->get('application_name'),
      '#size' => 40,
    ];

    $form['authentication_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Authentication Method'),
      '#options' => [
        'developer_key' => $this->t('Simple API Access'),
        'oauth' => $this->t('OAuth 2.0 for Webservers'),
        'application_credentials' => $this->t('OAuth 2.0 Service Accounts'),
      ],
      '#empty_option' => $this->t('- Select One -'),
      '#default_value' => $config->get('authentication_method'),
      '#description' => $this->t('Choose the authentication method you would like the API client to use. See the official <a href=":link" target="_none">Google PHP Client Library documentation.</a> for more information.', [
        ':link' => 'https://developers.google.com/api-client-library/php/guide/aaa_overview',
      ]),
    ];

    $form['developer_key'] = [
      '#type' => 'key_select',
      '#title' => $this->t('Developer Key'),
      '#default_value' => $config->get('developer_key'),
      '#states' => [
        'visible' => [
          ':input[name="authentication_method"]' => ['value' => 'developer_key'],
        ],
      ],
    ];

    $form['oauth'] = [
      '#type' => 'item',
      '#title' => $this->t('Not Supported'),
      '#description' => $this->t('OAuth 2.0 for Webservers is not currently supported. If you need this feature, feel free to submit a patch and feature request in <a href=":link">the issue queue</a>.', [
        ':link' => 'https://www.drupal.org/project/issues/gapi',
      ]),
      '#states' => [
        'visible' => [
          ':input[name="authentication_method"]' => ['value' => 'oauth'],
        ],
      ],
    ];

    $form['application_credentials'] = [
      '#type' => 'key_select',
      '#title' => $this->t('Application Credentials'),
      '#default_value' => $config->get('application_credentials'),
      '#states' => [
        'visible' => [
          ':input[name="authentication_method"]' => ['value' => 'application_credentials'],
        ],
      ],
    ];

    $form['application_scopes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Application Scopes'),
      '#default_value' => $config->get('application_scopes'),
      '#states' => [
        'visible' => [
          ':input[name="authentication_method"]' => ['value' => 'application_credentials'],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('gapi.settings');

    $config_keys = [
      'application_name',
      'authentication_method',
      'developer_key',
      'application_credentials',
      'application_scopes',
    ];

    foreach ($config_keys as $key) {
      $config->set($key, $values[$key]);
    }

    $config->save();
  }


}
