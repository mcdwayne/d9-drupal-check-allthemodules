<?php

namespace Drupal\formassembly\Form;

use Drupal\Core\Form\ConfigFormBaseTrait;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\formassembly\ApiAuthorize;
use Drupal\formassembly\FormAssemblyKeyService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * FormAssembly entity settings form.
 *
 * @author Shawn P. Duncan <code@sd.shawnduncan.org>
 *
 * Copyright 2018 by Shawn P. Duncan.  This code is
 * released under the GNU General Public License.
 * Which means that it is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 * http://www.gnu.org/licenses/gpl.html
 * @package Drupal\formassembly
 */
class FormAssemblyEntitySettingsForm extends FormBase {

  use ConfigFormBaseTrait;

  /**
   * Injected authorization service.
   *
   * @var \Drupal\formassembly\ApiAuthorize
   */
  protected $authorize;

  /**
   * Injected key service.
   *
   * @var \Drupal\formassembly\FormAssemblyKeyService
   */
  protected $keyService;

  /**
   * FormAssemblyEntity config.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $formassemblyConfig;

  /**
   * FormAssemblyEntitySettingsForm constructor.
   *
   * @param \Drupal\formassembly\ApiAuthorize $authorize
   *   Injected authorization service.
   * @param \Drupal\formassembly\FormAssemblyKeyService $keyService
   *   Injected key service.
   */
  public function __construct(ApiAuthorize $authorize, FormAssemblyKeyService $keyService) {
    $this->authorize = $authorize;
    $this->keyService = $keyService;
    $this->formassemblyConfig = $this->config('formassembly.api.oauth');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('formassembly.authorize'),
      $container->get('formassembly.key')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'FormAssemblyEntity_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'formassembly.api.oauth',
    ];
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Config\Config $formassembly_config */
    $values = $form_state->getValues();
    $config = [
      'endpoint' => $values['endpoint'],
      'admin_index' => $values['admin_index'],
      'credentials' => [
        'provider' => $values['provider'],
      ],
    ];
    switch ($values['provider']) {
      case 'formassembly':
        $config['credentials']['data']['cid'] = $values['cid'];
        $config['credentials']['data']['secret'] = $values['secret'];

        break;

      case 'key':
        $config['credentials']['data']['id'] = $values['id'];

        break;
    }
    $this->formassemblyConfig->setData($config)->save();
    $sync = $form_state->getValue('batch_sync');
    $reauthorize = $form_state->getValue('reauthorize');
    if ($reauthorize || !$this->authorize->isAuthorized()) {
      $form_state->setRedirect('fa_form.authorize');
    }
    if ($sync) {
      // Setup a batch.
      $batch = [
        'operations' => [
          ['formassembly_batch_get_forms', []],
        ],
        'finished' => 'formassembly_batch_finished',
        'title' => 'Request Forms Data from FormAssembly',
        'init_message' => 'Contacting FormAssembly',
      ];
      batch_set($batch);
    }
  }

  /**
   * Defines the settings form for FormAssembly Form entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $form['credentials'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Oauth Credentials'),
      'provider' => [
        '#type' => 'hidden',
        '#value' => 'formassembly',
      ],
    ];
    $credentials = $this->formassemblyConfig->get('credentials');
    $form['credentials']['cid'] = [
      '#type' => 'textfield',
      '#required' => FALSE,
      '#title' => t('FormAssembly OAuth Client ID'),
      '#description' => t('The client ID.'),
      '#default_value' => isset($credentials['data']['cid']) ? $credentials['data']['cid'] : '',
    ];

    $form['credentials']['secret'] = [
      '#type' => 'textfield',
      '#required' => FALSE,
      '#title' => t('FormAssembly OAuth Client Secret'),
      '#description' => t('The client secret.'),
      '#default_value' => isset($credentials['data']['secret']) ? $credentials['data']['secret'] : '',
    ];
    if ($this->keyService->additionalProviders()) {
      $this->buildProviderOptions($form);
    }

    $form['endpoint'] = [
      '#type' => 'url',
      '#title' => t('FormAssembly Url'),
      '#required' => TRUE,
      '#description' => t('The url of your instance. Examples: https://developer.formassembly.com, https://app.formassembly.com, https://instance_name.tfaforms.net, https://your_server/path/to/formassembly'),
      '#default_value' => $this->formassemblyConfig->get('endpoint'),
    ];

    $form['admin_index'] = [
      '#type' => 'checkbox',
      '#required' => FALSE,
      '#title' => t('Admin Index'),
      '#description' => t('Return a list of all forms in the FormAssembly instance. Only applies to Enterprise level instances. The authentication tokens entered above must be from an admin-level user'),
      '#default_value' => $this->formassemblyConfig->get('admin_index'),
    ];
    $documentation = Url::fromUri('http://docs.drush.org/en/master/cron/');
    $form['batch_sync'] = [
      '#type' => 'checkbox',
      '#title' => t('Sync now'),
      '#description' => t('Sync forms after submitting this form. <em>Disabled if authorization is not complete.</em>'),
      '#default_value' => FALSE,
      '#disabled' => !$this->authorize->isAuthorized(),
      '#suffix' => '<div>' . t('To sync on cron, place the drush command %command into your crontab.  See @url for more information.',
          [
            '%command' => 'drush fa-sync',
            '@url' => $this->getLinkGenerator()
              ->generate('Running Drupal cron tasks from Drush',
                $documentation),
          ]) . '</div>',
    ];

    $form['reauthorize'] = [
      '#type' => 'checkbox',
      '#title' => t('Reauthorize'),
      '#description' => t('Reauthorize this application and get a new access token.'),
      '#default_value' => FALSE,
    ];

    $form['save'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];

    return $form;
  }

  /**
   * Helper method to build the credential provider elements of the form.
   *
   * @param array $form
   *   The configuration form.
   */
  protected function buildProviderOptions(array &$form) {
    $credentials = $this->formassemblyConfig->get('credentials');
    // Provide selectors for the api key credential provider.
    $form['credentials']['provider'] = [
      '#type' => 'select',
      '#title' => $this->t('Credential provider'),
      '#default_value' => empty($credentials['provider']) ? 'formassembly' : $credentials['provider'],
      '#options' => [
        'formassembly' => 'FormAssembly Configuration',
        'key' => 'Key Module',
      ],
      '#attributes' => [
        'data-states-selector' => 'provider',
      ],
    ];

    $formassembly_state = [
      'required' => [
        ':input[data-states-selector="provider"]' => ['value' => 'formassembly'],
      ],
      'visible' => [
        ':input[data-states-selector="provider"]' => ['value' => 'formassembly'],
      ],
      'enabled' => [
        ':input[data-states-selector="provider"]' => ['value' => 'formassembly'],
      ],
    ];
    $form['credentials']['cid']['#states'] = $formassembly_state;
    $form['credentials']['secret']['#states'] = $formassembly_state;
    $key_id = isset($credentials['data']['id']) ? $credentials['data']['id'] : '';
    $form['credentials']['id'] = [
      '#type' => 'key_select',
      '#title' => $this->t('Select a Stored Key'),
      '#description' => $this->t('Select the key you have configured to hold the Oauth Client ID and Secret.'),
      '#default_value' => $key_id,
      '#empty_option' => $this->t('- Please select -'),
      '#key_filters' => ['type' => 'formassembly_oauth'],
      '#states' => [
        'required' => [
          ':input[data-states-selector="provider"]' => ['value' => 'key'],
        ],
        'visible' => [
          ':input[data-states-selector="provider"]' => ['value' => 'key'],
        ],
        'enabled' => [
          ':input[data-states-selector="provider"]' => ['value' => 'key'],
        ],
      ],
    ];
  }

}
