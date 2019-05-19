<?php

namespace Drupal\view_password\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * {@inheritdoc}
 */
class PasswordSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {

    return 'password_settings_form';

  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $config = $this->config('pwd.settings');

    $form['form_id_pwd'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Enter the form id here.'),
      '#description' => $this->t('Please enter the form id(s) by separating it with a comma. Here the default is user_login_form. You can remove and save the form if you do not want to display the password for this form.'),
      '#default_value' => $config->get('pwd.form_id_pwd'),
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('pwd.settings');
    $config->set('pwd.form_id_pwd', $form_state->getValue('form_id_pwd'));
    $config->save();
    return parent::submitForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {

    return [
      'pwd.settings',
    ];

  }

}
