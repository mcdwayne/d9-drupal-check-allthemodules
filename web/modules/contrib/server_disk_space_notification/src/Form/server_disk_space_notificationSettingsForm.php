<?php

namespace Drupal\server_disk_space_notification\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class server_disk_space_notificationSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'server_disk_space_notification_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'server_disk_space_notification.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('server_disk_space_notification.settings');

    $form['server_space'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Server Disk Space notification"),
      '#default_value' => $config->get('server_space'),
      '#description' => $this->t("Check if you want to get email notification for server disk space and memory."),
    ];

    $form['server_space_email_to'] = [
      '#type' => 'textarea',
      '#title' => $this->t("Email to"),
      '#default_value' => $config->get('server_space_email_to'),
      '#description' => $this->t("Enter multiple email address to get notification email. Please enter email address one by one without adding comma at the end."),
    ];

    $form['server_space_email_from_address'] = [
      '#type' => 'textfield',
      '#title' => $this->t("E-mail from address"),
      '#default_value' => $config->get('server_space_email_from_address'),
      '#description' => $this->t("The e-mail address that all e-mails will be from."),
    ];

    $form['server_space_email_from_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t("E-mail from name"),
      '#default_value' => $config->get('server_space_email_from_name'),
      '#description' => $this->t("The name that all e-mails will be from."),
    ];

    $form['server_space_cron'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Server Space notification cron"),
      '#default_value' => $config->get('server_space_cron'),
      '#description' => $this->t("Check if you want to get email notification for server space for schedule cron. By defult it runs accrording to site predefined cron schedule."),
    ];

    $form['#validate'][] = '::validateServerdiscspace';
    return parent::buildForm($form, $form_state);
  }

  /**
   * Checks from currency is not equal to converted currency.
   */
  public function validateServerdiscspace(array &$form, FormStateInterface $form_state) {
    $server_space_email_to = explode("\n", $form_state->getValue('server_space_email_to'));

    foreach ($server_space_email_to as $key => $value) {
      if (!empty($value)) {
        if (FALSE == filter_var(trim($value), FILTER_VALIDATE_EMAIL)) {
          $form_state->setErrorByName('server_space_email_to', $this->t('Invalid email address -- @value', ['@value' => trim($value)]));
        }
      }
    }
    $server_space_email_from_address = trim($form_state->getValue('server_space_email_from_address'));
    if (empty($server_space_email_from_address)) {
      $form_state->setErrorByName('server_space_email_from_address', $this->t('Invalid email address -- @value', ['@value' => trim($server_space_email_from_address)]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $server_space = $form_state->getValue('server_space');
    $server_space_email_to = $form_state->getValue('server_space_email_to');
    $server_space_email_from_name = $form_state->getValue('server_space_email_from_name');
    $server_space_email_from_address = $form_state->getValue('server_space_email_from_address');
    // Retrieve the configuration.
    $this->config('server_disk_space_notification.settings')
    // Set the submitted configuration setting.
      ->set('server_space', $form_state->getValue('server_space'))
      ->set('server_space_email_to', $form_state->getValue('server_space_email_to'))
      ->set('server_space_email_from_address', $form_state->getValue('server_space_email_from_address'))
      ->set('server_space_email_from_name', $form_state->getValue('server_space_email_from_name'))
      ->set('server_space_cron', $form_state->getValue('server_space_cron'))
      ->save();

    if ($server_space != '') {
      _server_disk_space_notification_email_details($server_space_email_to, $server_space_email_from_name, $server_space_email_from_address);
    }
    parent::submitForm($form, $form_state);
  }

}

