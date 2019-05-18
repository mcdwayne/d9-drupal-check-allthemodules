<?php

namespace Drupal\discourse_sso\Form;

use Drupal\Core\Form\FormBase;

class DiscourseSsoAdminForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'discourse_sso_settings_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $config = \Drupal::config('discourse_sso.settings');

    $form['discourse_server'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Discourse URL'),
      '#description' => $this->t('The web address of the Discourse server.'),
      '#size' => 40,
      '#maxlength' => 120,
      '#required' => TRUE,
      '#default_value' =>  $config->get('discourse_server'),
    ];

    $form['discourse_sso_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SSO Secret'),
      '#description' => $this->t('Secret string used to encrypt/decrypt SSO information, be sure it is 10 chars or longer and matches the Discourse setting under Admin > Settings > Login. If there is a value here Drupal will attempt to login the user to Discourse on Drupal login.'),
      '#default_value' =>  $config->get('discourse_sso_secret'),
    ];

    $form['api_username'] = [
      '#type' => 'textfield',
      '#title' => t('API user name'),
      '#description' => t('The name of the discourse user.'),
      '#default_value' => $config->get('api_username'),
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#description' => $this->t('The API Key of the discourse user.'),
      '#default_value' => $config->get('api_key'),
    ];

    $options = $this->getTextFieldsByEntityTypeBundle('user', 'user');
    if (count($options)) {
      $form['user_real_name_field'] = [
        '#type' => 'select',
        '#title' => $this->t('User real name field'),
        '#description' => $this->t('The Drupal user text field to use for the discourse user name field.'),
        '#default_value' =>  $config->get('user_real_name_field'),
        '#options' => $options,
        '#empty_value' => TRUE,
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save settings'),
    ];

    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $url = $form_state->getValue(['discourse_server']);

    if (substr($url, 0, 4) != 'http') {
      $form_state->setValue(['discourse_server'], 'http://' . $url);
    }
    // Remove any trailing slash.
    if (substr($form_state->getValue([
      'discourse_server'
      ]), -1) == '/') {
      $form_state->setValue(['discourse_server'], substr($form_state->getValue([
        'discourse_server'
        ]), 0, -1));
    }
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    \Drupal::configFactory()->getEditable('discourse_sso.settings')
      ->set('discourse_server', $form_state->getValue(['discourse_server']))
      ->set('discourse_sso_secret', $form_state->getValue(['discourse_sso_secret']))
      ->set('api_username', $form_state->getValue('api_username'))
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('user_real_name_field', $form_state->getValue('user_real_name_field'))
      ->save();

    drupal_set_message($this->t('The settings have been saved'));
  }

  protected function getTextFieldsByEntityTypeBundle($entity_type, $bundle) {
    $bundleFields = [];

    $fields = \Drupal::entityManager()->getFieldDefinitions($entity_type, $bundle);
    foreach ($fields as $field_name => $field_definition) {
      if (!empty($field_definition->getTargetBundle())) {
        if ($field_definition->getType() === 'string') {
        $bundleFields[$field_name] = $field_definition->getLabel();
        }
      }
    }

    return $bundleFields;
  }

}
