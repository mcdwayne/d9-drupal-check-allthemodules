<?php

namespace Drupal\apsis_mail\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Settings form.
 */
class ApsisMailSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'apsis_mail_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'apsis_mail.admin',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get config and states.
    $config = $this->config('apsis_mail.admin');
    $api_key = \Drupal::state()->get('apsis_mail.api_key');
    $mailing_lists = \Drupal::state()->get('apsis_mail.mailing_lists');
    $demographic_data = \Drupal::state()->get('apsis_mail.demographic_data');
    $always_show_demographic_data = \Drupal::state()->get('apsis_mail.demographic_data.always_show');

    // Invoke Apsis service.
    $apsis = \Drupal::service('apsis');

    $form['api'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API'),
    ];

    $form['api']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key'),
      '#description' => $this->t('API key goes here.'),
      '#default_value' => $api_key,
    ];

    $form['api']['endpoint'] = [
      '#type' => 'details',
      '#title' => $this->t('Endpoint'),
      '#open' => FALSE,
    ];

    $form['api']['endpoint']['api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#description' => $this->t('URL to API method.'),
      '#default_value' => $config->get('api_url'),
    ];

    $form['api']['endpoint']['api_ssl'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use SSL'),
      '#description' => $this->t('Use secure connection.'),
      '#default_value' => $config->get('api_ssl'),
    ];

    $form['api']['endpoint']['api_port'] = [
      '#type' => 'textfield',
      '#title' => t('SSL port'),
      '#description' => t('API endpoint SSL port number.'),
      '#default_value' => $config->get('api_port'),
      '#states' => [
        'visible' => [
          ':input[name="api_ssl"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    // Get user roles.
    $user_roles = user_roles(TRUE);
    $roles = [];
    foreach ($user_roles as $role) {
      $roles[$role->id()] = $role->label();
    }

    $form['user_roles'] = [
      '#type' => 'details',
      '#title' => $this->t('Control subscriptions on user profile'),
      '#description' => $this->t(
        'Enables users with corresponding role(s) selected to subscribe
        and unsubscribe to mailing lists via their user edit page.'
      ),
    ];

    $form['user_roles']['user_roles'] = [
      '#type' => 'checkboxes',
      '#title' => t('Roles'),
      '#options' => $roles,
      '#default_value' => $config->get('user_roles') ? $config->get('user_roles') : [],
    ];

    if ($apsis->getMailingLists()) {
      $form['mailing_lists'] = [
        '#type' => 'details',
        '#title' => $this->t('Mailing lists'),
        '#description' => $this->t('Globally allowed mailing lists on site'),
      ];

      $form['mailing_lists']['mailing_lists'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Allowed mailing lists'),
        '#options' => $apsis->getMailingLists(),
        '#default_value' => $mailing_lists ? $mailing_lists : [],
      ];
    }

    if ($apsis->getDemographicData()) {
      $form['demographic_data'] = [
        '#type' => 'details',
        '#title' => $this->t('Demographic data'),
        '#description' => $this->t('Globally allowed demographic data on site'),
      ];

      $form['demographic_data']['always_show'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Always show'),
        '#description' => $this->t('This will enforce all blocks to show demographic data'),
        '#default_value' => $always_show_demographic_data,
      ];

      $form['demographic_data']['demographic_data'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('APSIS Parameter'),
          $this->t('Label on block'),
          $this->t('Available on block'),
          $this->t('Required'),
          $this->t('Checkbox'),
          $this->t('Value when checkbox is checked'),
        ],
      ];

      foreach ($apsis->getDemographicData() as $key => $demographic) {
        $alternatives = $demographic['alternatives'];

        $form['demographic_data']['demographic_data'][$key]['key'] = [
          '#plain_text' => $key,
        ];

        $form['demographic_data']['demographic_data'][$key]['label'] = [
          '#type' => 'textfield',
          '#default_value' => !empty($demographic_data[$key]) ? $demographic_data[$key]['label'] : '',
        ];

        $form['demographic_data']['demographic_data'][$key]['available'] = [
          '#type' => 'checkbox',
          '#default_value' => !empty($demographic_data[$key]) ? $demographic_data[$key]['available'] : '',
        ];

        $form['demographic_data']['demographic_data'][$key]['required'] = [
          '#type' => 'checkbox',
          '#default_value' => !empty($demographic_data[$key]) ? $demographic_data[$key]['required'] : '',
          '#disabled' => (count($alternatives) > 2 || !$alternatives) ? FALSE : TRUE,
        ];

        $form['demographic_data']['demographic_data'][$key]['checkbox'] = [
          '#type' => 'checkbox',
          '#default_value' => !empty($demographic_data[$key]) ? $demographic_data[$key]['checkbox'] : '',
          '#disabled' => (count($alternatives) == 2) ? FALSE : TRUE,
        ];

        $form['demographic_data']['demographic_data'][$key]['return_value'] = [
          '#type' => (count($alternatives) == 2) ? 'select' : NULL,
          '#options' => (count($alternatives) == 2) ? $alternatives : NULL,
          '#default_value' => !empty($demographic_data[$key]['return_value']) ? $demographic_data[$key]['return_value'] : '',
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save states.
    \Drupal::state()->setMultiple([
      'apsis_mail.api_key' => $form_state->getValue('api_key') ? $form_state->getValue('api_key') : '',
      'apsis_mail.mailing_lists' => $form_state->getValue('mailing_lists') ? array_filter($form_state->getValue('mailing_lists')) : [],
      'apsis_mail.demographic_data' => $form_state->getValue('demographic_data') ? array_filter($form_state->getValue('demographic_data')) : [],
      'apsis_mail.demographic_data.always_show' => $form_state->getValue('always_show'),
    ]);

    // Save settings.
    $this->config('apsis_mail.admin')
      ->set('api_url', $form_state->getValue('api_url'))
      ->set('api_ssl', $form_state->getValue('api_ssl'))
      ->set('api_port', $form_state->getValue('api_port'))
      ->set('user_roles', $form_state->getValue('user_roles'))
      ->save();

    drupal_set_message($this->t('Settings saved.'));
  }

}
