<?php
namespace Drupal\password_policy_configuration\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PasswordPolicyConfigurationForm.
 */
class PasswordPolicyConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'password_policy_configuration.passwordpolicyconfiguration',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'password_policy_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('password_policy_configuration.passwordpolicyconfiguration');
    $form['password_policy_configuration_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Password Policy Configuration Details'),

    ];
    $form['password_policy_configuration_fieldset']['password_length'] = [
      '#type' => 'number',
      '#min' => '0',
      '#required' => TRUE,
      '#title' => $this->t('Password Length'),
      '#maxlength' => 10,
      '#size' => 10,
      '#default_value' => $config->get('password_length'),
    ];
    $form['password_policy_configuration_fieldset']['upper_case_length'] = [
      '#type' => 'number',
      '#min' => '0',
      '#required' => TRUE,
      '#title' => $this->t('Upper Case Length'),
      '#maxlength' => 10,
      '#size' => 10,
      '#default_value' => $config->get('upper_case_length'),
    ];
    $form['password_policy_configuration_fieldset']['lower_case_length'] = [
      '#type' => 'number',
      '#min' => '0',
      '#required' => TRUE,
      '#title' => $this->t('Lower Case Length'),
      '#maxlength' => 10,
      '#size' => 10,
      '#default_value' => $config->get('lower_case_length'),
    ];
    $form['password_policy_configuration_fieldset']['special_char_length'] = [
      '#type' => 'number',
      '#min' => '0',
      '#required' => TRUE,
      '#title' => $this->t('Special Char Length'),
      '#maxlength' => 10,
      '#size' => 10,
      '#default_value' => $config->get('special_char_length'),
    ];
    $form['password_policy_configuration_fieldset']['numeric_length'] = [
      '#type' => 'number',
      '#min' => '0',
      '#required' => TRUE,
      '#title' => $this->t('Numeric Length'),
      '#maxlength' => 10,
      '#size' => 10,
      '#default_value' => $config->get('numeric_length'),
    ];
    $form['password_policy_configuration_fieldset']['pass_reuse'] = [
      '#type' => 'number',
      '#min' => '0',
      '#required' => TRUE,
      '#title' => $this->t('Password Reuse Period'),
      '#maxlength' => 10,
      '#size' => 10,
      '#default_value' => $config->get('pass_reuse'),
    ];
    $form['exclude_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude Admin User'),
      '#description' => $this->t('Check to exclude administrator roles user from password policy validation'),
      '#default_value' => $config->get('exclude_admin'),
    ];
    $form['force_validation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force Validation'),
      '#description' => $this->t('Check to force validation for password policy'),
      '#default_value' => $config->get('force_validation'),
    ];
    $form['en_pass_reuse'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Password Reuse validation'),
      '#description' => $this->t('Check to enable Password Reuse Policy'),
      '#default_value' => $config->get('en_pass_reuse'),
    ];
    $form['validate_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Validation Message'),
      '#description' => $this->t('Message to show on validation error.'),
      '#maxlength' => 225,
      '#size' => 225,
      '#default_value' => $config->get('validate_message'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('password_policy_configuration.passwordpolicyconfiguration')
      ->set('password_length', $form_state->getValue('password_length'))
      ->set('upper_case_length', $form_state->getValue('upper_case_length'))
      ->set('lower_case_length', $form_state->getValue('lower_case_length'))
      ->set('special_char_length', $form_state->getValue('special_char_length'))
      ->set('numeric_length', $form_state->getValue('numeric_length'))
      ->set('pass_reuse', $form_state->getValue('pass_reuse'))
      ->set('exclude_admin', $form_state->getValue('exclude_admin'))
      ->set('force_validation', $form_state->getValue('force_validation'))
      ->set('en_pass_reuse', $form_state->getValue('en_pass_reuse'))
      ->set('validate_message', $form_state->getValue('validate_message'))
      ->save();
  }
}
