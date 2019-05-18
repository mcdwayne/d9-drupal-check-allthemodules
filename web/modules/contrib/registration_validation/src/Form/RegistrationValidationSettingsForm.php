<?php

namespace Drupal\registration_validation\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure registration validation settings.
 */
class RegistrationValidationSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'registration_validation_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['registration.validation'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['blacklist_domains'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Blacklist email domains'),
      '#description' => $this->t('Often times you will find specific domains getting past CAPTCHA modules, if they are installed. When attempting to register with any email domains you enter below, the registration will be blocked and a message will appear informing the user to use a different email address.'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['blacklist_domains']['domains'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Blacklisted email domains'),
      '#description' => $this->t('Specify blacklisted email domains by using their domain name and top-level domain (.com, .org, .info, etc.). Enter one domain per line. Example domains are <em class="placeholder">example.com</em> for <em class="placeholder">@example.com</em> email addresses and <em class="placeholder">example.*</em> for all <em class="placeholder">@example.*</em> email addresses (.com, .org, .info, etc.).'),
      '#default_value' => \Drupal::config('registration_validation.settings')->get('domains'),
    ];
    $form['blacklist_domains']['domains_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Failed validation error message'),
      '#description' => $this->t('Enter the message to display on the page if the user\'s email address domain is blacklisted. You can enter "%domain" (without quotes) anywhere in the message to print the domain name.<br>Default message: <em class="placeholder">The email domain %domain has been blacklisted from registering. Please enter a different email address.</em>'),
      '#default_value' => \Drupal::config('registration_validation.settings')->get('domains_message'),
    ];
    $form['blacklist_usernames'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Blacklist usernames'),
      '#description' => $this->t('When attempting to register a username with any strings you enter below, the registration will be blocked and a message will appear informing the user to use a different username.'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['blacklist_usernames']['usernames'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Blacklisted username strings'),
      '#description' => $this->t('Specify blacklisted strings in usernames. Enter one domain per line. Example strings are <em class="placeholder">example</em> for example as the complete username and <em class="placeholder">*example*</em> for example appearing anywhere in the username.'),
      '#default_value' => \Drupal::config('registration_validation.settings')->get('usernames'),
    ];
    $form['blacklist_usernames']['usernames_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Failed validation error message'),
      '#description' => $this->t('Enter the message to display on the page if the username is blacklisted or contains a blacklisted string. You can enter "%username" (without quotes) anywhere in the message to print the username or string.<br>Default message: <em class="placeholder">The username contains %username, which has been blacklisted from being used when registering. Please enter a different username.</em>'),
      '#default_value' => \Drupal::config('registration_validation.settings')->get('usernames_message'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('registration_validation.settings')
      ->set('domains', $form_state->getValue('domains'))
      ->set('domains_message', $form_state->getValue('domains_message'))
      ->set('usernames', $form_state->getValue('usernames'))
      ->set('usernames_message', $form_state->getValue('usernames_message'));
    $config->save();

    parent::submitForm($form, $form_state);
  }
}
