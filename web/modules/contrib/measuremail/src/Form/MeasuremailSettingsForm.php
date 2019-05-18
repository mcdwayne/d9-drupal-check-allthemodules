<?php

/**
 * @file
 * Contains \Drupal\measuremail\Form\MeasuremailSettingsForm.
 */

namespace Drupal\measuremail\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;

/**
 * Measuremail form settings.
 */
class MeasuremailSettingsForm extends MeasuremailFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\measuremail\MeasuremailInterface $measuremail */
    $measuremail = $this->entity;
    $settings = $measuremail->getSettings();

    $form = [];

    // ENDPOINT Settings.
    $form['settings'] = [
      '#type' => 'fieldgroup',
      '#title' => t('Endpoint settings'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#tree' => TRUE,
    ];
    $form['settings']['endpoint'] = [
      '#type' => 'textfield',
      '#title' => t('URL for service endpoint'),
      '#default_value' => $settings['endpoint'],
      '#description' => t('Enter the URL for the Measuremail endpoint (e.g: https://action.spike.email/{environment_number}/Subscriptions/Subscribe)'),
      '#required' => TRUE,
      '#maxlength' => 255,
    ];
    $form['settings']['id'] = [
      '#type' => 'textfield',
      '#title' => t('Subscription ID'),
      '#default_value' => $settings['id'],
      '#description' => t('ID as set on "Subscriptions" > "Channels" > "Subscription ID"'),
      '#required' => TRUE,
      '#maxlength' => 255,
    ];

    $form['settings']['email_field'] = [
      '#type' => 'textfield',
      '#title' => t('Email field'),
      '#default_value' => $settings['email_field'],
      '#description' => t('Email Field ID, as set on Measuremail. This is a mandatory field and you can define it\'s position by creating an element with the same id.'),
      '#required' => TRUE,
      '#maxlength' => 255,
    ];
    // FIELDS Settings.
    if (\Drupal::moduleHandler()->moduleExists('language')) {
      /** @var Language $language */
      foreach (\Drupal::service('language_manager')
                 ->getLanguages() as $langcode => $language) {
        $languages_enabled_options[$langcode] = $language->getName();
      }
      $form['settings']['languages_enabled'] = [
        '#type' => 'checkboxes',
        '#title' => t('Enabled languages'),
        '#options' => $languages_enabled_options,
        '#default_value' => (is_array($settings['languages_enabled']) ? $settings['languages_enabled'] : []),
        '#description' => t('Choose the languages on which to enable the subscription page. Leave it unselected to enable it for all languages.'),
      ];
    }
    $form['settings']['submit_button'] = [
      '#type' => 'textfield',
      '#title' => t('Submit value'),
      '#default_value' => $settings['submit_button'],
      '#description' => t('Enter the text value that should appear on the submit button.'),
      '#required' => TRUE,
    ];
    // GDPR Required Settings.
    $form['settings']['formversion'] = [
      '#type' => 'textfield',
      '#title' => t('Form Version'),
      '#default_value' => $settings['formversion'],
      '#description' => t('Measure mail subscription form version.'),
    ];
    $form['settings']['privacyurl'] = [
      '#type' => 'textfield',
      '#title' => t('Privacy Policy page Url'),
      '#default_value' => $settings['privacyurl'],
      '#description' => t('Absolute link to the privacy policy URL.'),
    ];
    $form['settings']['privacyversion'] = [
      '#type' => 'textfield',
      '#title' => t('Privacy Policy Version'),
      '#default_value' => $settings['privacyversion'],
    ];

    // MESSAGE Settings.
    $callbacktype_enabled_options = [
      'newpage' => t('New Page'),
      'inlinemessage' => t('Inline Message, using default Drupal Message System'),
    ];
    $form['settings']['callback_type'] = [
      '#type' => 'radios',
      '#title' => t('Callback type'),
      '#default_value' => $settings['callback_type'],
      '#description' => t('Select the action to take after the form submission.'),
      '#required' => TRUE,
      '#options' => $callbacktype_enabled_options,
    ];
    $form['settings']['callback_url'] = [
      '#type' => 'textfield',
      '#title' => t('Callback Url'),
      '#default_value' => $settings['callback_url'],
      '#description' => t('This should be set if "New Page" is defined as a callback type.'),
      '#states' => [
        'visible' => [
          ':input[name="settings[callback_type]"]' => ['value' => 'newpage'],
        ],
        'required' => [
          ':input[name="settings[callback_type]"]' => ['value' => 'newpage'],
        ],
      ],
    ];
    $form['settings']['message_success'] = [
      '#type' => 'textarea',
      '#title' => t('Success'),
      '#default_value' => $settings['message_success'],
      '#description' => t('Enter the message that will be displayed if everything went well.'),
      '#required' => TRUE,
    ];
    $form['settings']['message_update'] = [
      '#type' => 'textarea',
      '#title' => t('Update'),
      '#default_value' => $settings['message_update'],
      '#description' => t('Enter the message that will be displayed if the user already subscribed.'),
      '#required' => TRUE,
    ];
    $form['settings']['message_error'] = [
      '#type' => 'textarea',
      '#title' => t('Error'),
      '#default_value' => $settings['message_error'],
      '#description' => t('Enter the message that will be displayed if an error is returned by Measuremail.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->entity->toUrl('settings'));
    drupal_set_message($this->t('Changes to the Measuremail Form Settings have been saved.'));
  }


}
