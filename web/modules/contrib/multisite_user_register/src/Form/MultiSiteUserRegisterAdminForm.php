<?php

namespace Drupal\multisite_user_register\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 *
 */
class MultiSiteUserRegisterAdminForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'multisite_user_register_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = array();
    // Supported Default fields.
    $support = array(
      'language',
      'preferred_langcode',
      'name',
      'mail',
      'timezone',
      'status',
      'roles',
      'default_langcode',
      'path',
      'user_picture',
    );
    $field_definitions = \Drupal::entityManager()
      ->getFieldDefinitions('user', 'user');
    foreach ($field_definitions as $key => $value) {
      if (strpos($key, 'field_') !== FALSE) {
        $options[$key] = $value->getLabel();
      }
      if (in_array($key, $support)) {
        if ($value->getLabel() instanceof TranslatableMarkup) {
          $options[$key] = $value->getLabel()->getUntranslatedString();
        }
        else {
          $options[$key] = $value->getLabel();
        }
      }
    }
    // Get configuration value.
    $multisite_user_register_config = \Drupal::config('multisite_user_register.field_name_settings')
      ->get('multisite_user_register_config');
    $form['fields'] = [
      '#type' => 'checkboxes',
      '#title' => t('Select Fields to register'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => isset($multisite_user_register_config['fields']) ? $multisite_user_register_config['fields'] : 0,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Save',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values['fields'] = $form_state->getValue(['fields']);
    // Set multisite_user_register_config variable.
    \Drupal::configFactory()
      ->getEditable('multisite_user_register.field_name_settings')
      ->set('multisite_user_register_config', $values)
      ->save();
    drupal_set_message(t('Configurations saved successfully!'));
  }

}
