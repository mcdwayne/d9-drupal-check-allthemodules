<?php

namespace Drupal\urllogin\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements an urllgoin admin form.
 */
class UrlloginForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'urllogin_form';
  }


  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'urllogin.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('urllogin.settings');
    $form['encryption'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Encryption settings'),
      '#description' => $this->t('This page contains all the settings for urllogin.
      However you will also need to add the "login via url" permission to the roles of all users who will
      use this module for logging in.') . '<br />'
        . t('For testing purposes, individual url login strings can be generated from ')
        . t('the status page - see this page for details.'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['encryption']['urllogin_passphrase'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pass phrase'),
      '#description' => $this->t('The passphrase for encoding URL access'),
      '#default_value' => $config->get('urllogin.passphrase'),
      '#size' => 40,
    ];

    /*  Disable Add DB Password to passphrase
        $form['encryption']['urllogin_add_dbpass'] = array(
         '#type' => 'checkbox',
         '#title' => $this->t('Append database access string to passphrase'),
         '#description' => $this->t('Increase security by appending the database access string to the passphrase.
          The only disadvantage is that changing your database password will invalidate all currently
          issued URL access strings. The best solution is to set the password in settings.php.'),
         '#default_value' => $config->get('urllogin.add_dbpass'),
       );
    */

    // @TODO Check if this is neccasrry with D8 config overried system
    if (isset($GLOBALS['urllogin_passphrase'])) { // disable if passphrase set in settings.php
      $form['encryption']['urllogin_add_dbpass']['#disabled'] = TRUE;
      $form['encryption']['urllogin_passphrase']['#disabled'] = TRUE;
      $form['encryption']['urllogin_passphrase']['#title'] = 'Passphrase (not currently used)';
      $form['encryption']['urllogin_passphrase']['#description'] = 'Passphrase has been set in settings.php and overrides this value';
    }

    $form['encryption']['urllogin_codekey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Validation number for generating new URL login strings'),
      '#description' => $this->t('A value between 0 and 2,000,000,000. Suggestion: use current date in yyyymmdd format.'),
      '#default_value' => $config->get('urllogin.codekey'),
      '#size' => 10,
    ];

    $form['encryption']['urllogin_codemin'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Minimum validation number allowed for valid login'),
      '#description' => $this->t('A value between 0 and 2,000,000,000. Suggestion: use oldest valid date in yyyymmdd format.'),
      '#default_value' => $config->get('urllogin.codemin'),
      '#size' => 10,
    ];

    $form['userlist'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Bulk generation of access URLs'),
      '#description' => $this->t('A bulk download of all user logon strings as a tab-separated csv file can be downloaded
      by clicking and saving ')
        . \Drupal\Core\Link::fromTextAndUrl(t('this link. '), \Drupal\Core\Url::fromRoute('urllogin.user_list'))
          ->toString()
        . t('But first set the following options (if required) and <strong><em>save the form</em></strong>.'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['userlist']['urllogin_destination'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Optional destination for bulk generated links'),
      '#description' => $this->t('No leading "/" e.g. blog/my_latest_article'),
      '#default_value' => $config->get('urllogin.destination'),
      '#size' => 50,
    ];

    // @TODO see if this applies to D8
    $form['userlist']['urllogin_useprofile'] = [
      '#type' => 'hidden',
      /* marked hidden until profiles are supported properly in D7 (see end of file) */
      '#title' => $this->t('use "firstname" and "lastname" fields from profile when creating downloaded user list'),
      '#description' => $this->t('Requires the profile module and the creation of fields with the exact names:
       <em>profile_firstname, profile_lastname</em>.'),
      '#default_value' => $config->get('urllogin.useprofile'),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $entry = $form_state->getValue('urllogin_codekey');
    if (preg_match('@^[0-9]+$@', trim($entry)) != 1) { // test for digits
      $form_state->setErrorByName('urllogin_codekey', $this->t('Please enter a positive integer for Validation number.'));
    }
    $entry = $form_state->getValue('urllogin_codemin');
    if (preg_match('@^[0-9]+$@', trim($entry)) != 1) { // test for digits
      $form_state->setErrorByName('urllogin_codemin', $this->t('Please enter a positive integer for Minimum validation number.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration
    $this->configFactory->getEditable('urllogin.settings')
      // Set the submitted configuration settings
      ->set('urllogin.passphrase', $form_state->getValue('urllogin_passphrase'))
      ->set('urllogin.add_dbpass', $form_state->getValue('urllogin_add_dbpass'))
      ->set('urllogin.codekey', $form_state->getValue('urllogin_codekey'))
      ->set('urllogin.codemin', $form_state->getValue('urllogin_codemin'))
      ->set('urllogin.destination', $form_state->getValue('urllogin_destination'))
      ->save();


    parent::submitForm($form, $form_state);
  }
}
