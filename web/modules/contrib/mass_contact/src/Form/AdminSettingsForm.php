<?php

namespace Drupal\mass_contact\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\mass_contact\MassContactInterface;

/**
 * Admin settings form for Mass Contact.
 */
class AdminSettingsForm extends SettingsFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mass_contact_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigKeys() {
    return [
      'form_information',
      'recipient_limit',
      'send_with_cron',
      'optout_enabled',
      'create_archive_copy',
      'hourly_threshold',
      'category_display',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('mass_contact.settings');
    $form['form_information'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Additional information for Mass Contact form'),
      '#default_value' => $config->get('form_information'),
      '#description' => $this->t('Information to show on the <a href=":url">Mass Contact page</a>.', [':url' => Url::fromRoute('entity.mass_contact_message.add_form')->toString()]),
    ];

    // Rate limiting options.
    $form['limiting_options'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Rate limiting options'),
      '#description' => $this->t('By combining the two options below, messages sent through this module will be queued to be sent drung cron runs. Keep in mind that if you set your number of recipients to be the same as your limit, messages from this or other modules may be blocked by your hosting provider.'),
    ];
    // The maximum number of users to send to at one time.
    $form['limiting_options']['recipient_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum number of recipients before splitting up the email'),
      '#min' => 0,
      '#default_value' => $config->get('recipient_limit'),
      '#description' => $this->t('This is a workaround for server-side limits on the number of recipients in a single mail message. Once this limit is reached, the recipient list will be broken up and multiple copies of the message will be sent out until all recipients receive the mail. Setting this to 0 (zero) will turn this feature off.'),
      '#required' => TRUE,
    ];
    // The maximum number of users to send to at one time.
    $form['mass_contact_rate_limiting_options']['send_with_cron'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send messages with Cron'),
      '#default_value' => $config->get('send_with_cron'),
      '#description' => $this->t('This is another workaround for server-side limits. Check this box to delay sending until the next Cron run(s).'),
    ];

    // Opt out options.
    $form['mass_contact_optout_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Opt-out options'),
    ];
    // @todo Refactor this when adding optout functionality to utilize a field.
    $form['mass_contact_optout_options']['optout_enabled'] = [
      '#type' => 'radios',
      '#title' => $this->t('Allow users to opt-out of mass email messages'),
      '#default_value' => $config->get('optout_enabled'),
      '#options' => [
        MassContactInterface::OPT_OUT_DISABLED => $this->t('No'),
        MassContactInterface::OPT_OUT_GLOBAL => $this->t('Yes'),
        MassContactInterface::OPT_OUT_CATEGORY => $this->t('Selected categories'),
      ],
      '#description' => $this->t("Allow users to opt-out of receiving mass email messages. If 'No' is chosen, then the site's users will not be able to opt-out of receiving mass email messages. If 'Yes' is chosen, then the site's users will be able to opt-out of receiving mass email messages, and they will not receive any from any category. If 'Selected categories' is chosen, then the site's users will be able to opt-out of receiving mass email messages from which ever categories they choose."),
    ];

    // Node copy options.
    $form['create_archive_copy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Archive messages by saving a copy as a node'),
      '#default_value' => $config->get('create_archive_copy'),
    ];

    // Flood control options.
    $form['hourly_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Hourly threshold'),
      '#min' => 0,
      '#default_value' => $config->get('hourly_threshold'),
      '#description' => $this->t('The maximum number of Mass Contact form submissions a user can perform per hour.'),
    ];

    // Category options.
    $form['category_display'] = [
      '#type' => 'radios',
      '#title' => $this->t('Field to use to display the categories'),
      '#default_value' => $config->get('category_display'),
      '#options' => [
        'select' => 'Select list',
        'checkboxes' => 'Check boxes',
      ],
      '#description' => $this->t("Select the form field to use to display the available categories to the message sender."),
    ];

    return $form;
  }

}
