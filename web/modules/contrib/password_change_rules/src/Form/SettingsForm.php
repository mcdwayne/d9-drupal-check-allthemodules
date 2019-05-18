<?php

namespace Drupal\password_change_rules\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Password Change Rules settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('password_change_rules.settings');

    $form['configuration'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Configuration'),
    ];
    $form['configuration']['change_password_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Change password message'),
      '#description' => $this->t("This message is shown to the user when they're told they must change their password"),
      '#default_value' => $config->get('change_password_message'),
    ];

    $form['enforcements'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Enforcements'),
    ];
    $form['enforcements']['admin_registered_account'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Require password change if the account is registered by an admin'),
      '#description' => $this->t('Any user account that is created by an admin will be required to change their password when they first log in.'),
      '#default_value' => $config->get('admin_registered_account'),
    ];

    $form['enforcements']['admin_change_password'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Require password change after admin changes the password'),
      '#description' => $this->t('If an admin changes another users password, the user will be required to change their password the next time they login.'),
      '#default_value' => $config->get('admin_change_password'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('password_change_rules.settings');
    $data = array_intersect_key($form_state->getValues(), $config->get());
    $config
      ->setData($data)
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['password_change_rules.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'password_change_rules_settings';
  }

}
