<?php

namespace Drupal\shield_pages\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure shield page settings for this site.
 */
class ShieldPageSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shield_pages_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['shield_pages.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('shield_pages.settings');

    $password_types = [
      '#theme' => 'item_list',
      '#items' => [
        $this->t('Allow per page password = Only per page password will be accepted. Global password will not be accepted.'),
        $this->t('Allow per page password or Global password = Per page  password and global password both will be accepted.'),
        $this->t('Allow Only Global = Only global password will be accepted for each protected page.'),
      ],
    ];

    // Shield page password settings.
    $form['password_settings'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Shield page password settings'),
      '#description' => $this->t('Configure password related settings.'),
    ];
    $form['password_settings']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Global Password Setting'),
      '#default_value' => $config->get('password_settings.type'),
      '#options' => [
        'per_page_password' => $this->t('Allow per page password'),
        'per_page_or_global' => $this->t('Allow per page password or Global password'),
        'only_global' => $this->t('Allow Only Global'),
      ],
      '#description' => $this->t('Please select the appropriate option for shield pages handling.') . render($password_types),
    ];
    $form['password_settings']['global_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Global Password'),
      '#description' => $this->t('The default password for all shield pages. This password is necessary if you select the previous checkbox "Allow per page password or Global password" or "Allow Only Global" options above.'),
      '#default_value' => $config->get('password_settings.global_password'),
    ];
    $form['password_settings']['session_expire_time'] = [
      '#type' => 'number',
      '#title' => $this->t('Session Expire Time'),
      '#description' => $this->t('When user enters password a session is created. The node will be accessible until session expire. Once session expires, user will need to enter password again. The default session expire time is 0 (unlimited).'),
      '#default_value' => $config->get('password_settings.session_expire_time'),
      '#required' => TRUE,
      '#min' => 0,
      '#field_suffix' => t('in minutes'),
    ];

    // Shield page other settings.
    $form['other_settings'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Shield page other settings'),
      '#description' => $this->t('Configure other settings.'),
    ];
    $form['other_settings']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password page title'),
      '#default_value' => $config->get('other_settings.title'),
      '#description' => $this->t('Enter the title of the Shield page.'),
    ];
    $form['other_settings']['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Password page description (inside the field set)'),
      '#default_value' => $config->get('other_settings.description'),
      '#description' => $this->t('Enter specific description for the protected page. This description is displayed inside the fieldset. HTML is accepted.'),
    ];
    $form['other_settings']['field_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password field label'),
      '#default_value' => $config->get('other_settings.field_label'),
      '#description' => $this->t('Enter the text for the password field label.'),
    ];
    $form['other_settings']['submit_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Submit Button Text'),
      '#default_value' => $config->get('other_settings.submit_text'),
      '#description' => $this->t('Enter the text for the submit button of enter password form.'),
    ];
    $form['other_settings']['incorrect_password_msg'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Incorrect Password Error Text'),
      '#default_value' => $config->get('other_settings.incorrect_password_msg'),
      '#description' => $this->t('This error text will appear if someone enters wrong password in "Enter password screen".'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('shield_pages.settings')
      ->set('password_settings.type', $form_state->getValue('type'))
      ->set('password_settings.global_password', $form_state->getValue('global_password'))
      ->set('password_settings.session_expire_time', $form_state->getValue('session_expire_time'))
      ->set('other_settings.title', $form_state->getValue('title'))
      ->set('other_settings.description', $form_state->getValue('description'))
      ->set('other_settings.field_label', $form_state->getValue('field_label'))
      ->set('other_settings.submit_text', $form_state->getValue('submit_text'))
      ->set('other_settings.incorrect_password_msg', $form_state->getValue('incorrect_password_msg'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
