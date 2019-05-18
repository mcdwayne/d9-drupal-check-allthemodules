<?php

/**
 * @FILE:
 * Contains \Drupal\googleqrcode\Form\GoogleQRCodeForm
 *
 * Administrative settings for Google QR Code.
 */

namespace Drupal\apply_for_role\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;


class ApplyForRoleAdminForm extends ConfigFormBase{

  // Establish form ID.
  public function getFormID(){
    return 'apply_for_role_admin_form';
  }

  // Establish what values are editable via the form.
  public function getEditableConfigNames()
  {
    return [
      'apply_for_role.settings',
    ];
  }

  // Build out the form.
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    // Load configuration values
    $config = $this->config('apply_for_role.settings');

    $form['options'] = array(
      '#type' => 'fieldset',
      '#title' => t('Apply for role options'),
    );

    $form['options']['multiple_roles_per_app'] = array(
      '#type' => 'radios',
      '#title' => t('Allow multiple roles per application'),
      '#options' => array(t('No'), t('Yes')),
      '#default_value' => $this->get_setting('multiple_roles_per_app', 0, $config),
      '#description' => t("Choosing 'no' will limit users to applying for only one role per role application. Choosing 'yes' will allow users to apply for multiple roles per role application."),
      '#required' => TRUE,
    );

    // @TODO: Build out this functionality.
//    $form['options']['apply_for_role_on_registration_form'] = array(
//      '#type' => 'radios',
//      '#title' => t('Apply for role on registration'),
//      '#options' => array(t('No'), t('Optional'), t('Required')),
//      '#default_value' => $this->get_setting('apply_for_role_on_registration_form', 0, $config),
//      '#description' => t("Choosing 'optional' will allow users to apply for roles when creating a new account. Choosing 'required' will require users to apply for roles when creating a new account."),
//      '#required' => TRUE,
//    );
    // @TODO: Build out this functionality.
//    $form['options']['display_approved_roles_app_form'] = array(
//      '#type' => 'radios',
//      '#title' => t('Display approved roles in an application form'),
//      '#options' => array(t('No'), t('Yes')),
//      '#default_value' => $this->get_setting('display_approved_roles_app_form', 0, $config),
//      // @TODO: Add clarification to this description, might be vague?
//      '#description' => t("Choosing 'yes' will allow a user to see which role applications were approved."),
//      '#required' => TRUE,
//    );
    $form['options']['allow_user_message_with_app'] = array(
      '#type' => 'radios',
      '#title' => t('Allow application message'),
      '#options' => array(t('No'), t('Yes')),
      '#default_value' => $this->get_setting('allow_user_message_with_app', 0, $config),
      '#description' => t("Allows applicants to submit a message along with each application, explaining why they need the role."),
    );

    // Get all user roles, excluding anonymous.
    $roles = user_role_names(TRUE);
    unset($roles['authenticated']);

    $form['options']['apply_for_role_roles'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Roles'),
      '#options' => $roles,
      '#default_value' => $this->get_setting('apply_for_role_roles', array(), $config),
      '#description' => t('Select the roles that users will be able to apply for.'),
      '#required' => TRUE,
    );

    $form['options']['user_apply_form_description'] = array(
      '#type' => 'textarea',
      '#title' => t('Apply for role description/instructions for visitors'),
      '#description' => t('All text entered here will be displayed in the apply for role form, both in page and in block listings.'),
      '#default_value' => $this->get_setting('user_apply_form_description', '', $config),
    );

    $form['email'] = array(
      '#type' => 'fieldset',
      '#title' => t('Apply for role email options'),
      '#description' => t('Configure emails that apply for role can possibly send out, including enabled/disabled status.'),
    );

    $form['email']['apply_for_role_email_admin_content'] = array(
      '#type' => 'details',
      '#title' => t('Admin email settings'),
      '#open' => FALSE,
    );

    $form['email']['apply_for_role_email_admin_content']['send_email_to_admin'] = array(
      '#type' => 'checkbox',
      '#title' => t('Send administrators email on request'),
      '#default_value' => $this->get_setting('send_email_to_admin', 0, $config),
    );

    $form['email']['apply_for_role_email_admin_content']['admin_email_addresses'] = array(
      '#type' => 'textfield',
      '#title' => t('Admin email addressess'),
      '#description' => t('A comma seperated list of emails -OR- Leave blank to use site admin (UID 1) email address.'),
      '#default_value' => $this->get_setting('admin_email_addresses', '', $config),
      '#size' => 60,
      '#maxlength' => 128
    );
    $form['email']['apply_for_role_email_admin_content']['admin_email_subject'] = array(
      '#type' => 'textfield',
      '#title' => t('Admin email subject'),
      '#default_value' => $this->get_setting('admin_email_subject', 'New role application.', $config),
      '#size' => 60,
      '#maxlength' => 128
    );
    $form['email']['apply_for_role_email_admin_content']['admin_email_body'] = array(
      '#type' => 'textarea',
      '#title' => t('Admin email message body'),
      '#description' => 'Body of the email. Use %USER for user name, and %ROLE for role(s) applied for. NO HTML ALLOWED without a custom extension of drupal.',
      '#default_value' => $this->get_setting('admin_email_body', 'An application has been submit by %USER for %ROLE.', $config),
      '#size' => 60,
    );

