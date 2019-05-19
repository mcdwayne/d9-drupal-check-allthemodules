<?php

namespace Drupal\username_validation\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class UserNameValidationConfig.
 *
 * @package Drupal\user_name_validation\Form
 */
class UserNameValidationConfig extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'username_validation.usernamevalidationconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'username_validation_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('username_validation.usernamevalidationconfig');
    $default_min_char = $config->get('min_char');
    $default_max_char = $config->get('max_char');
    $default_ajax_validation = $config->get('ajax_validation');
    $default_user_label = $config->get('user_label');
    $default_user_desc = $config->get('user_desc');

    $form['username_validation_rule'] = array(
      '#type' => 'fieldset',
      '#title' => t('Username condition'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#tree' => TRUE,
    );
    $form['username_validation_config'] = array(
      '#type' => 'fieldset',
      '#title' => t('Username Configuration'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#tree' => TRUE,
    );
    $form['username_validation_rule']['blacklist_char'] = array(
      '#type' => 'textarea',
      '#default_value' => $config->get('blacklist_char'),
      '#title' => t('Blacklist Characters/Words'),
      '#description' => '<p>' . t("Comma separated characters or words to avoided while saving username. Ex: !,@,#,$,%,^,&,*,(,),1,2,3,4,5,6,7,8,9,0,have,has,were,aren't.") . '</p>' . '<p>' . t('If any of the blacklisted characters/words found in username ,would return validation error on user save.') . '</p>',
    );
    $form['username_validation_rule']['min_char'] = array(
      '#type' => 'textfield',
      '#title' => t("Minimum characters"),
      '#required' => TRUE,
      '#description' => t("Minimum number of characters username should contain"),
      '#size' => 12,
      '#maxlength' => 3,
      '#default_value' => isset($default_min_char) ? $default_min_char : '1',
    );
    $form['username_validation_rule']['max_char'] = array(
      '#type' => 'textfield',
      '#title' => t("Maximum characters"),
      '#required' => TRUE,
      '#description' => t("Maximum number of characters username should contain"),
      '#size' => 12,
      '#maxlength' => 3,
      '#default_value' => isset($default_max_char) ? $default_max_char : '128',
    );

    $form['username_validation_rule']['ajax_validation'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable Ajax Validation'),
      '#description' => t("By default ajax will be triggered on 'change' event."),
      '#default_value' => isset($default_ajax_validation) ? $default_ajax_validation : '',
    );
    $form['username_validation_config']['user_label'] = array(
      '#type' => 'textfield',
      '#title' => t("Username Label"),
      '#description' => t("This value will display instead of username in the registration form"),
      '#size' => 55,
      '#maxlength' => 55,
      '#default_value' => isset($default_user_label) ? $default_user_label : '',
    );

    $form['username_validation_config']['user_desc'] = array(
      '#type' => 'textfield',
      '#title' => t("Username description"),
      '#description' => t("This value will display as username description"),
      '#size' => 55,
      '#maxlength' => 55,
      '#default_value' => isset($default_user_desc) ? $default_user_desc : '',
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $min = $form_state->getValue('username_validation_rule')['min_char'];
    $max = $form_state->getValue('username_validation_rule')['max_char'];
    // Validate field is numerical.
    if (!is_numeric($max)) {
      $form_state->setErrorByName('max_char', t('These value should be Numerical'));
    }

    // Validate field should be between 0 and 128.
    if ($max <= 0 || $max > 128) {
      $form_state->setErrorByName('max_char', t('These value should be between 0 and 128'));
    }

    // Validate field is numerical.
    if (!is_numeric($min)) {
      $form_state->setErrorByName('min_char', t('These value should be Numerical'));
    }

    // Validate field is greater than 1.
    if ($min < 1) {
      $form_state->setErrorByName('min_char', t('These value should be more than 1'));
    }

    // Validate min is less than max value.
    if ($min > $max) {
      $form_state->setErrorByName('min_char', t('Minimum length should not be more than Max length'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('username_validation.usernamevalidationconfig')
      ->set('blacklist_char', $form_state->getValue('username_validation_rule')['blacklist_char'])
      ->set('min_char', $form_state->getValue('username_validation_rule')['min_char'])
      ->set('max_char', $form_state->getValue('username_validation_rule')['max_char'])
      ->set('ajax_validation', $form_state->getValue('username_validation_rule')['ajax_validation'])
      ->set('user_label', $form_state->getValue('username_validation_config')['user_label'])
      ->set('user_desc', $form_state->getValue('username_validation_config')['user_desc'])
      ->save();
  }

}
