<?php


namespace Drupal\node_notify\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class NotificationConfig extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'notification_setting_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['node_notify.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('node_notify.settings');
    $form['node_notify_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable notification module'),
      '#default_value' => $config->get('enable'),
      '#description' => $this->t('Enable notification.'),
    ];
    $form['node_notify_author'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable notification module to notify author of node expiration.'),
      '#default_value' => $config->get('send_email_to_author'),
      '#description' => $this->t('Enable notification module to notify author of node expiration.'),
    ];
    $form['node_notify_email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Notification email subject'),
      '#default_value' => $config->get('mail_subject'),
      '#description' => $this->t('Email used to notify user.'),
    ];
    $form['node_notify_email'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Notification email body'),
      '#default_value' => $config->get('mail_body'),
      '#description' => $this->t('Email used to notify user.'),
    ];
    $form['node_notify_days'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of days'),
      '#default_value' => $config->get('days'),
      '#description' => $this->t('Days before notification email should sent. Leave empty if email needs to be sent on expiration date.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::configFactory()->getEditable('node_notify.settings')
      ->set('enable', $form_state->getValue('node_notify_enable'))
      ->set('send_email_to_author', $form_state->getValue('node_notify_author'))
      ->set('mail_subject', $form_state->getValue('node_notify_email_subject'))
      ->set('mail_body', $form_state->getValue('node_notify_email'))
      ->set('days', $form_state->getValue('node_notify_days'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
