<?php

namespace Drupal\campaignmonitor\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\user\Entity\User;

/**
 * Subscribe to a campaignmonitor list.
 */
class CampaignMonitorSubscribeForm extends FormBase {

  /**
   * The ID for this form.
   * Set as class property so it can be overwritten as needed.
   *
   * @var string
   */
  private $formId = 'campaignmonitor_subscribe';

  /**
   * The campaignmonitorListsSubscription field instance used to build this form.
   *
   * @var campaignmonitorListsSubscription
   */
  private $fieldInstance;

  /**
   * A reference to the field formatter used to build this form.
   * Used to get field configuration.
   *
   * @var campaignmonitorListsFieldSubscribeFormatter
   */
  private $fieldFormatter;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->formId;
  }

  /**
   *
   */
  public function setFormId($formId) {
    $this->formId = $formId;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['campaignmonitor.subscribe'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $config = []) {

    // Try to get the e-mail address from the user object.
    if (\Drupal::currentUser()->id() != 0) {
      $account = User::load(\Drupal::currentUser()->id());
      $email = $account->get('mail')->getValue()[0]['value'];
    }

    $form['email'] = [
      '#type' => 'textfield',
      '#title' => t('Email'),
      '#required' => TRUE,
      '#maxlength' => 200,
      '#default_value' => isset($email) ? $email : '',
    ];

    switch ($config['list']) {
      case 'single':
        $form += $this->singleSubscribeForm($form, $form_state, $config);
        $this->setFormID($this->formId . '_single');
        break;

      default:
        $form += $this->userSelectSubscribeForm($form, $form_state, $config);
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Subscribe'),
    ];

    $form['config'] = [
      '#type' => 'hidden',
      '#value' => serialize($config),
    ];

    return $form;
  }

  /**
   *
   */
  protected function singleSubscribeForm(array $form, FormStateInterface $form_state, $config = []) {

    $list_id = $config['list_id'];

    $current_user = \Drupal::currentUser();

    $list = campaignmonitor_get_extended_list_settings($list_id);
    $list_options = campaignmonitor_get_list_settings($list_id);

    // Set options for the form.
    $form = [
      '#tree' => TRUE,
      '#attributes' => [
        'class' => [
          'campaignmonitor-subscribe-form',
          'campaignmonitor-subscribe-form-' . str_replace(' ', '-', strtolower($list['name'])),
        ],
      ],
    ];

    if ($config['list_id_text'] != '') {
      $text = str_replace('@name', $list['name'], $config['list_id_text']);
      $form['selection'] = [
        '#markup' => $text,
      ];
    }

    // Should the name field be displayed for this user.
    if (isset($list_options['display']['name']) && $list_options['display']['name']) {
      // Token replace if the token module is present.
      if (isset($list_options['tokens']['name']) && \Drupal::moduleHandler()->moduleExists('token') &&
        $current_user->id() > 0) {
        $name = \Drupal::token()->replace($list_options['tokens']['name'], [], ['clear' => TRUE]);
      }

      // Check if the user is subscribed and get name from Campaign Monitor.
      if (!empty($email) && campaignmonitor_is_subscribed($list_id, $email)) {
        // If subscribed, get her/his name from Campaign Monitor.
        $subscriber = campaignmonitor_get_subscriber($list_id, $email);
        $name = isset($subscriber['Name']) ? $subscriber['Name'] : $name;
      }

      $form['name'] = [
        '#type' => 'textfield',
        '#title' => t('Name'),
        '#required' => TRUE,
        '#maxlength' => 200,
        '#default_value' => isset($name) ? $name : '',
      ];
    }

    if (isset($list['CustomFields'])) {
      foreach ($list['CustomFields'] as $key => $field) {
        // Form API can't handle keys with [] in all cases.
        $form_key = str_replace(['[', ']'], '', $key);

        // Check if field should be displayed.
        if (isset($list_options['CustomFields']) && !$list_options['CustomFields']['selected'][$form_key]) {
          // Field is not selected, so continue.
          continue;
        }

        // Token replace default value, if the token module is present.
        $token = '';
        if (\Drupal::moduleHandler()->moduleExists('token') && isset($list_options['tokens'][$form_key])) {
          $token = \Drupal::token()->replace($list_options['tokens'][$form_key]);
        }

        switch ($field['DataType']) {
          case 'Text':
            $form['CustomFields'][$form_key] = [
              '#type' => 'textfield',
              '#title' => SafeMarkup::checkPlain($field['FieldName']),
              '#maxlength' => 200,
              '#default_value' => isset($subscriber['CustomFields'][$field['FieldName']]) ? $subscriber['CustomFields'][$field['FieldName']] : $token,
            ];
            break;

          case 'MultiSelectOne':
            $options = [];
            foreach ($field['FieldOptions'] as $option) {
              $options[$option] = $option;
            }

            $form['CustomFields'][$form_key] = [
              '#type' => 'select',
              '#title' => SafeMarkup::checkPlain($field['FieldName']),
              '#options' => $options,
              '#default_value' => isset($subscriber['CustomFields'][$field['FieldName']]) ? $subscriber['CustomFields'][$field['FieldName']] : $token,
            ];
            break;

          case 'MultiSelectMany':
            $options = [];
            foreach ($field['FieldOptions'] as $option) {
              $options[$option] = $option;
            }

            // If one value was selected, default is a string else an array.
            $cm_default = isset($subscriber['CustomFields'][$field['FieldName']]) ? $subscriber['CustomFields'][$field['FieldName']] : [];
            // Exspensive.
            $is_array = is_array($cm_default);
            $default = [];
            foreach ($options as $value) {
              if ($is_array) {
                if (in_array($value, $cm_default)) {
                  $default[$value] = $value;
                }
              }
              elseif ($cm_default == $value) {
                $default[$cm_default] = $cm_default;
              }
              else {
                $default[$value] = 0;
              }
            }

            $form['CustomFields'][$form_key] = [
              '#type' => 'checkboxes',
              '#title' => SafeMarkup::checkPlain($field['FieldName']),
              '#options' => $options,
              '#default_value' => $default,
            ];
            break;

          case 'Number':
            $form['CustomFields'][$form_key] = [
              '#type' => 'textfield',
              '#title' => SafeMarkup::checkPlain($field['FieldName']),
              '#default_value' => isset($subscriber['CustomFields'][$field['FieldName']]) ? $subscriber['CustomFields'][$field['FieldName']] : $token,
            ];
            break;

          case 'Date':
            // Load jQuery datepicker to ensure the right date format.
            drupal_add_library('system', 'ui.datepicker');
            $form['CustomFields'][$form_key] = [
              '#type' => 'date_popup',
              '#title' => SafeMarkup::checkPlain($field['FieldName']),
              '#default_value' => isset($subscriber['CustomFields'][$field['FieldName']]) ? $subscriber['CustomFields'][$field['FieldName']] : $token,
              '#attributes' => ['class' => ['campaignmonitor-date']],
            ];
            break;
        }
      }

    }
    $form['list_id'] = [
      '#type' => 'hidden',
      '#default_value' => $list_id,
    ];

    return $form;

  }

  /**
   *
   */
  protected function userSelectSubscribeForm(array $form, FormStateInterface $form_state, $config = []) {
    $form = [];

    // Set options for the form.
    $form = [
      '#tree' => TRUE,
      '#attributes' => [
        'class' => [
          'campaignmonitor-subscribe-form',
          'campaignmonitor-subscribe-form-all-lists',
        ],
      ],
    ];

    $lists = campaignmonitor_get_lists();

    $options = [];
    foreach ($lists as $list_id => $list) {
      $options[$list_id] = $list['name'];
    }

    $form['selection'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#title' => t('Lists'),
    // '#required' => TRUE,.
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $lists = campaignmonitor_get_lists();

    $values = $form_state->getValues();

    $config = $form_state->getValue('config');
    $config = unserialize($config);

    switch ($config['list']) {
      case 'single':
        $selection = [$form_state->getValue('list_id')];
        break;

      default:
        $selection = $form_state->getValue('selection');
    }

    $custom_fields = isset($values['CustomFields']) ? $values['CustomFields'] : NULL;
    $name = isset($values['name']) ? SafeMarkup::checkPlain($values['name'])->__toString() : NULL;
    $email = SafeMarkup::checkPlain($values['email'])->__toString();

    foreach ($selection as $list_id) {

      if ($list_id === 0) {
        continue;
      }

      if (campaignmonitor_subscribe($list_id, $email, $name, $custom_fields)) {
        drupal_set_message(t('You are subscribed to the @name list.', [
          '@name' => html_entity_decode($lists[$list_id]['name']),
        ]));
      }
      else {
        drupal_set_message(t('You were not subscribed to the list, please try again.'));
      }
    }

  }

}
