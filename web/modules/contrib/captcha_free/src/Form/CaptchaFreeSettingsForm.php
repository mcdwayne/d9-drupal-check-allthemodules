<?php

/**
 * @file
 * Contains Drupal\captcha_free\Form\CaptchaFreeSettingsForm.
 */

namespace Drupal\captcha_free\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;


/**
 * Displays the Captcha-free settings form.
 */
class CaptchaFreeSettingsForm implements FormInterface {
 /**
   * Get a value from the retrieved form settings array.
   */
  public function getFormSettingsValue($form_settings, $form_id) {
    // If there are settings in the array and the form ID already has a setting,
    // return the saved setting for the form ID.
    if (!empty($form_settings) && isset($form_settings[$form_id])) {
      return $form_settings[$form_id];
    }
    // Default to false.
    else {
      return 0;
    }
  }
  /**
   * {@inheritdoc}
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'captcha_free_settings';
  }

  /**
   * {@inheritdoc}
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
  $form['captcha_free_secret_salt'] = array(
    '#type' => 'textfield',
    '#title' => t('Secret Salt'),
    '#description' => t('Change this phrase to make your \'hash\' or token unique.<br />
      Use: Any short text string will do under 15 characters.'),

    '#default_value' => \Drupal::configFactory()->getEditable('captcha_free.settings')->get('captcha_free_secret_salt'),

    '#required' => TRUE,
    '#size' => 15,
    '#maxlength' => 15,
  );
  $form['captcha_free_time_out'] = array(
    '#type' => 'textfield',
    '#title' => t('Form Timeout'),
    '#description' => t('The number of minutes on the page that the form will be usable.<br />
      The default is 10 minutes, but I use 3 minutes for testing. It\'s your choice.<br />
      Use: Up to 2 digits allowed here.'),
    '#default_value' => \Drupal::configFactory()->getEditable('captcha_free.settings')->get('captcha_free_time_out'),

    '#required' => TRUE,
    '#size' => 2,
    '#maxlength' => 2
  );
  $options = array(
    'contact_message_feedback_form' => 'Site-wide Contact Form', //ok
    'contact_message_personal_form' => 'User Contact Form', //ok
    'user_register_form' => 'User Register Form', //ok
    'user_login_form' => 'User Login Form', //ok
    'comment' => 'All Comment Forms', //ok
    //'webform' => 'Webforms (All)', // waiting on d8 port of Webform module.
    'user_pass' => 'Password Request Form',//ok jQuery not called on user/password for some reason
 );
  $form['protect_forms'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Forms to protect'),
'#description' => t('Select one or more from the list. Start with the User Register Form<br /> and add other forms as they start being targeted by bots.'),
    '#default_value' => \Drupal::configFactory()->getEditable('captcha_free.settings')->get('protect_forms'),
    '#options' => $options,
    //'#weight' => 20,
    '#required' => TRUE,
 );
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
    //'#weight' => 21,
 );
 return $form;
}

/**
 * Implements \Drupal\Core\Form\FormInterface:validateForm()
 * This is for the Admin settings form.
 */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  $time = $form_state->getValues()['captcha_free_time_out'];

  if (!ctype_digit($time)) {
    $form_state->setErrorByName('captcha_free_time_out', t('Please use a 1 or 2 digit value for Timeout'));
  }
  }

  /**
   * {@inheritdoc}
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */

  public function submitForm(array &$form, FormStateInterface $form_state) {
  drupal_set_message(t('The configuration options have been saved.'));
  \Drupal::configFactory()->getEditable('captcha_free.settings')
    ->set('captcha_free_secret_salt', $form_state->getValues()['captcha_free_secret_salt'])
    ->set('captcha_free_time_out', $form_state->getValues()['captcha_free_time_out'])
    ->set('protect_forms', $form_state->getValues()['protect_forms'])
    ->save();

  return;
}
}