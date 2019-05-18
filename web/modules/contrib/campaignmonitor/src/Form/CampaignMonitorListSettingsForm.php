<?php

namespace Drupal\campaignmonitor\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Configure campaignmonitor settings for this site.
 */
class CampaignMonitorListSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'campaignmonitor_list_settings_form';
  }

  /**
   *
   */
  protected function getEditableConfigNames() {
    return ['campaignmonitor.settings.list'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $list_id = NULL) {
    $config = $this->config('campaignmonitor.settings.list');

    $form = ['#tree' => TRUE];

    $defaults = campaignmonitor_get_list_settings($list_id);

    $list = campaignmonitor_get_extended_list_settings($list_id);

    // Add list id to the form.
    $form['listId'] = [
      '#type' => 'hidden',
      '#value' => $list_id,
    ];

    // Set this form name (index).
    $form_key = 'campaignmonitor_list_' . $list_id;

    $form[$form_key]['status'] = [
      '#type' => 'fieldset',
      '#title' => t('Enable list'),
      '#description' => t('Enable the list to configure it and use it on the site.'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];

    $form[$form_key]['status']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable'),
      '#default_value' => isset($defaults['status']['enabled']) ? $defaults['status']['enabled'] : 0,
      '#attributes' => ['class' => ['enabled-list-checkbox']],
    ];

    $form[$form_key]['options'] = [
      '#type' => 'fieldset',
      '#title' => t('List options'),
      '#description' => t('Changing the values will result in an update of the values on the Campaign Monitor homepage.'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#states' => [
        'visible' => [
          '.enabled-list-checkbox' => ['checked' => TRUE],
        ],
      ],
    ];

    $form[$form_key]['options']['listname'] = [
      '#type' => 'textfield',
      '#title' => t('List name'),
      '#default_value' => $list['name'],
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="status[enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form[$form_key]['options']['UnsubscribePage'] = [
      '#type' => 'textfield',
      '#title' => t('Unsubscribe page'),
      '#default_value' => $list['details']['UnsubscribePage'],
    ];

    $form[$form_key]['options']['ConfirmationSuccessPage'] = [
      '#type' => 'textfield',
      '#title' => t('Confirmation success page'),
      '#default_value' => $list['details']['ConfirmationSuccessPage'],
    ];

    $form[$form_key]['options']['ConfirmedOptIn'] = [
      '#type' => 'checkbox',
      '#title' => t('Confirmed Opt In'),
      '#description' => t('Selecting this will mean that subscribers will need to confirm their email each time they
      subscribe to the list'),
      '#default_value' => $list['details']['ConfirmedOptIn'],
    ];

    $form[$form_key]['display'] = [
      '#type' => 'fieldset',
      '#title' => t('Display options'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#states' => [
        'visible' => [
          '.enabled-list-checkbox' => ['checked' => TRUE],
        ],
      ],
    ];

    $form[$form_key]['display']['name'] = [
      '#type' => 'checkbox',
      '#title' => t('Display Name field'),
      '#description' => t('Whether the Name field should be displayed when subscribing.'),
      '#default_value' => isset($defaults['display']['name']) ? $defaults['display']['name'] : 0,
      '#attributes' => ['class' => ['tokenable', 'tokenable-name']],
    ];

    $form[$form_key]['display']['description'] = [
      '#type' => 'textarea',
      '#title' => t('Description'),
      '#description' => t('A description to accompany the list in forms.'),
      '#default_value' => isset($defaults['display']['description']) ? $defaults['display']['description'] : '',
      '#attributes' => ['class' => ['tokenable', 'tokenable-description']],
    ];

    $field_map = \Drupal::entityManager()->getFieldMap();

    $user_field_map = $field_map['user'];

    $user_fields = array_keys($user_field_map);
    foreach ($user_fields as $key => $user_field) {
      unset($user_fields[$key]);
      $user_fields[$user_field] = $user_field;
    }

    $form[$form_key]['display']['name_field'] = [
      '#type' => 'select',
      '#options' => $user_fields,
      '#title' => t('Email name field'),
      '#description' => t('The name that will be used by Campaign Monitor as a salutation in emails sent out.'),
      '#default_value' => isset($defaults['display']['name_field']) ? $defaults['display']['name_field'] : 0,
    ];

    // List custom fields.
    if (!empty($list['CustomFields'])) {
      $options = [];
      foreach ($list['CustomFields'] as $key => $field) {
        // Form API can't handle keys with [] in all cases.
        $token_form_key = str_replace(['[', ']'], '', $key);
        $options[$token_form_key] = $field['FieldName'];
      }

      $form[$form_key]['CustomFields'] = [
        '#type' => 'fieldset',
        '#title' => t('Custom fields'),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
        '#attributes' => ['class' => ['tokenable', 'tokenable-custom-fields']],
        '#states' => [
          'visible' => [
            '.enabled-list-checkbox' => ['checked' => TRUE],
          ],
        ],
      ];

      $form[$form_key]['CustomFields']['selected'] = [
        '#type' => 'checkboxes',
        '#title' => t('Available fields'),
        '#description' => t('Select the fields that should be displayed on subscription forms.'),
        '#options' => $options,
        '#default_value' => isset($defaults['CustomFields']['selected']) ? $defaults['CustomFields']['selected'] : [],
      ];
    }

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form[$form_key]['tokens'] = [
        '#type' => 'fieldset',
        '#title' => t('Field tokens'),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
      ];

      $form[$form_key]['tokens']['name'] = [
        '#type' => 'textfield',
        '#title' => t('Name field'),
        '#default_value' => isset($defaults['tokens']['name']) ? $defaults['tokens']['name'] : '[current-user:name]',
        '#states' => [
          'visible' => [
            '.tokenable-name' => ['checked' => TRUE],
          ],
        ],
      ];

      if (!empty($list['CustomFields'])) {
        foreach ($list['CustomFields'] as $key => $field) {
          if ($field['DataType'] == 'MultiSelectMany') {
            // We can't handle this type of custom field (with tokens).
            continue;
          }

          // Form API can't handle keys with [] in all cases.
          $token_form_key = str_replace(['[', ']'], '', $key);
          $form[$form_key]['tokens'][$token_form_key] = [
            '#type' => 'textfield',
            '#title' => t('Custom field (@name)', ['@name' => $field['FieldName']]),
            '#default_value' => isset($defaults['tokens'][$token_form_key]) ? $defaults['tokens'][$token_form_key] : '',
            '#states' => [
              'visible' => [
                ':input[name="' . $form_key . '[CustomFields][selected][' . $token_form_key . ']' . '"]' => ['checked' => TRUE],
              ],
            ],
          ];
        }
      }

      $form[$form_key]['tokens']['token_tree'] = [
        '#theme' => 'token_tree',
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * Edit form validation handler which calls the API to save the information that
   * was entered. This is done in the validation function so we can give better
   * feedback to the user and to prevent the user from having to enter the
   * information once more on failure.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $list_id = $form_state->getValue('listId');
    $values = $form_state->getValues();
    // Build array with basic information.
    $values = $values['campaignmonitor_list_' . $list_id];
    $options = [
      'Title' => SafeMarkup::checkPlain($values['options']['listname']),
      'UnsubscribePage' => SafeMarkup::checkPlain($values['options']['UnsubscribePage']),
      'ConfirmedOptIn' => $values['options']['ConfirmedOptIn'] ? TRUE : FALSE,
      'ConfirmationSuccessPage' => SafeMarkup::checkPlain($values['options']['ConfirmationSuccessPage']),
    ];
    $result = campaignmonitor_set_extended_list_settings($list_id, $options);

    if ($result != 'success') {
      $form_state->setErrorByName('', $result);
    }

    // Redirect to list overview.
    $url = Url::fromRoute('campaignmonitor.lists');
    $form_state->setRedirectUrl($url);
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $list_id = $form_state->getValue('listId');
    $list_options = campaignmonitor_get_list_settings($list_id);

    $values = $form_state->getValues();
    $values = $values['campaignmonitor_list_' . $list_id];
    // These are saved remotely.
    unset($values['options']);

    campaignmonitor_set_list_settings($list_id, $values);

    parent::submitForm($form, $form_state);
  }

}
