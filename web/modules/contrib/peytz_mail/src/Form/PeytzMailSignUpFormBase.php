<?php

namespace Drupal\peytz_mail\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Represents the Peytz Mail signup form.
 */
abstract class PeytzMailSignUpFormBase extends FormBase {

  /**
   * PeytzMailSignUpFormBase constructor.
   *
   * @param array $form_configuration
   *   Signup form configuration settings.
   */
  public function __construct(array $form_configuration) {}

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $configuration = []) {

    $form = [];

    $form_state->setStorage([
      'thank_you_page' => $configuration['thank_you_page'],
      'use_subscription_queue' => $configuration['use_subscription_queue'],
    ]);

    if (!empty($configuration['header'])) {
      $form['header'] = [
        '#prefix' => '<div class="header-text">',
        '#suffix' => '</div>',
        '#markup' => $configuration['header'],
      ];
    }

    if (!empty($configuration['intro_text'])) {
      $form['intro_text'] = [
        '#prefix' => '<div class="intro-text">',
        '#suffix' => '</div>',
        '#markup' => $configuration['intro_text'],
      ];
    }

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
    ];

    if (!empty($configuration['name_field_setting']) && $configuration['name_field_setting'] !== 'none') {
      if ($configuration['name_field_setting'] == 'single') {
        $form['full_name'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Full name'),
          '#required' => TRUE,
        ];
      }
      elseif ($configuration['name_field_setting'] == 'double') {
        $form['first_name'] = [
          '#type' => 'textfield',
          '#title' => $this->t('First name'),
          '#required' => TRUE,
        ];
        $form['last_name'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Last name'),
          '#required' => TRUE,
        ];
      }
    }

    // Let other modules add form fields.
    $custom_form_fields = \Drupal::moduleHandler()->invokeAll('peytz_mail_form_fields', [$configuration]);
    $form += $custom_form_fields;

    $newsletter_list_options = [];
    if (!empty($configuration['newsletter_lists'])) {
      foreach ($configuration['newsletter_lists'] as $list) {
        $newsletter_list_options[$list['newsletter_machine_name']] = $list['newsletter_name'];
      }
    }

    // Additional confirmation.
    if (!empty($configuration['confirmation_checkbox_text'])) {
      $form['agree'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('@checkbox_text', ['@checkbox_text' => $configuration['confirmation_checkbox_text']]),
        '#required' => TRUE,
      ];
    }

    if (count($newsletter_list_options) == 1 && $configuration['hide_newsletter_lists']) {
      $list = reset($configuration['newsletter_lists']);
      $form['newsletter_signup_lists'] = [
        '#type' => 'hidden',
        '#value' => $list['newsletter_machine_name'],
      ];
    }
    else {
      $form['newsletter_signup_lists'] = [
        '#type' => $configuration['multiple_newsletter_lists'] ? 'checkboxes' : 'radios',
        '#title' => t('Newsletters'),
        '#options' => $newsletter_list_options,
        '#required' => TRUE,
      ];
    }

    $form['skip_confirm'] = [
      '#type' => 'hidden',
      '#value' => $configuration['skip_confirm'] ? TRUE : FALSE,
    ];

    $form['skip_welcome'] = [
      '#type' => 'hidden',
      '#value' => $configuration['skip_welcome'] ? TRUE : FALSE,
    ];

    $form['subscribe'] = [
      '#type' => 'submit',
      '#value' => $this->t('Sign up'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $form_state->cleanValues();
    $submitted_form_values = $form_state->getValues();

    $keys = array_keys($submitted_form_values);

    $parameters = [];
    $parameters['subscriber']['email'] = $submitted_form_values['email'];

    if (!empty($submitted_form_values['full_name'])) {
      // Only single name field allowed.
      $parameters['subscriber']['full_name'] = $submitted_form_values['full_name'];
    }
    elseif (!empty($submitted_form_values['first_name'])) {
      // Name field is configured into two fields.
      $parameters['subscriber']['first_name'] = $submitted_form_values['first_name'];
      $parameters['subscriber']['last_name'] = $submitted_form_values['last_name'];
    }

    $mailing_list = $submitted_form_values['newsletter_signup_lists'];
    $mailing_list = is_array($mailing_list) ? $mailing_list : [$mailing_list];
    $selected_mailing_list = [];
    foreach ($mailing_list as $mailing_list_id) {
      if (!empty($mailing_list_id)) {
        $selected_mailing_list[] = $mailing_list_id;
      }
    }
    $parameters['mailinglist_ids'] = $selected_mailing_list;

    $parameters['skip_confirm'] = $submitted_form_values['skip_confirm'];
    $parameters['skip_welcome'] = $submitted_form_values['skip_welcome'];

    $custom_fields = preg_grep('/^peytz_mail_custom_field_/', $keys);
    $custom_fields = array_values($custom_fields);

    foreach ($custom_fields as $field) {
      if (!empty($submitted_form_values[$field])) {
        $parameters['subscriber'][str_replace('peytz_mail_custom_field_', '', $field)] = $submitted_form_values[$field];
      }
    }

    $storage = $form_state->getStorage();

    // Send subscription request right away if configured to do that,
    // queue the request otherwise.
    if (empty($storage['use_subscription_queue'])) {
      try {
        \Drupal::service('peytz_mail.peytzmailer')->subscribe($parameters);
        $response_code = \Drupal::service('peytz_mail.peytzmailer')->getResponseCode();
        if ($response_code < 400) {
          drupal_set_message($this->t('Congratulations @name, you have been subscribed to @mailinglist.', [
            '@name' => isset($parameters['subscriber']['full_name']) ? $parameters['subscriber']['full_name'] :
            (isset($parameters['subscriber']['first_name']) ? $parameters['subscriber']['first_name'] : ''),
            '@mailinglist' => implode(', ', $parameters['mailinglist_ids']),
          ]));
        }
        elseif ($response_code == 422) {
          $msg = $this->t('The email you provided is not valid.');
          drupal_set_message($msg, 'error');
          \Drupal::logger('peytz_mail')->notice($msg);
        }
        else {
          $msg = $this->t('Peytz mail error subscribing user with Email @email,  @error_message, @error_code', [
            '@email' => $parameters['subscriber']['email'],
            '@error_message' => \Drupal::service('peytz_mail.peytzmailer')->getResponseBody()->message,
            '@error_code' => \Drupal::service('peytz_mail.peytzmailer')->getResponseCode(),
          ]);
          drupal_set_message($msg, 'error');
          \Drupal::logger('peytz_mail')->notice($msg);
        }
      }
      catch (\Exception $e) {
        drupal_set_message($e->getMessage());
        watchdog_exception('peytz_mail', $e);
      }
    }
    else {
      $queue_factory = \Drupal::service('queue');
      $queue = $queue_factory->get('peytz_mail_subscribe_worker_cron');
      $item = new \stdClass();
      $item->parameters = $parameters;
      $queue->createItem($item);
      drupal_set_message($this->t('Congratulations @name, you have been subscribed to @mailinglist.', [
        '@name' => isset($parameters['subscriber']['full_name']) ? $parameters['subscriber']['full_name'] :
        (isset($parameters['subscriber']['first_name']) ? $parameters['subscriber']['first_name'] : ''),
        '@mailinglist' => implode(', ', $parameters['mailinglist_ids']),
      ]));
    }

    if (!empty($storage['thank_you_page'])) {
      $form_state->setRedirectUrl(Url::fromUri('base://' . $storage['thank_you_page']));
    }

  }

}
