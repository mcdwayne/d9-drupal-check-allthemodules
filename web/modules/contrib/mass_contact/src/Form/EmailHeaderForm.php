<?php

namespace Drupal\mass_contact\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Email header settings form.
 */
class EmailHeaderForm extends SettingsFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getConfigKeys() {
    return [
      'character_set',
      'default_sender_name',
      'default_sender_email',
      'include_from_name',
      'include_to_name',
      'use_bcc',
      'category_override',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mass_contact_email_header_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('mass_contact.settings');
    // The default character set.
    $form['character_set'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Character set'),
      '#default_value' => $config->get('character_set'),
      '#description' => $this->t('You may specify an alternate character set to use when sending emails. If left blank, the default of UTF-8 will be used. If you are unsure of what to put here, then leave it blank. Caution: setting this may not get you the results you desire. Other modules may come along and change that value after it has been set by this module.'),
    ];

    // The sender's name and email address.
    $form['mass_contact_default_sender'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Default sender information'),
      '#description' => $this->t('If anything is specified in here, it is used in place of the "Your name" and "Your email address" fields when sending the mass email. Otherwise, the sender\'s name and email address will be the default values. You must fill in both values, if you want to specify a default.'),
    ];
    $form['mass_contact_default_sender']['default_sender_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default sender name'),
      '#default_value' => $config->get('default_sender_name'),
      '#maxlength' => 128,
      '#description' => $this->t('The optional user name to send email as. Replaces the "Your name" value when sending mass emails.'),
    ];
    $form['mass_contact_default_sender']['default_sender_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Default sender email address'),
      '#default_value' => $config->get('default_sender_email'),
      '#description' => $this->t('The optional user email address to send email as. Replaces the "Your email address" value when sending mass emails.'),
    ];

    // Sender name options.
    $form['mass_contact_include_name'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Include names with email addresses'),
      '#description' => $this->t("Checking either of the boxes below will include the name along with the email address, in the form of '%address'. If you have problems with sending mail, especially when your site is on a Windows server, try unchecking both checkboxes.", ['%address' => 'User name <email.address@example.com>']),
    ];
    $form['mass_contact_include_name']['include_from_name'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include the name for the sender'),
      '#default_value' => $config->get('include_from_name'),
    ];
    $form['mass_contact_include_name']['include_to_name'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include the names for the recipients'),
      '#default_value' => $config->get('include_to_name'),
      '#description' => $this->t("The name used for the recipients will be their site login ID."),
    ];

    // BCC options.
    $form['use_bcc'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send as BCC (hide recipients) by default'),
      '#default_value' => $config->get('use_bcc'),
    ];

    // More category options.
    $form['category_override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include category in subject line'),
      '#default_value' => \Drupal::config('mass_contact.settings')->get('category_override'),
      '#description' => $this->t("If you choose this option, the category name will be printed in square brackets preceeding the message sender's subject.
                        If the message sender has multiple categories selected with this option choosen, each category will be processed separately.
                        If you do not choose this option and the message sender has multiple categories selected, all users will be grouped together and the message will be sent to everyone as one group, thus reducing the likelihood of sending duplicates."),
    ];

    return $form;
  }

}