    $form['email']['send_approve_email_content'] = array(
      '#type' => 'details',
      '#title' => t('User Approval Email Settings'),
      '#open' => FALSE,
    );
    $form['email']['send_approve_email_content']['send_user_approval_email'] = array(
      '#type' => 'checkbox',
      '#title' => t('Send email on approval'),
      '#default_value' => $this->get_setting('send_user_approval_email', 0, $config),
    );

    $form['email']['send_approve_email_content']['send_user_approval_subject'] = array(
      '#type' => 'textfield',
      '#title' => t('Subject'),
      '#default_value' => $this->get_setting('send_user_approval_subject', 'Your role application has been approved.', $config),
      '#size' => 60,
      '#maxlength' => 128
    );
    $form['email']['send_approve_email_content']['send_user_approval_body'] = array(
      '#type' => 'textarea',
      '#title' => t('Message Body'),
      '#description' => 'Body of the email. Use %URL for your site URL, and %ROLE for approved role(s). NO HTML ALLOWED without a custom extension of drupal.',
      '#default_value' => $this->get_setting('send_user_approval_body', 'Your role application has been approved at %URL for %ROLE.', $config),
      '#size' => 60,
    );

    $form['email']['send_denial_email_content'] = array(
      '#type' => 'details',
      '#title' => t('User Denial Email Settings'),
      '#open' => FALSE,
    );

    $form['email']['send_denial_email_content']['send_user_deny_email'] = array(
      '#type' => 'checkbox',
      '#title' => t('Send email on Denial'),
      '#default_value' => $this->get_setting('send_user_deny_email', 0, $config),
    );

    $form['email']['send_denial_email_content']['send_user_deny_subject'] = array(
      '#type' => 'textfield',
      '#title' => t('Subject'),
      '#default_value' => $this->get_setting('send_user_deny_subject', 'Your role application has been denied.', $config),
      '#size' => 60,
      '#maxlength' => 128
    );
    $form['email']['send_denial_email_content']['send_user_deny_body'] = array(
      '#type' => 'textarea',
      '#title' => t('Message Body'),
      '#description' => 'Body of the email. Use %URL for your site URL, and %ROLE for denied role(s). NO HTML ALLOWED without a custom extension of drupal.',
      '#default_value' => $this->get_setting('send_user_deny_body', 'Your role application has been denied at %URL for %ROLE.', $config),
      '#size' => 60,
    );

    // Expand all collapsed detail sets if they are enabled.
    $this->expand_enabled_email_fieldsets($form, $config);

    return parent::buildForm($form, $form_state);
  }

  // Form validation.
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    // @TODO: Add form validation if desired.

    parent::validateForm($form, $form_state);
  }

  // Submit form processing function.
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    // Load configuration object
    $config = $this->config('apply_for_role.settings');

    // Get all submitted values, clean so just user inputted values.
    $submitted_values = $form_state->cleanValues()->getValues();

    // Loop through all submitted values, assigning to corresponding config value.
    foreach($submitted_values as $submitted_value_key => $submitted_value){
      $config->set($submitted_value_key, $submitted_value);
    }

    // Save all of the updated the values.
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Helper function to get default settings.
   *
   * @param $key
   *  Config value key to attempt to find default for.
   * @param mixed $default
   *   Whatever default value you desire if no config value exists.
   * @param object $config
   *   Loaded config object for module
   * @return mixed
   *  Returns either config value or default in whatever format it may be in.
   */
  protected function get_setting($key, $default, $config){
    return $config->get($key) ? $config->get($key) : $default;
  }

  /**
   * Helper function that collapse all email fields if they are enabled or disabled.
   *
   * @param array $form
   *   Form render array passed by reference for performing checks against.
   */
  protected function expand_enabled_email_fieldsets(&$form, $config){
    // Expand admin email if checked.
    if($config->get('send_email_to_admin')){
      $form['email']['apply_for_role_email_admin_content']['#open'] = TRUE;
    }
    // Expand user approval email if enabled.
    if($config->get('send_user_approval_email')){
      $form['email']['send_approve_email_content']['#open'] = TRUE;
    }
    // Expand user Denial email if enabled
    if($config->get('send_user_deny_email')){
      $form['email']['send_denial_email_content']['#open'] = TRUE;
    }
  }
}
